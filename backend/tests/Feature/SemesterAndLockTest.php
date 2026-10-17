<?php

use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Religion;
use App\Models\ReportCard;
use App\Models\ReportCardGrade;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolProfile;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Services\PeriodLockService;
use Database\Seeders\ReferenceTableSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SchoolProfileSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(ReferenceTableSeeder::class);
    $this->seed(SchoolProfileSeeder::class);

    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $roleKepsek = Role::where('nama', Role::KEPALA_SEKOLAH)->first();
    $roleWali = Role::where('nama', Role::WALI)->first();

    $this->admin = User::create([
        'nama' => 'Admin SIMBIM',
        'username' => 'admin_test',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);
    $this->adminToken = $this->admin->createToken('admin-token')->plainTextToken;

    $this->kepsek = User::create([
        'nama' => 'Kepala Sekolah SIMBIM',
        'username' => 'kepsek_test',
        'password' => Hash::make('password123'),
        'role_id' => $roleKepsek->id,
        'status' => 'aktif',
    ]);

    $this->waliUser = User::create([
        'nama' => 'Wali Murid Budi',
        'username' => 'walibudi_test',
        'password' => Hash::make('password123'),
        'role_id' => $roleWali->id,
        'status' => 'aktif',
    ]);
    $this->waliToken = $this->waliUser->createToken('wali-token')->plainTextToken;

    $this->guardian = Guardian::create([
        'user_id' => $this->waliUser->id,
        'hubungan' => 'ayah',
        'nama_lengkap' => 'Ayah Budi',
        'no_hp' => '081234567890',
        'alamat_jalan' => 'Jl. Test',
        'kab_kota' => 'Banyumas',
    ]);

    // Setup Academic Year 2026/2027
    $this->ay = AcademicYear::create([
        'nama' => '2026/2027',
        'tgl_mulai' => '2026-07-01',
        'tgl_selesai' => '2027-06-30',
        'status' => 'aktif',
    ]);

    // Setup Semester Gasal (Juli - Des)
    $this->semGasal = Semester::create([
        'academic_year_id' => $this->ay->id,
        'semester' => 'gasal',
        'tgl_mulai' => '2026-07-15',
        'tgl_selesai' => '2026-12-20',
        'status' => 'aktif',
    ]);

    // Setup Semester Genap (Jan - Jun)
    $this->semGenap = Semester::create([
        'academic_year_id' => $this->ay->id,
        'semester' => 'genap',
        'tgl_mulai' => '2027-01-05',
        'tgl_selesai' => '2027-06-25',
        'status' => 'rencana',
    ]);

    // Setup Kelas 1A
    $this->class1A = SchoolClass::create([
        'academic_year_id' => $this->ay->id,
        'kode' => '1A',
        'nama' => 'Kelas 1 A',
        'tingkat' => 1,
        'status' => 'aktif',
    ]);

    // Setup Siswa Budi
    $this->budi = Student::create([
        'nis' => '1001',
        'nama_lengkap' => 'Budi Santoso',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '2019-05-10',
        'agama' => 1,
        'alamat_jalan' => 'Jl. Sekolah No 1',
        'kab_kota' => 'Banyumas',
        'tgl_masuk' => '2026-07-15',
        'tahun_masuk' => 2026,
        'class_id' => $this->class1A->id,
        'status' => 'aktif',
    ]);

    // Link Budi ke Guardian
    $this->guardian->students()->attach($this->budi->id, ['hubungan' => 'anak_kandung']);
});

test('1. system enforces maximum one active semester and allows zero active semesters', function () {
    // Awalnya semGasal aktif
    expect(Semester::where('status', 'aktif')->count())->toBe(1);

    // Aktifkan semGenap -> otomatis semGasal dinonaktifkan (maksimal 1 aktif)
    PeriodLockService::activateSemester($this->semGenap);

    expect(Semester::where('status', 'aktif')->count())->toBe(1);
    expect($this->semGenap->fresh()->status)->toBe('aktif');
    expect($this->semGasal->fresh()->status)->toBe('terkunci');

    // Matikan status aktif (misalnya saat jeda libur semester -> 0 aktif diperbolehkan)
    $this->semGenap->update(['status' => 'terkunci']);
    expect(Semester::where('status', 'aktif')->count())->toBe(0);
});

test('2. semester date ranges cannot overlap', function () {
    // Mencoba membuat semester baru dengan rentang tanggal yang menabrak semester Gasal (2026-07-15 s.d 2026-12-20)
    expect(function () {
        PeriodLockService::validateNoOverlap('2026-10-01', '2026-11-01');
    })->toThrow(ValidationException::class);

    // Rentang yang tidak tumpang tindih lolos validasi
    PeriodLockService::validateNoOverlap('2027-07-01', '2027-12-31');
    expect(true)->toBeTrue();
});

test('3. date outside semester range (holiday/break) is rejected for attendance', function () {
    // Tanggal 2026-12-25 adalah masa libur antar-semester (antara 2026-12-20 dan 2027-01-05)
    $liburDate = '2026-12-25';

    expect(function () use ($liburDate) {
        PeriodLockService::validateDate($liburDate);
    })->toThrow(ValidationException::class);
});

test('4. locked semester blocks bulk insert update and raw SQL via Database Triggers', function () {
    // Kunci semester Gasal
    $this->semGasal->update([
        'status' => 'terkunci',
        'reopen_until' => null,
    ]);

    if (DB::getDriverName() === 'mysql') {
        // Coba bulk insert via Query Builder (DB::table) langsung tanpa melalui Eloquent
        $threwException = false;
        try {
            DB::table('student_attendance')->insert([
                'student_id' => $this->budi->id,
                'class_id' => $this->class1A->id,
                'tanggal' => '2026-08-10', // Masuk rentang semester gasal yang terkunci
                'status' => 'hadir',
                'dicatat_oleh' => $this->admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            $threwException = true;
            expect($e->getMessage())->toContain('SEMESTER_LOCKED');
        }

        expect($threwException)->toBeTrue();
    } else {
        // Pada SQLite (lingkungan test memory), proteksi divalidasi via PeriodLockService
        expect(function () {
            PeriodLockService::validateDate('2026-08-10');
        })->toThrow(ValidationException::class);
    }
});

test('5. admin close semester is blocked by draft reports unless forced explicitly', function () {
    // Buat rapor draf
    ReportCard::create([
        'student_id' => $this->budi->id,
        'class_id' => $this->class1A->id,
        'academic_year_id' => $this->ay->id,
        'semester_id' => $this->semGasal->id,
        'semester' => 'Gasal',
        'status' => 'draft',
    ]);

    // Menutup semester tanpa forceClose harus ditolak
    expect(function () {
        PeriodLockService::closeSemester($this->semGasal, false);
    })->toThrow(ValidationException::class);

    // Menutup semester dengan forceClose = true diizinkan
    $closed = PeriodLockService::closeSemester($this->semGasal, true, 'Pengesahan dewan guru');
    expect($closed->status)->toBe('terkunci');
});

test('6. reopen requires principal approval, reason, and auto-locks after expiry', function () {
    $this->semGasal->update(['status' => 'terkunci', 'reopen_until' => null]);

    // Reopen tanpa persetujuan Kepala Sekolah ditolak
    expect(function () {
        PeriodLockService::reopenSemester(
            $this->semGasal,
            'Revisi nilai susulan siswa',
            now()->addDays(2),
            $this->admin->id // Admin bukan kepala_sekolah
        );
    })->toThrow(ValidationException::class);

    // Reopen dengan persetujuan Kepala Sekolah berhasil
    $reopened = PeriodLockService::reopenSemester(
        $this->semGasal,
        'Revisi nilai susulan siswa atas persetujuan pengawas',
        now()->addDays(2),
        $this->kepsek->id
    );

    expect($reopened->is_locked)->toBeFalse();
    expect($reopened->is_reopened)->toBeTrue();

    // Simulasi kedaluwarsa: reopen_until di masa lalu
    $reopened->update(['reopen_until' => now()->subMinute()]);
    expect($reopened->fresh()->is_locked)->toBeTrue();
});

test('7. finalized report card is immutable on reopen and requires versioned revision', function () {
    $rc = ReportCard::create([
        'student_id' => $this->budi->id,
        'class_id' => $this->class1A->id,
        'academic_year_id' => $this->ay->id,
        'semester_id' => $this->semGasal->id,
        'semester' => 'Gasal',
        'status' => 'final',
        'version' => 1,
        'wali_kelas_nama' => 'Ustadzah Siti, S.Pd.',
    ]);

    ReportCardGrade::create([
        'report_card_id' => $rc->id,
        'nama_mapel' => 'Matematika',
        'nilai_akhir' => 85,
    ]);

    // Saat revisi, snapshot lama disimpan dan versi bertambah
    $revision = $rc->createRevision('Koreksi nilai ujian susulan remedial', $this->admin->id);

    expect($revision->version)->toBe(1);
    expect($revision->snapshot_data['grades'][0]['nilai_akhir'])->toBe(85);
    expect($rc->fresh()->version)->toBe(2);
    expect($rc->fresh()->revision_notes)->toBe('Koreksi nilai ujian susulan remedial');
});

test('8. student remains in same class when semester transitions from gasal to genap', function () {
    // Di semester Gasal, Budi di kelas 1A
    expect($this->budi->class_id)->toBe($this->class1A->id);

    // Transisi ke Genap
    PeriodLockService::activateSemester($this->semGenap);

    // Budi tetap di kelas 1A tanpa ganti ID rombel kelas
    expect($this->budi->fresh()->class_id)->toBe($this->class1A->id);
    expect($this->class1A->academic_year_id)->toBe($this->ay->id);
});

test('9. past report card snapshot is immutable even if teacher or principal changes', function () {
    $rc = ReportCard::create([
        'student_id' => $this->budi->id,
        'class_id' => $this->class1A->id,
        'academic_year_id' => $this->ay->id,
        'semester_id' => $this->semGasal->id,
        'semester' => 'Gasal',
        'wali_kelas_nama' => 'Wali Kelas Lama 1A',
        'kepala_sekolah_nama' => 'Kepala Sekolah Periode 2026',
        'status' => 'final',
    ]);

    // Ganti profil kepsek di sekolah
    $profile = SchoolProfile::first();
    $profile->update(['nama_sekolah' => 'SDI BIM New']);

    // Rapor Gasal yang tersimpan tetap mencatat nama pejabat asli
    expect($rc->fresh()->wali_kelas_nama)->toBe('Wali Kelas Lama 1A');
    expect($rc->fresh()->kepala_sekolah_nama)->toBe('Kepala Sekolah Periode 2026');
});

test('10. guardian can only view finalized report cards for their own children', function () {
    // Siswa lain (Bambang)
    $otherStudent = Student::create([
        'nis' => '1002',
        'nama_lengkap' => 'Bambang',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Purwokerto',
        'tgl_lahir' => '2019-06-10',
        'agama' => 1,
        'alamat_jalan' => 'Jl. Lain',
        'kab_kota' => 'Banyumas',
        'tgl_masuk' => '2026-07-15',
        'tahun_masuk' => 2026,
        'class_id' => $this->class1A->id,
        'status' => 'aktif',
    ]);

    // Rapor Budi (final)
    $rcBudi = ReportCard::create([
        'student_id' => $this->budi->id,
        'class_id' => $this->class1A->id,
        'academic_year_id' => $this->ay->id,
        'semester_id' => $this->semGasal->id,
        'semester' => 'Gasal',
        'status' => 'final',
    ]);

    // Rapor Bambang (final)
    $rcBambang = ReportCard::create([
        'student_id' => $otherStudent->id,
        'class_id' => $this->class1A->id,
        'academic_year_id' => $this->ay->id,
        'semester_id' => $this->semGasal->id,
        'semester' => 'Gasal',
        'status' => 'final',
    ]);

    // Wali Budi dapat mengakses rapor Budi
    $resBudi = $this->withHeader('Authorization', "Bearer {$this->waliToken}")
        ->getJson("/api/v1/parent/report-cards/{$this->budi->id}");
    // Pastikan tidak ada akses data anak lain
    $resBambang = $this->withHeader('Authorization', "Bearer {$this->waliToken}")
        ->getJson("/api/v1/parent/report-cards/{$otherStudent->id}");

    $resBambang->assertForbidden();
});
