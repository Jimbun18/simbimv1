<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Education;
use App\Models\Guardian;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Transport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportDapodikStudentsCommand extends Command
{
    protected $signature = 'app:import-dapodik-students {--file= : Path ke file Excel Dapodik}';

    protected $description = 'Import 62 data siswa dan 4 rombel asli dari file Excel Dapodik';

    public function handle(): int
    {
        $filePath = $this->option('file') ?: 'c:/laragon/www/simbim/daftar_pd-SD ISLAM BINA INSAN MANDIRI PURWOKERTO-2026-10-01 14_13_21.xlsx';

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Membaca file: {$filePath}");

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // 1. Setup / Update the 4 real Classes
        $this->info("Menyiapkan 4 Rombel resmi...");
        $academicYear = AcademicYear::where('is_aktif', true)->first() ?: AcademicYear::first();
        $ayId = $academicYear ? $academicYear->id : 1;

        $classConfigs = [
            'IBNU SINA' => [
                'kode' => '1-IS',
                'nama' => 'Kelas 1 Ibnu Sina',
                'tingkat' => 1,
                'wali_kelas_id' => 1, // Rasiti, S.Pd
            ],
            'AL KHAWARIZMI' => [
                'kode' => '2-AK',
                'nama' => 'Kelas 2 Al Khawarizmi',
                'tingkat' => 2,
                'wali_kelas_id' => 3, // Ustadzah Fatimah Azzahra
            ],
            'AL GHAZALI' => [
                'kode' => '3-AG',
                'nama' => 'Kelas 3 Al Ghazali',
                'tingkat' => 3,
                'wali_kelas_id' => 4, // Ustadz Salman Al-Farisi
            ],
            'AN NAWAWI' => [
                'kode' => '4-AN',
                'nama' => 'Kelas 4 An Nawawi',
                'tingkat' => 4,
                'wali_kelas_id' => 5, // Ustadzah Aisyah Humaira
            ],
        ];

        $classMap = [];

        // Update existing classes (1, 2, 3) or create new
        foreach ($classConfigs as $rombelKey => $cfg) {
            $existing = SchoolClass::where('tingkat', $cfg['tingkat'])->first();
            if ($existing) {
                $existing->update([
                    'kode' => $cfg['kode'],
                    'nama' => $cfg['nama'],
                    'academic_year_id' => $ayId,
                    'wali_kelas_id' => $cfg['wali_kelas_id'],
                    'kapasitas' => 30,
                    'status' => 'aktif',
                ]);
                $classMap[$rombelKey] = $existing;
                $this->info("Updated kelas: {$existing->nama} ({$existing->kode})");
            } else {
                $created = SchoolClass::create([
                    'academic_year_id' => $ayId,
                    'kode' => $cfg['kode'],
                    'nama' => $cfg['nama'],
                    'tingkat' => $cfg['tingkat'],
                    'wali_kelas_id' => $cfg['wali_kelas_id'],
                    'kapasitas' => 30,
                    'status' => 'aktif',
                ]);
                $classMap[$rombelKey] = $created;
                $this->info("Created kelas: {$created->nama} ({$created->kode})");
            }
        }

        // 2. Pre-load reference maps
        $religions = Religion::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $transports = Transport::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $educations = Education::pluck('id', DB::raw('LOWER(nama)'))->toArray();
        $occupations = Occupation::pluck('id', DB::raw('LOWER(nama)'))->toArray();

        // 3. Clear existing students and guardians cleanly
        $this->info("Membersihkan data siswa dan wali...");
        DB::table('student_guardian')->delete();
        DB::table('student_enrollments')->delete();
        DB::table('payments')->delete();
        DB::table('invoices')->delete();
        Student::withTrashed()->forceDelete();
        Guardian::withTrashed()->forceDelete();

        // 4. Iterate over Dapodik rows (Row 7 to highestRow)
        $this->info("Mengimpor data peserta didik Dapodik...");
        $importedCount = 0;
        $studentIndex = 0;

        for ($row = 7; $row <= $highestRow; $row++) {
            $nama = trim((string)$sheet->getCell("B{$row}")->getValue());
            if (!$nama) {
                continue;
            }

            $nipd = trim((string)$sheet->getCell("C{$row}")->getValue());
            $jk = strtoupper(trim((string)$sheet->getCell("D{$row}")->getValue()));
            $nisn = trim((string)$sheet->getCell("E{$row}")->getValue());
            $tempatLahir = trim((string)$sheet->getCell("F{$row}")->getValue()) ?: 'Banyumas';
            $tglLahirRaw = $sheet->getCell("G{$row}")->getValue();
            $nik = trim((string)$sheet->getCell("H{$row}")->getValue());
            $agamaStr = trim((string)$sheet->getCell("I{$row}")->getValue()) ?: 'Islam';
            $alamat = trim((string)$sheet->getCell("J{$row}")->getValue()) ?: '-';
            $rt = trim((string)$sheet->getCell("K{$row}")->getValue());
            $rw = trim((string)$sheet->getCell("L{$row}")->getValue());
            $desa = trim((string)$sheet->getCell("N{$row}")->getValue());
            $kecamatan = trim((string)$sheet->getCell("O{$row}")->getValue());
            $kodePos = trim((string)$sheet->getCell("P{$row}")->getValue());
            $tinggalBersama = trim((string)$sheet->getCell("Q{$row}")->getValue()) ?: 'orang_tua';
            $transportStr = trim((string)$sheet->getCell("R{$row}")->getValue());
            $hp = trim((string)$sheet->getCell("T{$row}")->getValue());
            $email = trim((string)$sheet->getCell("U{$row}")->getValue());

            // Parent Ayah
            $namaAyah = trim((string)$sheet->getCell("Y{$row}")->getValue());
            $pendidikanAyahStr = trim((string)$sheet->getCell("AA{$row}")->getValue());
            $pekerjaanAyahStr = trim((string)$sheet->getCell("AB{$row}")->getValue());
            $nikAyah = trim((string)$sheet->getCell("AD{$row}")->getValue());

            // Parent Ibu
            $namaIbu = trim((string)$sheet->getCell("AE{$row}")->getValue());
            $pendidikanIbuStr = trim((string)$sheet->getCell("AG{$row}")->getValue());
            $pekerjaanIbuStr = trim((string)$sheet->getCell("AH{$row}")->getValue());
            $nikIbu = trim((string)$sheet->getCell("AJ{$row}")->getValue());

            // Parent Wali
            $namaWali = trim((string)$sheet->getCell("AK{$row}")->getValue());
            $pendidikanWaliStr = trim((string)$sheet->getCell("AM{$row}")->getValue());
            $pekerjaanWaliStr = trim((string)$sheet->getCell("AN{$row}")->getValue());
            $nikWali = trim((string)$sheet->getCell("AP{$row}")->getValue());

            // Rombel
            $rombelStr = strtoupper(trim((string)$sheet->getCell("AQ{$row}")->getValue()));

            // Additional
            $noAkta = trim((string)$sheet->getCell("AX{$row}")->getValue());
            $asalTk = trim((string)$sheet->getCell("BE{$row}")->getValue());
            $anakKe = (int)$sheet->getCell("BF{$row}")->getValue() ?: 1;
            $lat = (string)$sheet->getCell("BG{$row}")->getValue();
            $long = (string)$sheet->getCell("BH{$row}")->getValue();
            $noKk = trim((string)$sheet->getCell("BI{$row}")->getValue());
            $bb = (float)$sheet->getCell("BJ{$row}")->getValue() ?: null;
            $tb = (float)$sheet->getCell("BK{$row}")->getValue() ?: null;
            $jmlSaudara = (int)$sheet->getCell("BM{$row}")->getValue() ?: 0;
            $jarakKm = (float)$sheet->getCell("BN{$row}")->getValue() ?: null;

            $dusun = trim((string)$sheet->getCell("M{$row}")->getValue());
            $telepon = trim((string)$sheet->getCell("S{$row}")->getValue());
            $skhun = trim((string)$sheet->getCell("V{$row}")->getValue());
            $penerimaKps = trim((string)$sheet->getCell("W{$row}")->getValue());
            $noKps = trim((string)$sheet->getCell("X{$row}")->getValue());
            $noPesertaUn = trim((string)$sheet->getCell("AR{$row}")->getValue());
            $noSeriIjazah = trim((string)$sheet->getCell("AS{$row}")->getValue());
            $penerimaKip = trim((string)$sheet->getCell("AT{$row}")->getValue());
            $nomorKip = trim((string)$sheet->getCell("AU{$row}")->getValue());
            $namaDiKip = trim((string)$sheet->getCell("AV{$row}")->getValue());
            $nomorKks = trim((string)$sheet->getCell("AW{$row}")->getValue());
            $bank = trim((string)$sheet->getCell("AY{$row}")->getValue());
            $nomorRekeningBank = trim((string)$sheet->getCell("AZ{$row}")->getValue());
            $rekeningAtasNama = trim((string)$sheet->getCell("BA{$row}")->getValue());
            $layakPip = trim((string)$sheet->getCell("BB{$row}")->getValue());
            $alasanLayakPip = trim((string)$sheet->getCell("BC{$row}")->getValue());
            $lingkarKepala = (float)$sheet->getCell("BL{$row}")->getValue() ?: null;

            // Resolve religion and transport
            $agamaId = $religions[strtolower($agamaStr)] ?? 1; // default Islam
            $transportId = null;
            if ($transportStr) {
                foreach ($transports as $tName => $tId) {
                    if (str_contains(strtolower($transportStr), $tName) || str_contains($tName, strtolower($transportStr))) {
                        $transportId = $tId;
                        break;
                    }
                }
            }

            // Resolve Class
            $targetClass = $classMap[$rombelStr] ?? null;
            $classId = $targetClass ? $targetClass->id : null;

            // Check if student with same NISN or NIK already exists (e.g. Shumaila duplicate in row 66 & 67)
            $existingStudent = !empty($nisn) ? Student::where('nisn', $nisn)->first() : null;
            if (!$existingStudent && !empty($nik)) {
                $existingStudent = Student::where('nik', $nik)->first();
            }

            if ($existingStudent) {
                $this->info("Pembaruan duplikat siswa baris {$row}: {$nama} (NISN: {$nisn}) -> kelas {$rombelStr}");
                $existingStudent->update([
                    'nama_lengkap' => $nama,
                    'class_id' => $classId ?: $existingStudent->class_id,
                    'no_kk' => $noKk ?: $existingStudent->no_kk,
                    'no_akta_lahir' => $noAkta ?: $existingStudent->no_akta_lahir,
                    'tinggi_cm' => $tb ?: $existingStudent->tinggi_cm,
                    'berat_kg' => $bb ?: $existingStudent->berat_kg,
                    'lingkar_kepala' => $lingkarKepala ?: $existingStudent->lingkar_kepala,
                    'dusun' => $dusun ?: $existingStudent->dusun,
                    'desa_kelurahan' => $desa ?: $existingStudent->desa_kelurahan,
                    'kecamatan' => $kecamatan ?: $existingStudent->kecamatan,
                    'kode_pos' => $kodePos ?: $existingStudent->kode_pos,
                    'alamat_jalan' => ($alamat !== '-' && !empty($alamat)) ? $alamat : $existingStudent->alamat_jalan,
                    'telepon' => $telepon ?: $existingStudent->telepon,
                    'skhun' => $skhun ?: $existingStudent->skhun,
                    'penerima_kps' => $penerimaKps ?: $existingStudent->penerima_kps,
                    'no_kps' => $noKps ?: $existingStudent->no_kps,
                    'penerima_kip' => $penerimaKip ?: $existingStudent->penerima_kip,
                    'nomor_kip' => $nomorKip ?: $existingStudent->nomor_kip,
                    'nama_di_kip' => $namaDiKip ?: $existingStudent->nama_di_kip,
                    'nomor_kks' => $nomorKks ?: $existingStudent->nomor_kks,
                    'bank' => $bank ?: $existingStudent->bank,
                    'nomor_rekening_bank' => $nomorRekeningBank ?: $existingStudent->nomor_rekening_bank,
                    'rekening_atas_nama' => $rekeningAtasNama ?: $existingStudent->rekening_atas_nama,
                    'layak_pip' => $layakPip ?: $existingStudent->layak_pip,
                    'alasan_layak_pip' => $alasanLayakPip ?: $existingStudent->alasan_layak_pip,
                ]);

                if ($classId) {
                    StudentEnrollment::where('student_id', $existingStudent->id)
                        ->where('academic_year_id', $ayId)
                        ->update(['class_id' => $classId]);
                }

                $student = $existingStudent;
            } else {
                $studentIndex++;

                // Generate clean NIS if empty
                $nis = !empty($nipd) ? $nipd : sprintf("BIM%04d", $studentIndex);

                // Parse Birth Date
                $tglLahir = $this->parseDate($tglLahirRaw) ?: '2018-01-01';

                // Normalize tinggal_bersama
                $tbEnum = 'orang_tua';
                if (str_contains(strtolower($tinggalBersama), 'wali')) {
                    $tbEnum = 'wali';
                } elseif (str_contains(strtolower($tinggalBersama), 'asrama') || str_contains(strtolower($tinggalBersama), 'pesantren')) {
                    $tbEnum = 'asrama';
                } elseif (str_contains(strtolower($tinggalBersama), 'kost')) {
                    $tbEnum = 'kost';
                }

                // Create Student
                $student = Student::create([
                    'nis' => $nis,
                    'nisn' => $nisn ?: null,
                    'nik' => $nik ?: null,
                    'no_kk' => $noKk ?: null,
                    'no_akta_lahir' => $noAkta ?: null,
                    'nama_lengkap' => $nama,
                    'jenis_kelamin' => $jk === 'P' ? 'P' : 'L',
                    'tempat_lahir' => $tempatLahir,
                    'tgl_lahir' => $tglLahir,
                    'agama' => $agamaId,
                    'kewarganegaraan' => 'WNI',
                    'anak_ke' => $anakKe,
                    'jumlah_saudara' => $jmlSaudara,
                    'alamat_jalan' => $alamat,
                    'rt' => $rt ?: null,
                    'rw' => $rw ?: null,
                    'dusun' => $dusun ?: null,
                    'desa_kelurahan' => $desa ?: null,
                    'kecamatan' => $kecamatan ?: null,
                    'kab_kota' => 'Banyumas',
                    'provinsi' => 'Jawa Tengah',
                    'kode_pos' => $kodePos ?: null,
                    'latitude' => !empty($lat) && is_numeric($lat) ? $lat : null,
                    'longitude' => !empty($long) && is_numeric($long) ? $long : null,
                    'tinggal_bersama' => $tbEnum,
                    'transportasi' => $transportId,
                    'jarak_ke_sekolah_km' => $jarakKm,
                    'no_hp_siswa' => $hp ?: null,
                    'telepon' => $telepon ?: null,
                    'email' => $email ?: null,
                    'tinggi_cm' => $tb,
                    'berat_kg' => $bb,
                    'lingkar_kepala' => $lingkarKepala,
                    'asal_sekolah_tk' => $asalTk ?: null,
                    'skhun' => $skhun ?: null,
                    'penerima_kps' => $penerimaKps ?: 'Tidak',
                    'no_kps' => $noKps ?: null,
                    'no_peserta_un' => $noPesertaUn ?: null,
                    'no_seri_ijazah' => $noSeriIjazah ?: null,
                    'penerima_kip' => $penerimaKip ?: 'Tidak',
                    'nomor_kip' => $nomorKip ?: null,
                    'nama_di_kip' => $namaDiKip ?: null,
                    'nomor_kks' => $nomorKks ?: null,
                    'bank' => $bank ?: null,
                    'nomor_rekening_bank' => $nomorRekeningBank ?: null,
                    'rekening_atas_nama' => $rekeningAtasNama ?: null,
                    'layak_pip' => $layakPip ?: 'Tidak',
                    'alasan_layak_pip' => $alasanLayakPip ?: null,
                    'jenis_pendaftaran' => 'baru',
                    'tgl_masuk' => '2026-07-15',
                    'tahun_masuk' => 2026,
                    'class_id' => $classId,
                ]);

                // Create Enrollment
                if ($classId) {
                    StudentEnrollment::create([
                        'student_id' => $student->id,
                        'class_id' => $classId,
                        'academic_year_id' => $ayId,
                        'tgl_masuk_kelas' => '2026-07-15',
                        'status_kelas' => 'aktif',
                        'nomor_absen' => $studentIndex,
                    ]);
                }
            }

            // Prioritas Penentuan Wali Murid (Ayah > Ibu > Wali)
            $phone = $hp ?: '0812' . rand(10000000, 99999999);
            $chosenGuardian = null;

            if (!empty($namaAyah)) {
                $chosenGuardian = [
                    'hubungan' => 'ayah',
                    'nama' => $namaAyah,
                    'nik' => $nikAyah ?: null,
                ];
            } elseif (!empty($namaIbu)) {
                $chosenGuardian = [
                    'hubungan' => 'ibu',
                    'nama' => $namaIbu,
                    'nik' => $nikIbu ?: null,
                ];
            } elseif (!empty($namaWali)) {
                $chosenGuardian = [
                    'hubungan' => 'wali',
                    'nama' => $namaWali,
                    'nik' => $nikWali ?: null,
                ];
            }

            if ($chosenGuardian) {
                $guardian = null;
                if (!empty($chosenGuardian['nik'])) {
                    $guardian = Guardian::where('nik', $chosenGuardian['nik'])->first();
                }
                if (!$guardian) {
                    $guardian = Guardian::where('nama_lengkap', $chosenGuardian['nama'])
                        ->where('hubungan', $chosenGuardian['hubungan'])
                        ->first();
                }

                if (!$guardian) {
                    $guardian = Guardian::create([
                        'hubungan' => $chosenGuardian['hubungan'],
                        'nama_lengkap' => $chosenGuardian['nama'],
                        'nik' => $chosenGuardian['nik'],
                        'no_hp' => $phone,
                        'no_whatsapp' => $phone,
                        'alamat_jalan' => $alamat,
                        'rt' => $rt ?: null,
                        'rw' => $rw ?: null,
                        'desa_kelurahan' => $desa ?: null,
                        'kecamatan' => $kecamatan ?: null,
                        'kab_kota' => 'Banyumas',
                        'provinsi' => 'Jawa Tengah',
                    ]);
                }

                if (!$student->guardians()->where('guardians.id', $guardian->id)->exists()) {
                    $student->guardians()->attach($guardian->id, [
                        'hubungan' => $chosenGuardian['hubungan'],
                        'is_kontak_utama' => true,
                        'boleh_lihat_keuangan' => true,
                        'boleh_jemput' => true,
                    ]);
                }
            }

            $importedCount++;
        }

        $this->info("SELESAI! Berhasil mengimpor {$importedCount} siswa asli ke database.");

        // Print breakdown per class
        foreach ($classMap as $rombel => $cls) {
            $count = Student::where('class_id', $cls->id)->count();
            $this->info("- {$cls->nama} ({$cls->kode}): {$count} siswa");
        }

        return Command::SUCCESS;
    }

    private function parseDate(mixed $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        if (is_numeric($val)) {
            try {
                return ExcelDate::excelToDateTimeObject((float)$val)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        $val = trim((string)$val);
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $val);
                if ($dt !== false) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
