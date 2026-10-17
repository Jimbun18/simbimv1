<?php

use App\Models\AcademicYear;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('unauthenticated request to report card endpoint is denied with 401', function () {
    $response = $this->getJson('/api/v1/report-cards?class_id=1');
    $response->assertUnauthorized();
});

test('guru and wali are denied access to report card with 403', function () {
    $roleGuru = Role::where('nama', Role::GURU)->first();
    $guru = User::create([
        'nama' => 'Guru Test',
        'username' => 'gurutest',
        'password' => Hash::make('password123'),
        'role_id' => $roleGuru->id,
        'status' => 'aktif',
    ]);
    $tokenGuru = $guru->createToken('test')->plainTextToken;

    $responseGuru = $this->withHeader('Authorization', "Bearer {$tokenGuru}")
        ->getJson('/api/v1/report-cards?class_id=1');

    $responseGuru->assertForbidden();

    $roleWali = Role::where('nama', Role::WALI)->first();
    $wali = User::create([
        'nama' => 'Wali Test',
        'username' => 'walitest',
        'password' => Hash::make('password123'),
        'role_id' => $roleWali->id,
        'status' => 'aktif',
    ]);
    $tokenWali = $wali->createToken('test')->plainTextToken;

    $responseWali = $this->withHeader('Authorization', "Bearer {$tokenWali}")
        ->getJson('/api/v1/report-cards?class_id=1');

    $responseWali->assertForbidden();
});

test('admin can initialize and update report cards for a class', function () {
    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $admin = User::create([
        'nama' => 'Admin Rapor',
        'username' => 'adminrapor',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);
    $tokenAdmin = $admin->createToken('test')->plainTextToken;

    $ay = AcademicYear::create([
        'nama' => '2025/2026',
        'semester' => 'ganjil',
        'tgl_mulai' => '2025-07-15',
        'tgl_selesai' => '2025-12-20',
        'is_aktif' => true,
    ]);

    $class = SchoolClass::create([
        'academic_year_id' => $ay->id,
        'kode' => '1A',
        'nama' => 'Kelas 1A',
        'tingkat' => 1,
        'paralel' => 'A',
        'kapasitas' => 28,
        'status' => 'aktif',
    ]);

    $this->seed(\Database\Seeders\ReferenceTableSeeder::class);

    $student = Student::create([
        'nis' => '12345',
        'nisn' => '0012345678',
        'nama_lengkap' => 'Budi Santoso',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '2018-05-10',
        'tgl_masuk' => '2024-07-15',
        'tahun_masuk' => 2024,
        'agama' => 1,
        'alamat_jalan' => 'Jl. Pahlawan No. 10',
        'desa_kelurahan' => 'Karangklesem',
        'kecamatan' => 'Purwokerto Selatan',
        'kab_kota' => 'Banyumas',
        'provinsi' => 'Jawa Tengah',
        'class_id' => $class->id,
        'status' => 'aktif',
    ]);

    // 1. Initialize class
    $initRes = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
        ->postJson('/api/v1/report-cards/initialize-class', [
            'class_id' => $class->id,
            'academic_year_id' => $ay->id,
            'semester' => 'Gasal',
        ]);

    $initRes->assertOk()
        ->assertJsonPath('success', true);

    $rc = ReportCard::where('student_id', $student->id)->first();
    expect($rc)->not->toBeNull();
    expect($rc->grades()->count())->toBe(18);
    expect($rc->extracurriculars()->count())->toBe(5);

    // 2. Fetch index
    $indexRes = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
        ->getJson("/api/v1/report-cards?class_id={$class->id}&academic_year_id={$ay->id}&semester=Gasal");

    $indexRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_students', 1);

    // 3. Update report card
    $gradesData = $rc->grades->map(function ($g) {
        return [
            'id' => $g->id,
            'nama_mapel' => $g->nama_mapel,
            'kelompok' => $g->kelompok,
            'nilai_akhir' => 88.5,
            'capaian_kompetensi' => 'Menguasai materi dengan sangat baik.',
            'urutan' => $g->urutan,
        ];
    })->toArray();

    $updateRes = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
        ->putJson("/api/v1/report-cards/{$rc->id}", [
            'catatan_wali_kelas' => 'Terus pertahankan prestasimu yang gemilang!',
            'status' => 'final',
            'grades' => $gradesData,
        ]);

    $updateRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'final');

    $rc->refresh();
    expect($rc->status)->toBe('final');
    expect($rc->catatan_wali_kelas)->toBe('Terus pertahankan prestasimu yang gemilang!');
    expect((float)$rc->grades->first()->nilai_akhir)->toBe(88.5);
});
