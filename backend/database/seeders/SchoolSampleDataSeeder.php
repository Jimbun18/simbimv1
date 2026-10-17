<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\AssetMaintenance;
use App\Models\FeeType;
use App\Models\FinanceTransaction;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Religion;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeachingEvidence;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::where('is_aktif', true)->first();
        if (!$year) return;

        $roleGuru = Role::where('nama', Role::GURU)->first();
        $roleWali = Role::where('nama', Role::WALI)->first();
        $adminUser = User::where('username', 'admin')->first();
        $religionIslam = Religion::firstOrCreate(['nama' => 'Islam']);

        // 1. RUANGAN
        $room1A = Room::firstOrCreate(['kode' => 'R101'], ['nama' => 'Ruang 1 Abu Bakar', 'jenis' => 'kelas', 'kapasitas' => 28, 'gedung_lantai' => 'Gedung A Lantai 1', 'kondisi' => 'baik']);
        $room2A = Room::firstOrCreate(['kode' => 'R102'], ['nama' => 'Ruang 2 Umar bin Khattab', 'jenis' => 'kelas', 'kapasitas' => 28, 'gedung_lantai' => 'Gedung A Lantai 1', 'kondisi' => 'baik']);
        $room3A = Room::firstOrCreate(['kode' => 'R201'], ['nama' => 'Ruang 3 Utsman bin Affan', 'jenis' => 'kelas', 'kapasitas' => 28, 'gedung_lantai' => 'Gedung A Lantai 2', 'kondisi' => 'baik']);
        $roomLab = Room::firstOrCreate(['kode' => 'LAB01'], ['nama' => 'Laboratorium Komputer', 'jenis' => 'lab', 'kapasitas' => 30, 'gedung_lantai' => 'Gedung B Lantai 1', 'kondisi' => 'baik']);
        $roomPerpus = Room::firstOrCreate(['kode' => 'PUS01'], ['nama' => 'Perpustakaan Khadijah', 'jenis' => 'perpustakaan', 'kapasitas' => 40, 'gedung_lantai' => 'Gedung B Lantai 2', 'kondisi' => 'baik']);

        // 2. GURU-GURU TAMBAHAN
        $guruData = [
            [
                'username' => 'fatimah',
                'nama' => 'Ustadzah Fatimah Azzahra, S.Pd.I',
                'email' => 'fatimah@simbim.sch.id',
                'nip' => '199001012015012001',
                'nik' => '3302015001900001',
                'jenis_ptk' => 'guru_agama',
                'mapel_utama' => 'Tahfidz Al-Qur\'an',
            ],
            [
                'username' => 'salman',
                'nama' => 'Ustadz Salman Al-Farisi, S.Pd.',
                'email' => 'salman@simbim.sch.id',
                'nip' => '199203102016021002',
                'nik' => '3302011003920002',
                'jenis_ptk' => 'guru_mapel',
                'mapel_utama' => 'Matematika',
            ],
            [
                'username' => 'aisyah',
                'nama' => 'Ustadzah Aisyah Humaira, S.Hum.',
                'email' => 'aisyah@simbim.sch.id',
                'nip' => '199407222018032003',
                'nik' => '3302016207940003',
                'jenis_ptk' => 'guru_mapel',
                'mapel_utama' => 'Bahasa Arab',
            ],
        ];

        $teachers = [];
        $existingGuru = Teacher::first();
        if ($existingGuru) {
            $teachers[] = $existingGuru;
        }

        foreach ($guruData as $g) {
            $u = User::firstOrCreate(
                ['username' => $g['username']],
                [
                    'nama' => $g['nama'],
                    'email' => $g['email'],
                    'no_hp' => '+62812' . rand(10000000, 99999999),
                    'password' => Hash::make('Password123!'),
                    'role_id' => $roleGuru->id,
                    'status' => 'aktif',
                    'must_change_password' => false,
                ]
            );

            $t = Teacher::firstOrCreate(
                ['user_id' => $u->id],
                [
                    'nip' => $g['nip'],
                    'nik' => $g['nik'],
                    'nama_lengkap' => $g['nama'],
                    'jenis_kelamin' => str_contains($g['nama'], 'Ustadzah') ? 'P' : 'L',
                    'tempat_lahir' => 'Purwokerto',
                    'tgl_lahir' => '1992-05-15',
                    'alamat_jalan' => 'Jl. Sunan Kalijaga No. ' . rand(1, 50),
                    'kab_kota' => 'Banyumas',
                    'provinsi' => 'Jawa Tengah',
                    'no_hp' => $u->no_hp,
                    'email' => $u->email,
                    'jenis_ptk' => $g['jenis_ptk'],
                    'status_kepegawaian' => 'gty',
                    'tgl_mulai_tugas' => '2020-07-15',
                    'status' => 'aktif',
                ]
            );
            $teachers[] = $t;
        }

        // 3. MATA PELAJARAN
        $subjects = [
            ['kode' => 'TAHFIDZ', 'nama' => 'Tahfidz & Tahsin Al-Qur\'an', 'kelompok' => 'Agama', 'kkm' => 80],
            ['kode' => 'PAI', 'nama' => 'Pendidikan Agama Islam', 'kelompok' => 'Agama', 'kkm' => 78],
            ['kode' => 'BIND', 'nama' => 'Bahasa Indonesia', 'kelompok' => 'Umum', 'kkm' => 75],
            ['kode' => 'MTK', 'nama' => 'Matematika', 'kelompok' => 'Umum', 'kkm' => 75],
            ['kode' => 'BARAB', 'nama' => 'Bahasa Arab', 'kelompok' => 'Muatan lokal', 'kkm' => 75],
            ['kode' => 'IPAS', 'nama' => 'Ilmu Pengetahuan Alam dan Sosial', 'kelompok' => 'Umum', 'kkm' => 75],
        ];

        $subjectModels = [];
        foreach ($subjects as $s) {
            $subjectModels[] = Subject::firstOrCreate(
                ['kode' => $s['kode']],
                [
                    'nama' => $s['nama'],
                    'kelompok' => $s['kelompok'],
                    'tingkat_berlaku' => '1-6',
                    'jam_per_minggu' => 4,
                    'kkm' => $s['kkm'],
                    'status' => 'Aktif',
                ]
            );
        }

        // 4. KELAS / ROMBEL
        $classes = [
            ['kode' => '1A', 'nama' => 'Kelas 1 Abu Bakar', 'tingkat' => 1, 'wali' => $teachers[0] ?? null, 'room' => $room1A],
            ['kode' => '2A', 'nama' => 'Kelas 2 Umar bin Khattab', 'tingkat' => 2, 'wali' => $teachers[1] ?? null, 'room' => $room2A],
            ['kode' => '3A', 'nama' => 'Kelas 3 Utsman bin Affan', 'tingkat' => 3, 'wali' => $teachers[2] ?? null, 'room' => $room3A],
        ];

        $classModels = [];
        foreach ($classes as $c) {
            $classModels[] = SchoolClass::firstOrCreate(
                ['academic_year_id' => $year->id, 'kode' => $c['kode']],
                [
                    'nama' => $c['nama'],
                    'tingkat' => $c['tingkat'],
                    'paralel' => 'A',
                    'wali_kelas_id' => $c['wali']?->id,
                    'room_id' => $c['room']->id,
                    'kapasitas' => 28,
                    'status' => 'Aktif',
                ]
            );
        }

        // 5. SISWA & WALI MURID
        $existingWali = Guardian::first();
        $sampleStudents = [
            ['nis' => '260101', 'nisn' => '0012345601', 'nama' => 'Muhammad Bilal Al-Farisi', 'jk' => 'L', 'kelas_idx' => 0],
            ['nis' => '260102', 'nisn' => '0012345602', 'nama' => 'Aisyah Putri Azzahra', 'jk' => 'P', 'kelas_idx' => 0],
            ['nis' => '260103', 'nisn' => '0012345603', 'nama' => 'Fatih Rayyan Al-Ghifari', 'jk' => 'L', 'kelas_idx' => 0],
            ['nis' => '260104', 'nisn' => '0012345604', 'nama' => 'Khadijah Maryam', 'jk' => 'P', 'kelas_idx' => 0],

            ['nis' => '250201', 'nisn' => '0012345501', 'nama' => 'Ibrahim Khalilurrahman', 'jk' => 'L', 'kelas_idx' => 1],
            ['nis' => '250202', 'nisn' => '0012345502', 'nama' => 'Nayla Husna Zahira', 'jk' => 'P', 'kelas_idx' => 1],
            ['nis' => '250203', 'nisn' => '0012345503', 'nama' => 'Zaidan Akmal Pratama', 'jk' => 'L', 'kelas_idx' => 1],
            ['nis' => '250204', 'nisn' => '0012345504', 'nama' => 'Salma Aqila Jasmine', 'jk' => 'P', 'kelas_idx' => 1],

            ['nis' => '240301', 'nisn' => '0012345401', 'nama' => 'Ahmad Fauzan Rabbani', 'jk' => 'L', 'kelas_idx' => 2],
            ['nis' => '240302', 'nisn' => '0012345402', 'nama' => 'Zahra Syarifah', 'jk' => 'P', 'kelas_idx' => 2],
            ['nis' => '240303', 'nisn' => '0012345403', 'nama' => 'Hafizh Al-Habsyi', 'jk' => 'L', 'kelas_idx' => 2],
            ['nis' => '240304', 'nisn' => '0012345404', 'nama' => 'Fatima Al-Zahra', 'jk' => 'P', 'kelas_idx' => 2],
        ];

        $studentModels = [];
        foreach ($sampleStudents as $idx => $s) {
            $student = Student::firstOrCreate(
                ['nis' => $s['nis']],
                [
                    'nisn' => $s['nisn'],
                    'nik' => '3302011005' . str_pad((string)($idx + 10), 6, '0', STR_PAD_LEFT),
                    'nama_lengkap' => $s['nama'],
                    'jenis_kelamin' => $s['jk'],
                    'tempat_lahir' => 'Banyumas',
                    'tgl_lahir' => '2018-05-12',
                    'agama' => $religionIslam->id,
                    'alamat_jalan' => 'Jl. Dr. Angka No. ' . ($idx + 1),
                    'desa_kelurahan' => 'Bancarkembar',
                    'kecamatan' => 'Purwokerto Utara',
                    'kab_kota' => 'Banyumas',
                    'provinsi' => 'Jawa Tengah',
                    'status' => 'aktif',
                    'tgl_masuk' => '2026-07-15',
                    'tahun_masuk' => 2026,
                    'class_id' => $classModels[$s['kelas_idx']]->id,
                ]
            );

            // Hubungkan ke wali murid (Pak Hendra hanya memiliki 2 orang anak: Bilal & Nayla)
            if ($existingWali && in_array($idx, [0, 5], true)) {
                $student->guardians()->syncWithoutDetaching([
                    $existingWali->id => [
                        'hubungan' => 'orang_tua',
                        'is_kontak_utama' => true,
                        'boleh_lihat_keuangan' => true,
                        'boleh_jemput' => true,
                    ]
                ]);
            }

            StudentEnrollment::firstOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $year->id],
                [
                    'class_id' => $classModels[$s['kelas_idx']]->id,
                    'status_kelas' => 'aktif',
                    'tgl_masuk_kelas' => '2026-07-15',
                ]
            );

            $studentModels[] = $student;
        }

        // 6. JAM PELAJARAN & JADWAL KBM HARI INI
        $slots = [
            ['nama' => 'Jam Ke-1', 'jenis' => 'belajar', 'mulai' => '07:15:00', 'selesai' => '08:00:00', 'urutan' => 1],
            ['nama' => 'Jam Ke-2', 'jenis' => 'belajar', 'mulai' => '08:00:00', 'selesai' => '08:45:00', 'urutan' => 2],
            ['nama' => 'Sholat Dhuha & Istirahat', 'jenis' => 'istirahat', 'mulai' => '08:45:00', 'selesai' => '09:15:00', 'urutan' => 3],
            ['nama' => 'Jam Ke-3', 'jenis' => 'belajar', 'mulai' => '09:15:00', 'selesai' => '10:00:00', 'urutan' => 4],
            ['nama' => 'Jam Ke-4', 'jenis' => 'belajar', 'mulai' => '10:00:00', 'selesai' => '10:45:00', 'urutan' => 5],
        ];

        $timeSlotModels = [];
        $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        foreach ($dayNames as $day) {
            $lowerDay = strtolower($day);
            foreach ($slots as $sl) {
                $timeSlotModels[$day][] = TimeSlot::firstOrCreate(
                    ['nama' => $sl['nama'], 'hari' => $lowerDay],
                    [
                        'jenis' => $sl['jenis'],
                        'jam_mulai' => $sl['mulai'],
                        'jam_selesai' => $sl['selesai'],
                        'urutan' => $sl['urutan'],
                    ]
                );
            }
        }

        // Jadwal Hari Ini
        $dayOfWeekMap = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $todayDay = $dayOfWeekMap[now()->dayOfWeek] ?? 'Kamis';
        $todayDayLower = strtolower($todayDay);

        $schedulesToday = [];
        if (isset($timeSlotModels[$todayDay])) {
            foreach ($classModels as $cIdx => $class) {
                foreach ([0, 1] as $sIdx) {
                    if (isset($timeSlotModels[$todayDay][$sIdx]) && isset($subjectModels[$sIdx])) {
                        $schedulesToday[] = Schedule::firstOrCreate(
                            [
                                'academic_year_id' => $year->id,
                                'class_id' => $class->id,
                                'hari' => $todayDayLower,
                                'time_slot_id' => $timeSlotModels[$todayDay][$sIdx]->id,
                            ],
                            [
                                'subject_id' => $subjectModels[$sIdx]->id,
                                'teacher_id' => $teachers[$cIdx % count($teachers)]->id,
                                'room_id' => $class->room_id,
                                'status' => 'aktif',
                            ]
                        );
                    }
                }
            }
        }

        // 7. PRESENSI HARI INI
        $today = now()->toDateString();
        // Presensi Guru
        foreach ($teachers as $idx => $t) {
            TeacherAttendance::firstOrCreate(
                ['teacher_id' => $t->id, 'tanggal' => $today],
                [
                    'jam_masuk' => $idx === 0 ? '06:50:00' : ($idx === 1 ? '06:55:00' : '07:10:00'),
                    'status' => 'hadir',
                    'is_terlambat' => $idx === 2,
                    'menit_keterlambatan' => $idx === 2 ? 10 : 0,
                    'lat' => -7.424300,
                    'lng' => 109.230100,
                    'jarak_masuk_m' => 12,
                ]
            );
        }

        // Bukti Mengajar KBM Hari Ini
        if (count($schedulesToday) > 0) {
            TeachingEvidence::firstOrCreate(
                ['schedule_id' => $schedulesToday[0]->id, 'tanggal' => $today],
                [
                    'teacher_id' => $schedulesToday[0]->teacher_id,
                    'photo_path' => 'evidence/sample_kbm_1.jpg',
                    'captured_at' => now(),
                    'lat' => -7.424300,
                    'lng' => 109.230100,
                ]
            );
        }

        // Presensi Murid Hari Ini
        foreach ($studentModels as $idx => $st) {
            $statusAbsen = $idx === 2 ? 'sakit' : ($idx === 5 ? 'izin' : 'hadir');
            StudentAttendance::firstOrCreate(
                ['student_id' => $st->id, 'tanggal' => $today],
                [
                    'class_id' => $st->class_id,
                    'status' => $statusAbsen,
                    'dicatat_oleh' => $adminUser?->id ?? 1,
                ]
            );
        }

        // 8. KEUANGAN (SPP & KAS)
        $sppFee = FeeType::where('kode', 'SPP')->first();
        if ($sppFee) {
            $currentMonth = now()->format('Y-m');
            foreach ($studentModels as $idx => $st) {
                $isPaid = $idx % 2 === 0;
                $inv = Invoice::firstOrCreate(
                    [
                        'student_id' => $st->id,
                        'fee_type_id' => $sppFee->id,
                        'periode' => $currentMonth,
                    ],
                    [
                        'no_tagihan' => 'INV/' . now()->format('Y/m') . '/' . str_pad((string)($idx + 1), 4, '0', STR_PAD_LEFT),
                        'academic_year_id' => $year->id,
                        'jumlah' => $sppFee->nominal,
                        'jumlah_dibayar' => $isPaid ? $sppFee->nominal : 0,
                        'sisa' => $isPaid ? 0 : $sppFee->nominal,
                        'status' => $isPaid ? 'lunas' : 'belum_bayar',
                        'jatuh_tempo' => now()->endOfMonth()->toDateString(),
                        'created_by' => $adminUser?->id ?? 1,
                    ]
                );

                if ($isPaid) {
                    $p = Payment::firstOrCreate(
                        ['invoice_id' => $inv->id],
                        [
                            'no_kuitansi' => 'KWI/' . now()->format('Y/m') . '/' . str_pad((string)($idx + 1), 4, '0', STR_PAD_LEFT),
                            'tanggal' => $today,
                            'jumlah' => $sppFee->nominal,
                            'metode' => 'transfer',
                            'catatan' => 'Pembayaran SPP Bulan ' . now()->translatedFormat('F Y'),
                            'dicatat_oleh' => $adminUser?->id ?? 1,
                        ]
                    );

                    FinanceTransaction::firstOrCreate(
                        ['payment_id' => $p->id],
                        [
                            'no_transaksi' => 'TRX/M/' . now()->format('Y/m') . '/' . str_pad((string)($idx + 1), 4, '0', STR_PAD_LEFT),
                            'tipe' => 'masuk',
                            'kategori' => 'pembayaran_siswa',
                            'jumlah' => $p->jumlah,
                            'tanggal' => $today,
                            'keterangan' => "Penerimaan SPP {$st->nama_lengkap} ({$p->no_kuitansi})",
                            'dicatat_oleh' => $adminUser?->id ?? 1,
                        ]
                    );
                }
            }

            // Operasional Kas Masuk & Keluar
            FinanceTransaction::firstOrCreate(
                ['no_transaksi' => 'TRX/M/' . now()->format('Y/m') . '/9001'],
                [
                    'tipe' => 'masuk',
                    'kategori' => 'dana_bos',
                    'jumlah' => 25000000,
                    'tanggal' => now()->startOfMonth()->toDateString(),
                    'keterangan' => 'Pencairan Dana BOS Reguler Tahap II',
                    'dicatat_oleh' => $adminUser?->id ?? 1,
                ]
            );

            FinanceTransaction::firstOrCreate(
                ['no_transaksi' => 'TRX/K/' . now()->format('Y/m') . '/9002'],
                [
                    'tipe' => 'keluar',
                    'kategori' => 'atk',
                    'jumlah' => 1450000,
                    'tanggal' => now()->subDays(2)->toDateString(),
                    'keterangan' => 'Pembelian Kertas HVS, Tinta Printer, dan Spidol Whiteboard',
                    'dicatat_oleh' => $adminUser?->id ?? 1,
                ]
            );
        }

        // 9. SARPRAS & ASET
        $assets = [
            ['kode' => 'AST-PROJ-01', 'nama' => 'Proyektor Epson EB-X500', 'kategori' => 'elektronik', 'lokasi' => 'Lab Komputer', 'nilai' => 6500000, 'kondisi' => 'baik'],
            ['kode' => 'AST-AC-01', 'nama' => 'AC Daikin Inverter 1.5 PK', 'kategori' => 'elektronik', 'lokasi' => 'Ruang 1 Abu Bakar', 'nilai' => 5200000, 'kondisi' => 'baik'],
            ['kode' => 'AST-PC-01', 'nama' => 'PC Desktop All-in-One Guru', 'kategori' => 'elektronik', 'lokasi' => 'Ruang Guru', 'nilai' => 8500000, 'kondisi' => 'rusak_ringan'],
            ['kode' => 'AST-MIC-01', 'nama' => 'Wireless Microphone Set', 'kategori' => 'elektronik', 'lokasi' => 'Mushola Sekolah', 'nilai' => 1800000, 'kondisi' => 'baik'],
        ];

        foreach ($assets as $a) {
            $ast = Asset::firstOrCreate(
                ['kode' => $a['kode']],
                [
                    'nama' => $a['nama'],
                    'kategori' => $a['kategori'],
                    'lokasi' => $a['lokasi'],
                    'tahun_perolehan' => 2024,
                    'nilai' => $a['nilai'],
                    'kondisi' => $a['kondisi'],
                    'jumlah' => 1,
                    'satuan' => 'unit',
                    'created_by' => $adminUser?->id ?? 1,
                ]
            );

            if ($a['kondisi'] === 'rusak_ringan') {
                AssetMaintenance::firstOrCreate(
                    ['asset_id' => $ast->id, 'tanggal' => $today],
                    [
                        'deskripsi' => 'Penggantian power supply dan pembersihan kipas pendingin',
                        'biaya' => 350000,
                        'pelaksana' => 'CV Mitra Komputer',
                        'kondisi_sesudah' => 'baik',
                        'created_by' => $adminUser?->id ?? 1,
                    ]
                );
            }
        }
    }
}
