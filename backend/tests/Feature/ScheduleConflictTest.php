<?php

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Models\User;
use Database\Seeders\ReferenceTableSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReferenceTableSeeder::class);

    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $this->admin = User::create([
        'nama' => 'Admin Jadwal',
        'username' => 'adminjadwal',
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

    $this->classA = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'kode' => '1A',
        'nama' => 'Kelas 1 A',
        'tingkat' => 1,
    ]);

    $this->classB = SchoolClass::create([
        'academic_year_id' => $this->year->id,
        'kode' => '1B',
        'nama' => 'Kelas 1 B',
        'tingkat' => 1,
    ]);

    $this->subjectMath = Subject::create([
        'kode' => 'MTK',
        'nama' => 'Matematika',
        'kelompok' => 'umum',
    ]);

    $this->subjectScience = Subject::create([
        'kode' => 'IPA',
        'nama' => 'Ilmu Pengetahuan Alam',
        'kelompok' => 'umum',
    ]);

    $this->teacher1 = Teacher::create([
        'nik' => '3302011111110001',
        'nama_lengkap' => 'Guru Matematika',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '1990-01-01',
        'alamat_jalan' => 'Jl. Guru',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+628111111111',
        'jenis_ptk' => 'guru_mapel',
        'status_kepegawaian' => 'gty',
        'tgl_mulai_tugas' => '2020-01-01',
    ]);

    $this->teacher2 = Teacher::create([
        'nik' => '3302012222220002',
        'nama_lengkap' => 'Guru IPA',
        'jenis_kelamin' => 'P',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '1992-02-02',
        'alamat_jalan' => 'Jl. Guru 2',
        'kab_kota' => 'Banyumas',
        'no_hp' => '+628222222222',
        'jenis_ptk' => 'guru_mapel',
        'status_kepegawaian' => 'gty',
        'tgl_mulai_tugas' => '2021-01-01',
    ]);

    $this->roomLab = Room::create([
        'kode' => 'LAB-IPA',
        'nama' => 'Laboratorium IPA',
        'jenis' => 'lab',
    ]);

    $this->slot1 = TimeSlot::create([
        'nama' => 'Jam 1',
        'jenis' => 'belajar',
        'jam_mulai' => '07:30',
        'jam_selesai' => '08:15',
        'urutan' => 1,
        'hari' => 'senin',
    ]);
});

test('system prevents teacher schedule clash in different classes at same day and time slot', function () {
    // Schedule 1: Teacher 1 teaches Class A at Senin Jam 1
    $res1 = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/schedules', [
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classA->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacher1->id,
            'hari' => 'senin',
            'time_slot_id' => $this->slot1->id,
        ]);
    $res1->assertCreated();

    // Schedule 2: Attempting Teacher 1 to teach Class B at same Senin Jam 1 must fail
    $res2 = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/schedules', [
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classB->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacher1->id,
            'hari' => 'senin',
            'time_slot_id' => $this->slot1->id,
        ]);
    $res2->assertStatus(422)
        ->assertJsonPath('errors.teacher_id.0', fn ($val) => str_contains($val, 'sudah memiliki jadwal mengajar'));
});

test('system prevents room clash when two classes try to use same room at same time', function () {
    // Schedule 1: Class A uses LAB-IPA at Senin Jam 1
    $res1 = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/schedules', [
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classA->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacher1->id,
            'room_id' => $this->roomLab->id,
            'hari' => 'senin',
            'time_slot_id' => $this->slot1->id,
        ]);
    $res1->assertCreated();

    // Schedule 2: Class B with Teacher 2 tries to use LAB-IPA at same Senin Jam 1
    $res2 = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/schedules', [
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classB->id,
            'subject_id' => $this->subjectScience->id,
            'teacher_id' => $this->teacher2->id,
            'room_id' => $this->roomLab->id,
            'hari' => 'senin',
            'time_slot_id' => $this->slot1->id,
        ]);
    $res2->assertStatus(422)
        ->assertJsonPath('errors.room_id.0', fn ($val) => str_contains($val, 'sudah terpakai'));
});
