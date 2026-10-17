<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Staff;
use App\Models\User;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SyncSchoolStaffCommand extends Command
{
    protected $signature = 'app:sync-school-staff';
    protected $description = 'Sinkronisasi data resmi guru dan penugasan SDI Bina Insan Mandiri';

    public function handle()
    {
        $this->info("=== MEMULAI SINKRONISASI DATA GURU & TUGAS ===");

        $activeYear = AcademicYear::where('is_aktif', true)->first() ?: AcademicYear::first();
        if (!$activeYear) {
            $this->error("Tahun ajaran aktif tidak ditemukan!");
            return 1;
        }

        $roleKepsek = Role::where('nama', 'kepala_sekolah')->firstOrFail();
        $roleAdmin = Role::where('nama', 'staf_admin')->firstOrFail();
        $roleGuru = Role::where('nama', 'guru')->firstOrFail();

        // 1. SINKRONISASI MATA PELAJARAN / TEMA
        $this->info("1. Sinkronisasi Mata Pelajaran / Tema & BK...");

        $subjectsDef = [
            [
                'kode' => 'TEMA1',
                'nama' => 'Tema 1 (Pembelajaran Tematik Terpadu)',
                'is_tematik' => true,
                'tema_nomor' => 1,
                'muatan_pelajaran' => ['PPKn', 'Bahasa Indonesia', 'Matematika', 'SBdP', 'PJOK'],
                'deskripsi' => 'Pembelajaran Tematik Kelas 1 Terpadu',
                'kelompok' => 'wajib',
                'tingkat_berlaku' => '1',
                'jam_per_minggu' => 24,
                'kkm' => 75,
                'status' => 'aktif',
            ],
            [
                'kode' => 'TEMA2',
                'nama' => 'Tema 2 (Pembelajaran Tematik Terpadu)',
                'is_tematik' => true,
                'tema_nomor' => 2,
                'muatan_pelajaran' => ['PPKn', 'Bahasa Indonesia', 'Matematika', 'SBdP', 'PJOK'],
                'deskripsi' => 'Pembelajaran Tematik Kelas 2 Terpadu',
                'kelompok' => 'wajib',
                'tingkat_berlaku' => '2',
                'jam_per_minggu' => 24,
                'kkm' => 75,
                'status' => 'aktif',
            ],
            [
                'kode' => 'TEMA3',
                'nama' => 'Tema 3 (Pembelajaran Tematik Terpadu)',
                'is_tematik' => true,
                'tema_nomor' => 3,
                'muatan_pelajaran' => ['PPKn', 'Bahasa Indonesia', 'Matematika', 'SBdP', 'PJOK'],
                'deskripsi' => 'Pembelajaran Tematik Kelas 3 Terpadu',
                'kelompok' => 'wajib',
                'tingkat_berlaku' => '3',
                'jam_per_minggu' => 24,
                'kkm' => 75,
                'status' => 'aktif',
            ],
            [
                'kode' => 'TEMA4',
                'nama' => 'Tema 4 (Pembelajaran Tematik Terpadu)',
                'is_tematik' => true,
                'tema_nomor' => 4,
                'muatan_pelajaran' => ['PPKn', 'Bahasa Indonesia', 'Matematika', 'SBdP', 'PJOK'],
                'deskripsi' => 'Pembelajaran Tematik Kelas 4 Terpadu',
                'kelompok' => 'wajib',
                'tingkat_berlaku' => '4',
                'jam_per_minggu' => 24,
                'kkm' => 75,
                'status' => 'aktif',
            ],
            [
                'kode' => 'BK',
                'nama' => 'Bimbingan Konseling (BK)',
                'is_tematik' => false,
                'tema_nomor' => null,
                'muatan_pelajaran' => null,
                'deskripsi' => 'Bimbingan dan Konseling Siswa (Pengembangan Karakter & Psikologis)',
                'kelompok' => 'muatan_lokal',
                'tingkat_berlaku' => '1,2,3,4',
                'jam_per_minggu' => 2,
                'kkm' => 80,
                'status' => 'aktif',
            ],
        ];

        $subjectMap = [];
        foreach ($subjectsDef as $sDef) {
            $subj = Subject::updateOrCreate(
                ['kode' => $sDef['kode']],
                $sDef
            );
            $subjectMap[$sDef['kode']] = $subj;
            $this->line("   -> Mapel [{$subj->kode}] {$subj->nama} tersimpan.");
        }

        // 2. DATA 8 GURU & TENDIK RESMI
        $this->info("\n2. Sinkronisasi Data 8 Pendidik & Tenaga Kependidikan...");

        $staffList = [
            [
                'key' => 'rasiti',
                'username' => 'Rasiti',
                'email' => 'rasiti@simbim.sch.id',
                'no_hp' => '081226500001',
                'role_id' => $roleKepsek->id,
                'nama_lengkap' => 'Rasiti',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.Pd.',
                'jenis_kelamin' => 'P',
                'nip' => '197505122000032001',
                'nuptk' => '1234753873200001',
                'nik' => '3302015205750001',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1975-05-12',
                'jabatan' => 'Kepala Sekolah',
                'tugas_tambahan' => 'Wali Kelas 1',
                'jenis_ptk' => 'kepala_sekolah',
                'status_kepegawaian' => 'GTY',
                'tgl_mulai_tugas' => '2015-07-01',
                'is_wali' => true,
                'wali_kelas_tingkat' => 1, // Kelas 1 Ibnu Sina
                'assignments' => [],
            ],
            [
                'key' => 'hilda',
                'username' => 'hilda',
                'email' => 'hilda@simbim.sch.id',
                'no_hp' => '081226500002',
                'role_id' => $roleGuru->id,
                'nama_lengkap' => 'Hilda Anika Ramadani',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.Pd.',
                'jenis_kelamin' => 'P',
                'nip' => '199308152019032002',
                'nuptk' => '2345753873200002',
                'nik' => '3302015508930002',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1993-08-15',
                'jabatan' => 'Guru Kelas 2 & Guru Tema 2',
                'tugas_tambahan' => 'Wali Kelas 2 + Guru Tema 2',
                'jenis_ptk' => 'guru_kelas',
                'status_kepegawaian' => 'GTY',
                'tgl_mulai_tugas' => '2019-07-01',
                'is_wali' => true,
                'wali_kelas_tingkat' => 2, // Kelas 2 Al Khawarizmi
                'assignments' => [
                    ['kode' => 'TEMA2', 'tingkat' => 2, 'jam' => 24],
                ],
            ],
            [
                'key' => 'elsa',
                'username' => 'elsa',
                'email' => 'elsa@simbim.sch.id',
                'no_hp' => '081226500003',
                'role_id' => $roleGuru->id,
                'nama_lengkap' => 'Elsa Rahmawati',
                'gelar_depan' => null,
                'gelar_belakang' => null,
                'jenis_kelamin' => 'P',
                'nip' => '199511202020122003',
                'nuptk' => '3456753873200003',
                'nik' => '3302016011950003',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1995-11-20',
                'jabatan' => 'Guru Kelas 3 & Guru Tema 3',
                'tugas_tambahan' => 'Wali Kelas 3 + Guru Tema 3',
                'jenis_ptk' => 'guru_kelas',
                'status_kepegawaian' => 'GTY',
                'tgl_mulai_tugas' => '2020-07-01',
                'is_wali' => true,
                'wali_kelas_tingkat' => 3, // Kelas 3 Al Ghazali
                'assignments' => [
                    ['kode' => 'TEMA3', 'tingkat' => 3, 'jam' => 24],
                ],
            ],
            [
                'key' => 'rusdyah',
                'username' => 'rusdyah',
                'email' => 'rusdyah@simbim.sch.id',
                'no_hp' => '081226500004',
                'role_id' => $roleGuru->id,
                'nama_lengkap' => 'Rusdyah Hasnaa Taqiyah Hastuti',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.Pd.',
                'jenis_kelamin' => 'P',
                'nip' => '199604102021042004',
                'nuptk' => '4567753873200004',
                'nik' => '3302015004960004',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1996-04-10',
                'jabatan' => 'Guru Mata Pelajaran Tema 1',
                'tugas_tambahan' => 'Guru Tema 1',
                'jenis_ptk' => 'guru_mapel',
                'status_kepegawaian' => 'GTY',
                'tgl_mulai_tugas' => '2021-07-01',
                'is_wali' => false,
                'assignments' => [
                    ['kode' => 'TEMA1', 'tingkat' => 1, 'jam' => 24],
                ],
            ],
            [
                'key' => 'abdulrosyid',
                'username' => 'abdulrosyid',
                'email' => 'abdulrosyid@simbim.sch.id',
                'no_hp' => '081226500005',
                'role_id' => $roleGuru->id,
                'nama_lengkap' => 'Abdul Rosyid Hamid',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.Pd.',
                'jenis_kelamin' => 'L',
                'nip' => '199201152018011005',
                'nuptk' => '5678753873100005',
                'nik' => '3302011501920005',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1992-01-15',
                'jabatan' => 'Guru Mata Pelajaran Tema 4',
                'tugas_tambahan' => 'Guru Tema 4',
                'jenis_ptk' => 'guru_mapel',
                'status_kepegawaian' => 'GTY',
                'tgl_mulai_tugas' => '2018-07-01',
                'is_wali' => false,
                'assignments' => [
                    ['kode' => 'TEMA4', 'tingkat' => 4, 'jam' => 24],
                ],
            ],
            [
                'key' => 'bayu',
                'username' => 'bayu',
                'email' => 'bayu@simbim.sch.id',
                'no_hp' => '081226500006',
                'role_id' => $roleAdmin->id, // Staf Tata Usaha role admin
                'nama_lengkap' => 'Bayu Caesario Nor Saputra',
                'gelar_depan' => null,
                'gelar_belakang' => null,
                'jenis_kelamin' => 'L',
                'nip' => '199407222019031006',
                'nuptk' => '6789753873100006',
                'nik' => '3302012207940006',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1994-07-22',
                'jabatan' => 'Staf Tata Usaha & Guru Tema 4',
                'tugas_tambahan' => 'Staf Tata Usaha + Guru Tema 4',
                'jenis_ptk' => 'guru_mapel',
                'status_kepegawaian' => 'GTY',
                'tgl_mulai_tugas' => '2019-07-01',
                'is_wali' => false,
                'is_staff' => true,
                'assignments' => [
                    ['kode' => 'TEMA4', 'tingkat' => 4, 'jam' => 12],
                ],
            ],
            [
                'key' => 'nurkafi',
                'username' => 'nurkafi',
                'email' => 'nurkafi@simbim.sch.id',
                'no_hp' => '081226500007',
                'role_id' => $roleGuru->id,
                'nama_lengkap' => 'Nur Kafi Ramadani',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.Psi.',
                'jenis_kelamin' => 'P',
                'nip' => '199509182020122007',
                'nuptk' => '7890753873200007',
                'nik' => '3302015809950007',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1995-09-18',
                'jabatan' => 'Guru Bimbingan dan Konseling (BK)',
                'tugas_tambahan' => 'Bimbingan Konseling (BK)',
                'jenis_ptk' => 'guru_bk',
                'status_kepegawaian' => 'GTY',
                'tgl_mulai_tugas' => '2020-07-01',
                'is_wali' => false,
                'assignments' => [
                    ['kode' => 'BK', 'tingkat' => 1, 'jam' => 2],
                    ['kode' => 'BK', 'tingkat' => 2, 'jam' => 2],
                    ['kode' => 'BK', 'tingkat' => 3, 'jam' => 2],
                    ['kode' => 'BK', 'tingkat' => 4, 'jam' => 2],
                ],
            ],
            [
                'key' => 'lulu',
                'username' => 'lulu',
                'email' => 'lulu@simbim.sch.id',
                'no_hp' => '081226500008',
                'role_id' => $roleGuru->id,
                'nama_lengkap' => 'Lulu Farihati',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.Pd.',
                'jenis_kelamin' => 'P',
                'nip' => '199103252017052008',
                'nuptk' => '8901753873200008',
                'nik' => '3302016503910008',
                'tempat_lahir' => 'Banyumas',
                'tgl_lahir' => '1991-03-25',
                'jabatan' => 'Guru Kelas 4 & Guru Tema 4',
                'tugas_tambahan' => 'Guru Tema + Wali Kelas 4',
                'jenis_ptk' => 'guru_kelas',
                'status_kepegawaian' => 'GTY',
                'tgl_mulai_tugas' => '2017-07-01',
                'is_wali' => true,
                'wali_kelas_tingkat' => 4, // Kelas 4 An Nawawi
                'assignments' => [
                    ['kode' => 'TEMA4', 'tingkat' => 4, 'jam' => 24],
                ],
            ],
        ];

        // Ambil kelas yang ada
        $classesByGrade = [];
        SchoolClass::all()->each(function ($c) use (&$classesByGrade) {
            $classesByGrade[$c->tingkat] = $c;
        });

        $createdTeacherIds = [];

        foreach ($staffList as $data) {
            $fullNameWithTitle = trim(($data['gelar_depan'] ? $data['gelar_depan'].' ' : '') . $data['nama_lengkap'] . ($data['gelar_belakang'] ? ', ' . $data['gelar_belakang'] : ''));

            // A. User Account
            $user = User::where('username', $data['username'])->first();
            if (!$user) {
                // Cari apakah ada user dengan email
                $user = User::where('email', $data['email'])->first();
            }

            if (!$user) {
                $user = User::create([
                    'nama' => $fullNameWithTitle,
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'no_hp' => $data['no_hp'],
                    'password' => Hash::make('Password123!'),
                    'role_id' => $data['role_id'],
                    'status' => 'aktif',
                    'must_change_password' => false,
                ]);
            } else {
                $user->update([
                    'nama' => $fullNameWithTitle,
                    'email' => $data['email'],
                    'no_hp' => $data['no_hp'],
                    'role_id' => $data['role_id'],
                    'status' => 'aktif',
                ]);
            }

            // B. Teacher Record
            // Cari apakah sudah ada teacher dengan nama mirip atau user_id
            $teacher = Teacher::where('user_id', $user->id)
                ->orWhere('nik', $data['nik'])
                ->orWhere('nama_lengkap', 'like', "%{$data['nama_lengkap']}%")
                ->first();

            $teacherAttributes = [
                'user_id' => $user->id,
                'nip' => $data['nip'],
                'nuptk' => $data['nuptk'],
                'nik' => $data['nik'],
                'nama_lengkap' => $fullNameWithTitle,
                'gelar_depan' => $data['gelar_depan'],
                'gelar_belakang' => $data['gelar_belakang'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tempat_lahir' => $data['tempat_lahir'],
                'tgl_lahir' => $data['tgl_lahir'],
                'alamat_jalan' => 'Jl. Riyanto No. 12, Sumampir',
                'kab_kota' => 'Banyumas',
                'provinsi' => 'Jawa Tengah',
                'no_hp' => $data['no_hp'],
                'email' => $data['email'],
                'jenis_ptk' => $data['jenis_ptk'],
                'status_kepegawaian' => $data['status_kepegawaian'],
                'tgl_mulai_tugas' => $data['tgl_mulai_tugas'],
                'jabatan' => $data['jabatan'],
                'tugas_tambahan' => $data['tugas_tambahan'],
                'status' => 'aktif',
            ];

            if ($teacher) {
                // Restore if trashed
                if ($teacher->trashed()) {
                    $teacher->restore();
                }
                $teacher->update($teacherAttributes);
            } else {
                $teacher = Teacher::create($teacherAttributes);
            }

            $createdTeacherIds[] = $teacher->id;
            $this->line("   -> Pendidik: {$fullNameWithTitle} ({$data['jabatan']}) tersimpan ID: {$teacher->id}");

            // C. Jika staf TU (Bayu) simpan juga ke staff table
            if (!empty($data['is_staff'])) {
                Staff::updateOrCreate(
                    ['nik' => $data['nik']],
                    [
                        'user_id' => $user->id,
                        'nama_lengkap' => $fullNameWithTitle,
                        'jenis_kelamin' => $data['jenis_kelamin'],
                        'tempat_lahir' => $data['tempat_lahir'],
                        'tgl_lahir' => $data['tgl_lahir'],
                        'alamat_jalan' => 'Jl. Riyanto No. 12, Sumampir',
                        'kab_kota' => 'Banyumas',
                        'no_hp' => $data['no_hp'],
                        'email' => $data['email'],
                        'jabatan' => 'Staf Tata Usaha',
                        'status_kepegawaian' => 'GTY',
                        'tgl_mulai_tugas' => $data['tgl_mulai_tugas'],
                        'status' => 'aktif',
                    ]
                );
            }

            // D. Wali Kelas
            if (!empty($data['is_wali']) && isset($data['wali_kelas_tingkat'])) {
                $classTarget = $classesByGrade[$data['wali_kelas_tingkat']] ?? null;
                if ($classTarget) {
                    $classTarget->update(['wali_kelas_id' => $teacher->id]);
                    $this->info("      [WALI KELAS] {$classTarget->nama} dialokasikan ke: {$fullNameWithTitle}");
                }
            }

            // E. Penugasan Mapel (TeacherAssignment)
            if (!empty($data['assignments'])) {
                foreach ($data['assignments'] as $assign) {
                    $sObj = $subjectMap[$assign['kode']] ?? null;
                    $cObj = $classesByGrade[$assign['tingkat']] ?? null;
                    if ($sObj && $cObj) {
                        TeacherAssignment::updateOrCreate(
                            [
                                'teacher_id' => $teacher->id,
                                'subject_id' => $sObj->id,
                                'class_id' => $cObj->id,
                                'academic_year_id' => $activeYear->id,
                            ],
                            [
                                'jam_per_minggu' => $assign['jam'] ?? 24,
                            ]
                        );
                        $this->line("      [PENUGASAN] {$fullNameWithTitle} mengajar {$sObj->kode} di {$cObj->nama}");
                    }
                }
            }
        }

        // 3. NONAKTIFKAN GURU DUMMY LAINNYA YANG TIDAK ADA DALAM DAFTAR RESMI
        $this->info("\n3. Menonaktifkan data demo/dummy yang tidak relevan...");
        Teacher::whereNotIn('id', $createdTeacherIds)->each(function ($dummyTeacher) {
            $this->warn("   -> Mengarsipkan guru demo: {$dummyTeacher->nama_lengkap} (ID: {$dummyTeacher->id})");
            $dummyTeacher->delete(); // Soft delete agar riwayat foreign key aman
        });

        $this->info("\n=== SINKRONISASI SELESAI DENGAN SUKSES! ===");
        return 0;
    }
}
