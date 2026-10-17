<?php

use App\Models\AcademicYear;
use App\Models\Backup;
use App\Models\FeeType;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Religion;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 1. Roles
    $this->roleAdmin = Role::firstOrCreate(['nama' => 'staf_admin'], ['deskripsi' => 'Staf Administrasi']);
    $this->roleKepsek = Role::firstOrCreate(['nama' => 'kepala_sekolah'], ['deskripsi' => 'Kepala Sekolah']);
    $this->roleGuru = Role::firstOrCreate(['nama' => 'guru'], ['deskripsi' => 'Guru']);
    $this->roleWali = Role::firstOrCreate(['nama' => 'wali'], ['deskripsi' => 'Wali Murid']);

    // 2. School Profile
    $this->school = SchoolProfile::create([
        'nama_sekolah' => 'SDI Bina Insan Mandiri',
        'npsn' => '20300001',
        'jenjang' => 'SD',
        'status_sekolah' => 'Swasta',
        'alamat_jalan' => 'Jl. Sunan Kalijaga No. 12',
        'kecamatan' => 'Purwokerto Selatan',
        'kab_kota' => 'Banyumas',
        'provinsi' => 'Jawa Tengah',
        'telepon' => '0281-630001',
        'email' => 'info@sdibim.sch.id',
        'geofence_lat' => -7.424300,
        'geofence_lng' => 109.230100,
        'geofence_radius_meter' => 100,
        'jam_masuk_guru' => '07:00:00',
        'jam_pulang_guru' => '14:00:00',
    ]);

    // 3. Academic Year
    $this->year = AcademicYear::create([
        'nama' => '2026/2027 Ganjil',
        'semester' => 'ganjil',
        'tgl_mulai' => '2026-07-15',
        'tgl_selesai' => '2026-12-20',
        'is_aktif' => true,
    ]);

    // 4. Users
    $this->adminPassword = 'SecretPassword123!';
    $this->admin = User::create([
        'nama' => 'Staf TU Purwokerto',
        'username' => 'admin_tu',
        'email' => 'tu@sdibim.sch.id',
        'role_id' => $this->roleAdmin->id,
        'password' => Hash::make($this->adminPassword),
        'status' => 'aktif',
    ]);

    $this->kepsek = User::create([
        'nama' => 'Ustadz Ahmad Al-Hafidz',
        'username' => 'kepsek_ahmad',
        'email' => 'kepsek@sdibim.sch.id',
        'role_id' => $this->roleKepsek->id,
        'password' => Hash::make('password123'),
        'status' => 'aktif',
    ]);

    $this->guruUser = User::create([
        'nama' => 'Ustadzah Fatimah',
        'username' => 'guru_fatimah',
        'email' => 'fatimah@sdibim.sch.id',
        'role_id' => $this->roleGuru->id,
        'password' => Hash::make('password123'),
        'status' => 'aktif',
    ]);
    $this->teacher = Teacher::create([
        'user_id' => $this->guruUser->id,
        'nik' => '3302015001850001',
        'nip' => '198501012010012001',
        'nama_lengkap' => 'Ustadzah Fatimah S.Pd.I',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Banyumas',
        'tgl_lahir' => '1985-01-01',
        'alamat_jalan' => 'Jl. Masjid No. 5',
        'kab_kota' => 'Banyumas',
        'provinsi' => 'Jawa Tengah',
        'no_hp' => '081234567890',
        'email' => 'fatimah@sdibim.sch.id',
        'jenis_ptk' => 'guru_kelas',
        'status_kepegawaian' => 'gty',
        'tgl_mulai_tugas' => '2020-07-01',
        'status' => 'aktif',
    ]);

    $this->waliUser = User::create([
        'nama' => 'Bapak Hendra',
        'username' => 'wali_hendra',
        'email' => 'hendra@gmail.com',
        'role_id' => $this->roleWali->id,
        'password' => Hash::make('password123'),
        'status' => 'aktif',
    ]);
    $this->guardian = Guardian::create([
        'user_id' => $this->waliUser->id,
        'nama_lengkap' => 'Bapak Hendra',
        'hubungan' => 'ayah',
        'no_hp' => '081987654321',
        'alamat_jalan' => 'Jl. Pahlawan No. 4',
        'is_kontak_utama' => true,
    ]);

    $this->class = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'kode' => '1A',
        'nama' => 'Kelas 1 Abu Bakar',
        'tingkat' => 1,
        'paralel' => 'A',
        'wali_kelas_id' => $this->teacher->id,
        'kapasitas' => 28,
        'status' => 'aktif',
    ]);

    $religion = Religion::firstOrCreate(['nama' => 'Islam']);

    $this->student = Student::create([
        'nis' => '2627001',
        'nisn' => '0012345678',
        'nik' => '3302011005190001',
        'nama_lengkap' => 'Ahmad Bilal Al-Farisi',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Banyumas',
        'tgl_lahir' => '2019-05-10',
        'agama' => $religion->id,
        'alamat_jalan' => 'Jl. Kalibagor No. 7',
        'desa_kelurahan' => 'Kalibagor',
        'kecamatan' => 'Kalibagor',
        'kab_kota' => 'Banyumas',
        'provinsi' => 'Jawa Tengah',
        'status' => 'aktif',
        'tgl_masuk' => '2026-07-15',
        'tahun_masuk' => 2026,
        'class_id' => $this->class->id,
    ]);
});

test('kepala sekolah and admin can view executive dashboard summary with KPIs and real-time attendance', function () {
    // Record teacher attendance today
    TeacherAttendance::create([
        'teacher_id' => $this->teacher->id,
        'tanggal' => now()->toDateString(),
        'jam_masuk' => '06:55:00',
        'status' => 'hadir',
        'lat' => -7.424300,
        'lng' => 109.230100,
        'jarak_meter' => 15,
        'is_in_geofence' => true,
    ]);

    // As Kepala Sekolah
    Sanctum::actingAs($this->kepsek);

    $response = $this->getJson('/api/v1/dashboard/summary');
    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'tanggal',
                'hari',
                'akademik' => [
                    'total_siswa',
                    'siswa_laki',
                    'siswa_perempuan',
                    'total_guru',
                    'total_tendik',
                    'total_kelas',
                ],
                'kehadiran_hari_ini' => [
                    'guru',
                    'siswa',
                    'bukti_mengajar',
                ],
                'keuangan_bulan_ini',
                'sarpras',
                'aktivitas_terbaru',
            ],
        ]);

    expect($response->json('data.akademik.total_siswa'))->toBe(1);
    expect($response->json('data.akademik.total_guru'))->toBe(1);
    expect($response->json('data.kehadiran_hari_ini.guru.hadir'))->toBe(1);

    // Guru should be forbidden (403)
    Sanctum::actingAs($this->guruUser);
    $this->getJson('/api/v1/dashboard/summary')->assertForbidden();

    // Wali should be forbidden (403)
    Sanctum::actingAs($this->waliUser);
    $this->getJson('/api/v1/dashboard/summary')->assertForbidden();
});

test('admin can trigger manual backup, list backups, download, and delete', function () {
    Sanctum::actingAs($this->admin);

    // 1. Create manual backup
    $resStore = $this->postJson('/api/v1/backups', [
        'catatan' => 'Manual backup sebelum pembaruan semester',
    ]);

    $resStore->assertCreated();
    $backupId = $resStore->json('data.id');
    $fileName = $resStore->json('data.nama_file');

    expect($backupId)->not()->toBeNull();
    expect($resStore->json('data.tipe'))->toBe('manual');
    expect($resStore->json('data.status'))->toBe('sukses');

    // 2. List backups with summary
    $resIndex = $this->getJson('/api/v1/backups');
    $resIndex->assertOk();
    expect($resIndex->json('summary.total_backup'))->toBeGreaterThanOrEqual(1);
    expect($resIndex->json('data.0.nama_file'))->toBe($fileName);

    // 3. Download backup
    $resDownload = $this->getJson("/api/v1/backups/{$backupId}/download");
    $resDownload->assertOk();
    $resDownload->assertHeader('content-type', 'application/zip');

    // 4. Delete backup
    $resDelete = $this->deleteJson("/api/v1/backups/{$backupId}");
    $resDelete->assertOk();
    expect(Backup::find($backupId))->toBeNull();
});

test('admin can restore database from backup with double confirmation password', function () {
    Sanctum::actingAs($this->admin);

    // 1. Create a backup
    $resStore = $this->postJson('/api/v1/backups', [
        'catatan' => 'Backup untuk uji restore',
    ]);
    $resStore->assertCreated();
    $backupId = $resStore->json('data.id');

    // 2. Attempt restore with wrong password -> should be rejected with 422
    $failRestore = $this->postJson("/api/v1/backups/{$backupId}/restore", [
        'password' => 'WrongPassword!',
    ]);
    $failRestore->assertStatus(422);

    // 3. Attempt restore with correct admin password -> should succeed
    $successRestore = $this->postJson("/api/v1/backups/{$backupId}/restore", [
        'password' => $this->adminPassword,
    ]);
    $successRestore->assertOk();
    expect($successRestore->json('data.status'))->toBe('dipulihkan');
});

test('artisan app:backup-run command works via CLI and scheduled runner', function () {
    $exitCode = Artisan::call('app:backup-run', [
        '--type' => 'otomatis',
        '--note' => 'Pengujian backup terjadwal',
    ]);

    expect($exitCode)->toBe(0);

    $autoBackup = Backup::where('tipe', 'otomatis')->latest('created_at')->first();
    expect($autoBackup)->not()->toBeNull();
    expect($autoBackup->status)->toBe('sukses');
});

test('user can update profile and change password', function () {
    Sanctum::actingAs($this->admin);

    // 1. Update profile
    $resProfile = $this->putJson('/api/v1/me', [
        'nama' => 'Staf TU SDI BIM Baru',
        'email' => 'tu_baru@sdibim.sch.id',
        'no_hp' => '081299998888',
    ]);
    $resProfile->assertOk();
    expect($resProfile->json('data.nama'))->toBe('Staf TU SDI BIM Baru');

    // 2. Change password with incorrect current password
    $resFailPass = $this->putJson('/api/v1/me/password', [
        'current_password' => 'SalahPassword',
        'new_password' => 'PasswordBaru123!',
        'new_password_confirmation' => 'PasswordBaru123!',
    ]);
    $resFailPass->assertStatus(422);

    // 3. Change password successfully
    $resSuccessPass = $this->putJson('/api/v1/me/password', [
        'current_password' => $this->adminPassword,
        'new_password' => 'PasswordBaru123!',
        'new_password_confirmation' => 'PasswordBaru123!',
    ]);
    $resSuccessPass->assertOk();

    // Verify new password works
    $this->admin->refresh();
    expect(Hash::check('PasswordBaru123!', $this->admin->password))->toBeTrue();
});
