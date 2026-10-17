<?php

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Religion;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\ReferenceTableSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SchoolProfileSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReferenceTableSeeder::class);
    $this->seed(SchoolProfileSeeder::class);

    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $this->admin = User::create([
        'nama' => 'Admin Master Data',
        'username' => 'adminmaster',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);
    $this->token = $this->admin->createToken('admin-token')->plainTextToken;
});

test('admin can view and update school profile with geofence settings', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/school-profile');

    $response->assertOk()
        ->assertJsonPath('data.nama_sekolah', 'SDI Bina Insan Mandiri Purwokerto');

    $updateResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->putJson('/api/v1/school-profile', [
            'nama_sekolah' => 'SDI Bina Insan Mandiri',
            'radius_geofence_m' => 150,
            'toleransi_terlambat_menit' => 20,
        ]);

    $updateResponse->assertOk()
        ->assertJsonPath('data.radius_geofence_m', 150)
        ->assertJsonPath('data.toleransi_terlambat_menit', 20);
});

test('admin can manage academic years and toggle active year atomically', function () {
    // Create year 1
    $res1 = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/academic-years', [
            'nama' => '2026/2027',
            'semester' => 'ganjil',
            'tgl_mulai' => '2026-07-15',
            'tgl_selesai' => '2026-12-20',
            'is_aktif' => true,
        ]);
    $res1->assertCreated();
    $year1Id = $res1->json('data.id');

    // Create year 2
    $res2 = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/academic-years', [
            'nama' => '2027/2028',
            'tgl_mulai' => '2027-07-15',
            'tgl_selesai' => '2028-06-20',
            'is_aktif' => false,
        ]);
    $res2->assertCreated();
    $year2Id = $res2->json('data.id');

    // Switch active to year 2
    $toggleRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/academic-years/{$year2Id}/set-active");

    $toggleRes->assertOk();

    expect(AcademicYear::find($year1Id)->is_aktif)->toBeFalse();
    expect(AcademicYear::find($year2Id)->is_aktif)->toBeTrue();
});

test('admin can create teacher, upload document, and sensitive fields are masked for guru role', function () {
    Storage::fake('public');
    $agama = Religion::first();

    $res = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/teachers', [
            'nik' => '3302011203900001',
            'nama_lengkap' => 'Ustadz Ahmad',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Purwokerto',
            'tgl_lahir' => '1990-03-12',
            'agama' => $agama->id,
            'alamat_jalan' => 'Jl. Masjid No. 3',
            'kab_kota' => 'Banyumas',
            'no_hp' => '+6281234567001',
            'jenis_ptk' => 'guru_kelas',
            'status_kepegawaian' => 'gty',
            'tgl_mulai_tugas' => '2020-01-01',
            'npwp' => '12.345.678.9-001.000',
            'nama_bank' => 'Bank Syariah Indonesia',
            'no_rekening' => '7112233445',
            'atas_nama' => 'Ahmad',
            'create_user_account' => true,
        ]);

    $res->assertCreated();
    $teacherId = $res->json('data.id');

    // Upload document
    $docRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/teachers/{$teacherId}/documents", [
            'jenis_dokumen' => 'Ijazah S1',
            'nomor_dokumen' => '123/UN/2012',
            'file' => UploadedFile::fake()->create('ijazah.pdf', 500, 'application/pdf'),
        ]);
    $docRes->assertCreated();

    // Now test sensitive data masking when viewed by another Guru
    $roleGuru = Role::where('nama', Role::GURU)->first();
    $otherGuru = User::create([
        'nama' => 'Guru Lain',
        'username' => 'gurulain',
        'password' => Hash::make('password123'),
        'role_id' => $roleGuru->id,
        'status' => 'aktif',
    ]);
    \Laravel\Sanctum\Sanctum::actingAs($otherGuru);

    $viewRes = $this->getJson("/api/v1/teachers/{$teacherId}");

    $viewRes->assertOk();
    // NPWP and bank details should be masked/null for other guru
    expect($viewRes->json('data.npwp'))->toBeNull();
    expect($viewRes->json('data.no_rekening'))->toBeNull();
    // NIK should be partially masked
    expect($viewRes->json('data.nik'))->toContain('******');
});

test('admin can manage classes with homeroom teacher exclusivity and create students with guardian sync', function () {
    $agama = Religion::first();
    $year = AcademicYear::create([
        'nama' => '2026/2027',
        'semester' => 'ganjil',
        'tgl_mulai' => '2026-07-15',
        'tgl_selesai' => '2026-12-20',
        'is_aktif' => true,
    ]);

    $teacher = Teacher::create([
        'nik' => '3302011505880002',
        'nama_lengkap' => 'Wali Kelas 1',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '1988-05-15',
        'alamat_jalan' => 'Jl. Sekolah',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+6281234567002',
        'jenis_ptk' => 'guru_kelas',
        'status_kepegawaian' => 'gty',
        'tgl_mulai_tugas' => '2019-01-01',
    ]);

    // Create class 1A
    $classRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/classes', [
            'academic_year_id' => $year->id,
            'kode' => '1A',
            'nama' => 'Kelas 1 A',
            'tingkat' => 1,
            'paralel' => 'A',
            'wali_kelas_id' => $teacher->id,
            'kapasitas' => 28,
        ]);
    $classRes->assertCreated();
    $classId = $classRes->json('data.id');

    // Attempting to assign same teacher as homeroom to class 1B should fail
    $class2Res = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/classes', [
            'academic_year_id' => $year->id,
            'kode' => '1B',
            'nama' => 'Kelas 1 B',
            'tingkat' => 1,
            'paralel' => 'B',
            'wali_kelas_id' => $teacher->id,
        ]);
    $class2Res->assertStatus(422);

    // Create student
    $studentRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/students', [
            'nis' => '2026001',
            'nisn' => '0123456789',
            'nik' => '3302011005180001',
            'nama_lengkap' => 'Ananda Rizki',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Purwokerto',
            'tgl_lahir' => '2018-05-10',
            'agama' => $agama->id,
            'alamat_jalan' => 'Jl. Melati No. 1',
            'kab_kota' => 'Banyumas',
            'tgl_masuk' => '2026-07-15',
            'tahun_masuk' => 2026,
            'current_class_id' => $classId,
        ]);
    $studentRes->assertCreated();
    $studentId = $studentRes->json('data.id');

    // Create guardian
    $guardianRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/guardians', [
            'hubungan' => 'ayah',
            'nama_lengkap' => 'Pak Joko',
            'no_hp' => '+6281234567099',
        ]);
    $guardianRes->assertCreated();
    $guardianId = $guardianRes->json('data.id');

    // Sync guardian with primary contact
    $syncRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/students/{$studentId}/guardians", [
            'guardians' => [
                [
                    'guardian_id' => $guardianId,
                    'hubungan' => 'ayah',
                    'is_kontak_utama' => true,
                ],
            ],
        ]);
    $syncRes->assertOk();

    // Mutate student status
    $statusRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/students/{$studentId}/status", [
            'status_baru' => 'mutasi_keluar',
            'tanggal' => '2026-10-01',
            'alasan' => 'Pindah ke kota lain mengikuti orang tua',
            'nomor_surat' => '421/001/X/2026',
        ]);
    $statusRes->assertOk()
        ->assertJsonPath('data.status', 'mutasi_keluar');
});

test('admin can upload student and teacher photo successfully', function () {
    Storage::fake('public');

    $agama = Religion::first();
    $fakePhoto = UploadedFile::fake()->image('pasfoto.jpg', 400, 400);

    // 1. Create student with photo
    $resStudent = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->post('/api/v1/students', [
            'nis' => '2026099',
            'nama_lengkap' => 'Siswa Berfoto',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Purwokerto',
            'tgl_lahir' => '2018-01-01',
            'agama' => $agama->id,
            'alamat_jalan' => 'Jl. Pendidikan No. 5',
            'kab_kota' => 'Banyumas',
            'tgl_masuk' => '2026-07-01',
            'tahun_masuk' => 2026,
            'foto' => $fakePhoto,
        ]);

    $resStudent->assertCreated();
    expect($resStudent->json('data.foto_path'))->not->toBeNull();
    Storage::disk('public')->assertExists($resStudent->json('data.foto_path'));

    // 2. Create teacher with photo
    $fakeTeacherPhoto = UploadedFile::fake()->image('fotoguru.jpg', 400, 400);
    $resTeacher = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->post('/api/v1/teachers', [
            'nik' => '3302010101900009',
            'nama_lengkap' => 'Ustadzah Sarah, S.Pd.',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Purwokerto',
            'tgl_lahir' => '1992-05-15',
            'alamat_jalan' => 'Jl. Guru No. 10',
            'kab_kota' => 'Banyumas',
            'no_hp' => '081234567890',
            'jenis_ptk' => 'guru_kelas',
            'status_kepegawaian' => 'gty',
            'tgl_mulai_tugas' => '2020-01-01',
            'foto' => $fakeTeacherPhoto,
        ]);

    $resTeacher->assertCreated();
    expect($resTeacher->json('data.foto_path'))->not->toBeNull();
    Storage::disk('public')->assertExists($resTeacher->json('data.foto_path'));
});

test('admin can export master data teachers, students, and classes to excel', function () {
    $resTeachers = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/teachers/export');
    $resTeachers->assertOk();
    $resTeachers->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $resStudents = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/students/export');
    $resStudents->assertOk();
    $resStudents->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $resClasses = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/classes/export');
    $resClasses->assertOk();
    $resClasses->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('admin can manage thematic and regular subjects CRUD', function () {
    // 1. Create Tematik subject (e.g. Tema 1: Matematika & IPA)
    $resTema = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/subjects', [
            'kode' => 'TEMA-1',
            'nama' => 'Tema 1: Indahnya Kebersamaan',
            'is_tematik' => true,
            'tema_nomor' => 1,
            'muatan_pelajaran' => ['Matematika', 'IPA'],
            'deskripsi' => 'Integrasi mata pelajaran Matematika dan IPA untuk kelas 4',
            'kelompok' => 'tematik',
            'tingkat_berlaku' => 'Kelas 4',
            'jam_per_minggu' => 6,
            'kkm' => 75,
            'status' => 'aktif',
        ]);

    $resTema->assertCreated()
        ->assertJsonPath('data.is_tematik', true)
        ->assertJsonPath('data.tema_nomor', 1)
        ->assertJsonPath('data.muatan_pelajaran', ['Matematika', 'IPA'])
        ->assertJsonPath('data.display_name', 'Tema 1: Indahnya Kebersamaan (Matematika, IPA)');

    $subjectId = $resTema->json('data.id');

    // 2. Update Tematik subject
    $resUpdate = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->putJson("/api/v1/subjects/{$subjectId}", [
            'nama' => 'Tema 1: Indahnya Keberagaman',
            'muatan_pelajaran' => ['Matematika', 'IPA', 'PPKn'],
        ]);

    $resUpdate->assertOk()
        ->assertJsonPath('data.nama', 'Tema 1: Indahnya Keberagaman')
        ->assertJsonPath('data.display_name', 'Tema 1: Indahnya Keberagaman (Matematika, IPA, PPKn)');

    // 3. List with is_tematik filter
    $resList = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/subjects?is_tematik=1');

    $resList->assertOk();
    expect(count($resList->json('data')))->toBeGreaterThanOrEqual(1);

    // 4. Update Tematik subject with titlecase status and kelompok
    $resUpdate2 = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->putJson("/api/v1/subjects/{$subjectId}", [
            'nama' => 'Tema 5 (Pembelajaran Tematik Terpadu)',
            'is_tematik' => true,
            'tema_nomor' => 5,
            'muatan_pelajaran' => ['Sejarah Kebudayaan Islam', "Qur'an Hadis"],
            'jam_per_minggu' => 24,
            'kkm' => 75,
            'status' => 'Aktif',
            'kelompok' => 'Tematik',
        ]);

    $resUpdate2->assertOk()
        ->assertJsonPath('data.status', 'aktif')
        ->assertJsonPath('data.kelompok', 'tematik')
        ->assertJsonPath('data.muatan_pelajaran', ['Sejarah Kebudayaan Islam', "Qur'an Hadis"]);

    // 5. Delete subject
    $resDelete = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson("/api/v1/subjects/{$subjectId}");

    $resDelete->assertOk();
    expect(Subject::find($subjectId))->toBeNull();
});


