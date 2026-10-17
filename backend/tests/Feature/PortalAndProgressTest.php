<?php

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\DailyActivity;
use App\Models\Guardian;
use App\Models\InAppNotification;
use App\Models\LearningProgress;
use App\Models\Religion;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\ReferenceTableSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SchoolProfileSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RoleSeeder::class);
    $this->seed(ReferenceTableSeeder::class);
    $this->seed(SchoolProfileSeeder::class);
    $this->seed(SettingSeeder::class);

    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $roleGuru = Role::where('nama', Role::GURU)->first();
    $roleWali = Role::where('nama', Role::WALI)->first();

    // Admin
    $this->admin = User::create([
        'nama' => 'Staf Administrasi',
        'username' => 'stafadmin',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);

    // Guru
    $this->userGuru = User::create([
        'nama' => 'Ustadz Zaid',
        'username' => 'zaid',
        'password' => Hash::make('password123'),
        'role_id' => $roleGuru->id,
        'status' => 'aktif',
    ]);

    $this->teacher = Teacher::create([
        'user_id' => $this->userGuru->id,
        'nik' => '3302011101900001',
        'nama_lengkap' => 'Ustadz Zaid',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '1990-01-11',
        'alamat_jalan' => 'Jl. Masjid No. 7',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567080',
        'jenis_ptk' => 'guru_kelas',
        'status_kepegawaian' => 'gty',
        'tgl_mulai_tugas' => '2020-07-01',
        'status' => 'aktif',
    ]);

    // Wali 1
    $this->userWali1 = User::create([
        'nama' => 'Wali Murid 1',
        'username' => 'wali1',
        'password' => Hash::make('password123'),
        'role_id' => $roleWali->id,
        'status' => 'aktif',
    ]);
    $this->guardian1 = Guardian::create([
        'user_id' => $this->userWali1->id,
        'nik' => '3302011005850001',
        'nama_lengkap' => 'Wali Murid 1',
        'hubungan' => 'ayah',
        'jenis_kelamin' => 'L',
        'alamat_jalan' => 'Jl. Mawar No. 1',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567081',
        'status' => 'aktif',
    ]);

    // Wali 2 (Wali anak lain)
    $this->userWali2 = User::create([
        'nama' => 'Wali Murid 2',
        'username' => 'wali2',
        'password' => Hash::make('password123'),
        'role_id' => $roleWali->id,
        'status' => 'aktif',
    ]);
    $this->guardian2 = Guardian::create([
        'user_id' => $this->userWali2->id,
        'nik' => '3302011005850002',
        'nama_lengkap' => 'Wali Murid 2',
        'hubungan' => 'ibu',
        'jenis_kelamin' => 'P',
        'alamat_jalan' => 'Jl. Melati No. 2',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567082',
        'status' => 'aktif',
    ]);

    $this->year = AcademicYear::create([
        'nama' => '2026/2027',
        'semester' => 'ganjil',
        'tgl_mulai' => '2026-07-15',
        'tgl_selesai' => '2026-12-20',
        'is_aktif' => true,
    ]);

    $this->room = Room::create(['kode' => 'R201', 'nama' => 'Ruang 2A', 'kapasitas' => 30, 'jenis' => 'kelas']);
    $this->class1 = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'kode' => '2A',
        'tingkat' => 2,
        'nama' => '2A Umar bin Khattab',
        'wali_kelas_id' => $this->teacher->id,
        'room_id' => $this->room->id,
        'kapasitas' => 28,
        'status' => 'aktif',
    ]);

    $this->class2 = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'kode' => '2B',
        'tingkat' => 2,
        'nama' => '2B Utsman bin Affan',
        'room_id' => $this->room->id,
        'kapasitas' => 28,
        'status' => 'aktif',
    ]);

    $this->subject = Subject::create([
        'kode' => 'TAHFIDZ',
        'nama' => 'Tahfidz Al-Qur\'an',
        'kelompok' => 'B',
        'kkm_default' => 80,
        'is_aktif' => true,
    ]);

    // Siswa 1 (milik Wali 1 di kelas 2A)
    $this->student1 = Student::create([
        'nis' => '26010',
        'nisn' => '0012345688',
        'nik' => '3302012001150010',
        'nama_lengkap' => 'Muhammad Bilal',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '2018-05-10',
        'agama' => Religion::first()->id,
        'alamat_jalan' => 'Jl. Mawar No. 1',
        'kab_kota' => 'Banyumas',
        'tgl_masuk' => '2025-07-15',
        'tahun_masuk' => 2025,
        'class_id' => $this->class1->id,
        'status' => 'aktif',
    ]);
    $this->student1->guardians()->attach($this->guardian1->id, [
        'hubungan' => 'ayah',
        'is_kontak_utama' => true,
        'boleh_lihat_keuangan' => true,
        'boleh_jemput' => true,
    ]);

    // Siswa 2 (milik Wali 2 di kelas 2B)
    $this->student2 = Student::create([
        'nis' => '26020',
        'nisn' => '0012345699',
        'nik' => '3302012001150020',
        'nama_lengkap' => 'Khadijah Al-Kubra',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '2018-08-20',
        'agama' => Religion::first()->id,
        'alamat_jalan' => 'Jl. Melati No. 2',
        'kab_kota' => 'Banyumas',
        'tgl_masuk' => '2025-07-15',
        'tahun_masuk' => 2025,
        'class_id' => $this->class2->id,
        'status' => 'aktif',
    ]);
    $this->student2->guardians()->attach($this->guardian2->id, [
        'hubungan' => 'ibu',
        'is_kontak_utama' => true,
        'boleh_lihat_keuangan' => true,
        'boleh_jemput' => true,
    ]);
});

test('teacher can record daily class activity and guardian in that class can view it with notification', function () {
    Sanctum::actingAs($this->userGuru);

    $activityRes = $this->postJson('/api/v1/activities', [
        'class_id' => $this->class1->id,
        'tanggal' => now()->toDateString(),
        'judul' => 'Praktik Wudhu dan Sholat Dhuha Berjamaah',
        'deskripsi' => 'Anak-anak kelas 2A hari ini mempraktikkan gerakan wudhu yang benar dilanjutkan sholat Dhuha.',
        'kategori' => 'karakter',
        'foto' => UploadedFile::fake()->image('wudhu.jpg', 800, 600),
    ]);

    $activityRes->assertCreated();
    expect($activityRes->json('data.photo_path'))->not->toBeNull();

    // Verify in-app notification sent to guardian1
    $notif = InAppNotification::where('user_id', $this->userWali1->id)->first();
    expect($notif)->not->toBeNull();
    expect($notif->judul)->toContain('Praktik Wudhu');

    // Guardian 1 (anaknya di kelas 2A) CAN see this activity
    Sanctum::actingAs($this->userWali1);
    $viewRes1 = $this->getJson('/api/v1/activities');
    $viewRes1->assertOk();
    expect($viewRes1->json('data'))->toHaveCount(1);

    // Guardian 2 (anaknya di kelas 2B) CANNOT see class 2A activity
    Sanctum::actingAs($this->userWali2);
    $viewRes2 = $this->getJson('/api/v1/activities');
    $viewRes2->assertOk();
    expect($viewRes2->json('data'))->toHaveCount(0);
});

test('teacher can record individual and batch learning progress and guardian can only view their own child', function () {
    Sanctum::actingAs($this->userGuru);

    // 1. Individual progress
    $singleRes = $this->postJson('/api/v1/progress', [
        'student_id' => $this->student1->id,
        'subject_id' => $this->subject->id,
        'tanggal' => now()->toDateString(),
        'kategori' => 'tahfidz',
        'capaian' => 'Surah An-Naba ayat 1-20 lancar dan mutqin',
        'catatan' => 'Makharijul huruf sangat baik',
        'nilai' => 'Mumtaz',
    ]);

    $singleRes->assertCreated();
    expect($singleRes->json('data.capaian'))->toBe('Surah An-Naba ayat 1-20 lancar dan mutqin');

    // 2. Batch progress (e.g. sholat dhuha check)
    $batchRes = $this->postJson('/api/v1/progress/batch', [
        'class_id' => $this->class1->id,
        'tanggal' => now()->toDateString(),
        'kategori' => 'sholat',
        'items' => [
            [
                'student_id' => $this->student1->id,
                'capaian' => 'Sholat Dhuha 2 rakaat tertib',
                'nilai' => 'A',
            ],
        ],
    ]);

    $batchRes->assertCreated();
    expect($batchRes->json('data.total_tersimpan'))->toBe(1);

    // 3. FR-PRG-05: Guardian 1 can view Student 1 progress
    Sanctum::actingAs($this->userWali1);
    $waliRes = $this->getJson("/api/v1/progress?student_id={$this->student1->id}");
    $waliRes->assertOk();
    expect($waliRes->json('data'))->toHaveCount(2);

    // Summary endpoint
    $summaryRes = $this->getJson("/api/v1/progress/summary/{$this->student1->id}");
    $summaryRes->assertOk();
    expect($summaryRes->json('data.total_capaian'))->toBe(2);

    // Guardian 2 CANNOT access Student 1 progress summary (403)
    Sanctum::actingAs($this->userWali2);
    $forbiddenRes = $this->getJson("/api/v1/progress/summary/{$this->student1->id}");
    $forbiddenRes->assertStatus(403);
});

test('admin can manage announcements with target roles and users receive in-app notifications', function () {
    Sanctum::actingAs($this->admin);

    // Create announcement for Wali Murid
    $res = $this->postJson('/api/v1/announcements', [
        'judul' => 'Pemberitahuan Libur Awal Ramadhan 1448 H',
        'isi' => 'Diberitahukan kepada seluruh wali murid bahwa KBM diliburkan mulai hari Senin s.d Rabu.',
        'target_role' => 'wali',
        'kategori' => 'libur',
        'is_pinned' => true,
    ]);

    $res->assertCreated();
    $announcementId = $res->json('data.id');

    // Verify in-app notifications dispatched to wali users
    $waliNotif = InAppNotification::where('user_id', $this->userWali1->id)->where('tipe', 'pengumuman')->first();
    expect($waliNotif)->not->toBeNull();
    expect($waliNotif->judul)->toContain('Pemberitahuan Libur');

    // Wali can see it
    Sanctum::actingAs($this->userWali1);
    $viewWali = $this->getJson('/api/v1/announcements');
    $viewWali->assertOk();
    expect($viewWali->json('data'))->toHaveCount(1);

    // Guru does NOT see announcement targeted only for 'wali'
    Sanctum::actingAs($this->userGuru);
    $viewGuru = $this->getJson('/api/v1/announcements');
    $viewGuru->assertOk();
    expect($viewGuru->json('data'))->toHaveCount(0);
});

test('parent can view children list and 360-degree dashboard summary', function () {
    // Add an attendance record
    StudentAttendance::create([
        'student_id' => $this->student1->id,
        'class_id' => $this->class1->id,
        'tanggal' => now()->toDateString(),
        'status' => 'hadir',
        'dicatat_oleh' => $this->admin->id,
    ]);

    // Add a progress record
    LearningProgress::create([
        'student_id' => $this->student1->id,
        'teacher_id' => $this->teacher->id,
        'class_id' => $this->class1->id,
        'tanggal' => now()->toDateString(),
        'kategori' => 'tahfidz',
        'capaian' => 'Surah An-Naziat ayat 1-10',
        'nilai' => 'A',
    ]);

    Sanctum::actingAs($this->userWali1);

    // 1. List children
    $childrenRes = $this->getJson('/api/v1/parent/children');
    $childrenRes->assertOk();
    expect($childrenRes->json('data'))->toHaveCount(1);
    expect($childrenRes->json('data.0.nama_lengkap'))->toBe('Muhammad Bilal');

    // 2. Child summary dashboard
    $summaryRes = $this->getJson("/api/v1/parent/children/{$this->student1->id}/summary");
    $summaryRes->assertOk();
    expect($summaryRes->json('data.student.nama_lengkap'))->toBe('Muhammad Bilal');
    expect($summaryRes->json('data.attendance_summary.hadir'))->toBe(1);
    expect($summaryRes->json('data.latest_progress'))->toHaveCount(1);

    // 3. Guardian 1 cannot view summary of Student 2
    $forbiddenRes = $this->getJson("/api/v1/parent/children/{$this->student2->id}/summary");
    $forbiddenRes->assertStatus(403);
});

test('user can read and mark in-app notifications as read', function () {
    $notif = InAppNotification::send(
        userId: $this->userWali1->id,
        judul: 'Tes Notifikasi',
        isi: 'Isi tes pesan notifikasi',
        tipe: 'info'
    );

    Sanctum::actingAs($this->userWali1);

    // 1. List
    $listRes = $this->getJson('/api/v1/notifications');
    $listRes->assertOk();
    expect($listRes->json('unread_count'))->toBe(1);

    // 2. Mark as read
    $readRes = $this->postJson("/api/v1/notifications/{$notif->id}/read");
    $readRes->assertOk();
    expect($readRes->json('data.dibaca'))->toBeTrue();

    // 3. Unread count becomes 0
    $listRes2 = $this->getJson('/api/v1/notifications');
    expect($listRes2->json('unread_count'))->toBe(0);
});
