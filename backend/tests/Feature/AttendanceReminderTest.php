<?php

namespace Tests\Feature;

use App\Models\InAppNotification;
use App\Models\Role;
use App\Models\SchoolCalendar;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceReminderTest extends TestCase
{
    use RefreshDatabase;

    private Role $guruRole;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->guruRole = Role::firstOrCreate(['nama' => Role::GURU], ['guard_name' => 'web']);
    }

    private function createActiveTeacher(string $username): Teacher
    {
        $user = User::create([
            'nama' => 'Guru ' . $username,
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $this->guruRole->id,
            'status' => 'aktif',
        ]);

        return Teacher::create([
            'user_id' => $user->id,
            'nik' => '3302' . str_pad((string) $user->id, 12, '0', STR_PAD_LEFT),
            'nama_lengkap' => $user->nama,
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Banyumas',
            'tgl_lahir' => '1990-01-01',
            'alamat_jalan' => 'Jl. Sekolah No. 1',
            'kab_kota' => 'Banyumas',
            'no_hp' => '+628123456' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'jenis_ptk' => 'guru_kelas',
            'status_kepegawaian' => 'gty',
            'tgl_mulai_tugas' => '2020-07-01',
            'status' => 'aktif',
        ]);
    }

    public function test_checkin_reminder_notifies_only_teachers_without_attendance_on_effective_day(): void
    {
        $teacher1 = $this->createActiveTeacher('guru_belum');
        $teacher2 = $this->createActiveTeacher('guru_sudah');

        // Senin 2026-11-23 (bukan hari Minggu / libur)
        $effectiveDate = '2026-11-23';

        TeacherAttendance::create([
            'teacher_id' => $teacher2->id,
            'tanggal' => $effectiveDate,
            'jam_masuk' => '06:55:00',
            'status' => 'hadir',
            'status_persetujuan' => 'disetujui',
        ]);

        Setting::set('notification.channels.in_app', '1');
        Setting::set('notification.channels.web_push', '0');
        Setting::set('notification.channels.email', '0');

        $this->artisan('attendance:send-reminders', [
            '--type' => 'checkin',
            '--date' => $effectiveDate,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacher1->user_id,
            'judul' => 'Pengingat Presensi Masuk',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $teacher2->user_id,
            'judul' => 'Pengingat Presensi Masuk',
        ]);
    }

    public function test_checkout_reminder_notifies_only_teachers_who_checked_in_without_checkout(): void
    {
        $teacherNeedCheckout = $this->createActiveTeacher('guru_perlu_pulang');
        $teacherAlreadyOut = $this->createActiveTeacher('guru_sudah_pulang');

        $effectiveDate = '2026-11-23';

        TeacherAttendance::create([
            'teacher_id' => $teacherNeedCheckout->id,
            'tanggal' => $effectiveDate,
            'jam_masuk' => '06:55:00',
            'jam_keluar' => null,
            'status' => 'hadir',
            'status_persetujuan' => 'disetujui',
        ]);

        TeacherAttendance::create([
            'teacher_id' => $teacherAlreadyOut->id,
            'tanggal' => $effectiveDate,
            'jam_masuk' => '06:50:00',
            'jam_keluar' => '14:35:00',
            'status' => 'hadir',
            'status_persetujuan' => 'disetujui',
        ]);

        Setting::set('notification.channels.in_app', '1');
        Setting::set('notification.channels.web_push', '0');

        $this->artisan('attendance:send-reminders', [
            '--type' => 'checkout',
            '--date' => $effectiveDate,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacherNeedCheckout->user_id,
            'judul' => 'Pengingat Presensi Pulang',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $teacherAlreadyOut->user_id,
            'judul' => 'Pengingat Presensi Pulang',
        ]);
    }

    public function test_reminders_are_skipped_on_school_holidays(): void
    {
        $teacher = $this->createActiveTeacher('guru_libur');
        $holidayDate = '2026-11-24';

        SchoolCalendar::create([
            'judul' => 'Libur Nasional Simulasi',
            'tanggal_mulai' => $holidayDate,
            'tanggal_selesai' => $holidayDate,
            'kategori' => 'libur_nasional',
            'is_hari_libur' => true,
        ]);

        $this->artisan('attendance:send-reminders', [
            '--type' => 'checkin',
            '--date' => $holidayDate,
        ])->assertSuccessful();

        $this->assertEquals(0, InAppNotification::where('user_id', $teacher->user_id)->count());
    }

    public function test_user_can_subscribe_and_unsubscribe_web_push(): void
    {
        $teacher = $this->createActiveTeacher('guru_push');
        $user = User::findOrFail($teacher->user_id);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/example-subscription-token';

        $this->actingAs($user)
            ->postJson('/api/v1/push/subscribe', [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'BPublicKeyExampleString123',
                    'auth' => 'AuthSecretExample123',
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint_hash' => hash('sha256', $endpoint),
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/push/unsubscribe', [
                'endpoint' => $endpoint,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $endpoint),
        ]);
    }
}
