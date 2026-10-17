<?php

use App\Models\AcademicYear;
use App\Models\ImportBatch;
use App\Models\Religion;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\ReferenceTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReferenceTableSeeder::class);

    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $this->admin = User::create([
        'nama' => 'Admin Import',
        'username' => 'adminimport',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);
    $this->token = $this->admin->createToken('admin-token')->plainTextToken;

    $this->year = AcademicYear::create([
        'nama' => '2026/2027',
        'semester' => 'ganjil',
        'tgl_mulai' => '2026-07-15',
        'tgl_selesai' => '2026-12-20',
        'is_aktif' => true,
    ]);
});

test('admin can download excel template for subjects and students and indonesian aliases', function () {
    // English canonical names
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/imports/templates/students')
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=template_import_students.xlsx');

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/imports/templates/teachers')
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=template_import_teachers.xlsx');

    // Indonesian aliases from UI dropdown
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/imports/templates/guru')
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=template_import_teachers.xlsx');

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/imports/templates/siswa')
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=template_import_students.xlsx');

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/imports/templates/kelas')
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=template_import_classes.xlsx');

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/imports/templates/wali')
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=template_import_guardians.xlsx');

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->get('/api/v1/imports/templates/mata_pelajaran')
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=template_import_subjects.xlsx');
});

test('excel import engine validates file, commits records, and supports rollback', function () {
    // 1. Generate an in-memory spreadsheet for subjects
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers
    $sheet->setCellValue('A1', 'kode');
    $sheet->setCellValue('B1', 'nama');
    $sheet->setCellValue('C1', 'kelompok');
    $sheet->setCellValue('D1', 'tingkat_berlaku');
    $sheet->setCellValue('E1', 'jam_per_minggu');
    $sheet->setCellValue('F1', 'kkm');

    // Row 2: Valid
    $sheet->setCellValue('A2', 'BIND');
    $sheet->setCellValue('B2', 'Bahasa Indonesia');
    $sheet->setCellValue('C2', 'umum');
    $sheet->setCellValue('D2', '1-6');
    $sheet->setCellValue('E2', '4');
    $sheet->setCellValue('F2', '75');

    // Row 3: Valid
    $sheet->setCellValue('A3', 'BJAW');
    $sheet->setCellValue('B3', 'Bahasa Jawa');
    $sheet->setCellValue('C3', 'muatan_lokal');
    $sheet->setCellValue('D3', '1-6');
    $sheet->setCellValue('E3', '2');
    $sheet->setCellValue('F3', '70');

    // Row 4: Invalid (missing kode and invalid kelompok)
    $sheet->setCellValue('A4', '');
    $sheet->setCellValue('B4', 'Mapel Tanpa Kode');
    $sheet->setCellValue('C4', 'invalid_kelompok');

    $tempFile = tempnam(sys_get_temp_dir(), 'test_import_') . '.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    $uploadedFile = new UploadedFile($tempFile, 'mapel.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    // 2. Validate file
    $validateRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/imports/subjects/validate', [
            'file' => $uploadedFile,
        ]);

    $validateRes->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.valid_count', 2)
        ->assertJsonPath('data.error_count', 1);

    $batchId = $validateRes->json('data.batch_id');

    // 3. Commit batch
    $commitRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/imports/{$batchId}/commit", [
            'duplicate_strategy' => 'skip',
        ]);

    $commitRes->assertOk()
        ->assertJsonPath('data.inserted', 2);

    expect(Subject::where('kode', 'BIND')->exists())->toBeTrue();
    expect(Subject::where('kode', 'BJAW')->exists())->toBeTrue();

    // 4. Rollback batch
    $rollbackRes = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/imports/{$batchId}/rollback");

    $rollbackRes->assertOk()
        ->assertJsonPath('data.rolled_back_count', 2);

    // Verify records deleted after rollback
    expect(Subject::where('kode', 'BIND')->exists())->toBeFalse();
    expect(Subject::where('kode', 'BJAW')->exists())->toBeFalse();

    @unlink($tempFile);
});
