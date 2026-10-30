<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\BkCaseLog;
use App\Models\BkCounselingCase;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentObservation;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ObservationAndBkSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $academicYearId = $activeYear?->id;

        $guruBk = Teacher::where('jabatan', 'like', '%BK%')
            ->orWhere('nama_lengkap', 'like', '%Kafi%')
            ->first();
        $guruBkId = $guruBk?->id;
        $adminUser = User::where('username', 'admin')->orWhere('role_id', 1)->first();
        $adminUserId = $adminUser?->id;

        $students = Student::all();

        // Helper match student by name
        $findStudent = function (string $rawName) use ($students) {
            $cleaned = trim(preg_replace('/^:\s*/', '', $rawName));
            $cleanedLower = strtolower($cleaned);
            
            // Exact or contains match
            foreach ($students as $s) {
                $sName = strtolower($s->nama_lengkap);
                if ($sName === $cleanedLower || str_contains($sName, $cleanedLower) || str_contains($cleanedLower, $sName)) {
                    return $s;
                }
            }
            // Word parts match
            $words = explode(' ', $cleanedLower);
            foreach ($words as $w) {
                if (strlen($w) >= 4) {
                    foreach ($students as $s) {
                        if (str_contains(strtolower($s->nama_lengkap), $w)) {
                            return $s;
                        }
                    }
                }
            }
            return null;
        };

        // Helper parse date
        $parseDate = function ($raw) {
            if (empty($raw)) return Carbon::today()->format('Y-m-d');
            if (is_numeric($raw)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($raw))->format('Y-m-d');
            }
            // e.g. "Rabu, 16 September 2026" or "16 Sept 2026" or "2026-09-16"
            $bulanIndo = [
                'Januari' => '01', 'Februari' => '02', 'Maret' => '03', 'April' => '04',
                'Mei' => '05', 'Juni' => '06', 'Juli' => '07', 'Agustus' => '08',
                'September' => '09', 'Oktober' => '10', 'November' => '11', 'Desember' => '12',
                'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
                'Mei' => '05', 'Jun' => '06', 'Jul' => '07', 'Agu' => '08', 'Ags' => '08',
                'Sept' => '09', 'Sep' => '09', 'Okt' => '10', 'Nov' => '11', 'Des' => '12',
            ];
            $str = trim((string)$raw);
            foreach ($bulanIndo as $idName => $num) {
                if (stripos($str, $idName) !== false) {
                    if (preg_match('/(\d{1,2})\s+[A-Za-z]+\s+(\d{4})/', $str, $matches)) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $year = $matches[2];
                        return "{$year}-{$num}-{$day}";
                    }
                }
            }
            try {
                return Carbon::parse($str)->format('Y-m-d');
            } catch (\Exception $e) {
                return Carbon::today()->format('Y-m-d');
            }
        };

        $reader = new Xlsx();

        // ==========================================
        // 1. SEED CATATAN OBSERVASI KELAS 1
        // ==========================================
        $obsPath = base_path('../Catatan Observasi Kelas 1.xlsx');
        if (!file_exists($obsPath)) {
            $obsPath = base_path('Catatan Observasi Kelas 1.xlsx');
        }

        if (file_exists($obsPath)) {
            $spreadsheet = $reader->load($obsPath);
            $waliKelas1 = Teacher::find(1); // Rasiti

            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                $rawName = $sheet->getCell('B1')->getValue() ?: $sheetName;
                $student = $findStudent((string)$rawName);
                if (!$student) continue;

                $class = SchoolClass::where('tingkat', 1)->first();

                $highestRow = $sheet->getHighestRow();
                for ($r = 8; $r <= $highestRow; $r++) {
                    $tanggalRaw = $sheet->getCell('B' . $r)->getValue();
                    $perilaku = $sheet->getCell('F' . $r)->getValue();
                    if (empty($tanggalRaw) && empty($perilaku)) continue;
                    if (empty($perilaku)) continue;

                    $waktu = $sheet->getCell('C' . $r)->getValue();
                    if (is_numeric($waktu)) {
                        $waktu = "Jam Ke-" . round($waktu);
                    }
                    $tempat = $sheet->getCell('D' . $r)->getValue();
                    $situasi = $sheet->getCell('E' . $r)->getValue();
                    $respons = $sheet->getCell('G' . $r)->getValue();
                    $rekomBk = $sheet->getCell('H' . $r)->getValue();
                    $saranGuru = $sheet->getCell('I' . $r)->getValue();
                    $catatanWali = $sheet->getCell('J' . $r)->getValue();
                    $status = $sheet->getCell('K' . $r)->getValue() ?: 'Dalam Pemantauan';

                    StudentObservation::create([
                        'student_id' => $student->id,
                        'class_id' => $class?->id ?? $student->class_id,
                        'academic_year_id' => $academicYearId,
                        'teacher_id' => $waliKelas1?->id ?? 1,
                        'tanggal' => $parseDate($tanggalRaw),
                        'waktu' => $waktu ? substr((string)$waktu, 0, 100) : 'Jam Pelajaran',
                        'tempat' => $tempat ? substr((string)$tempat, 0, 150) : 'Ruang Kelas',
                        'situasi_sebelum' => $situasi ? (string)$situasi : null,
                        'perilaku_diamati' => (string)$perilaku,
                        'respons_lingkungan' => $respons ? (string)$respons : null,
                        'rekomendasi_bk' => $rekomBk ? (string)$rekomBk : null,
                        'rekomendasi_bk_by' => !empty($rekomBk) ? $guruBkId : null,
                        'rekomendasi_bk_at' => !empty($rekomBk) ? Carbon::now() : null,
                        'saran_untuk_guru' => $saranGuru ? (string)$saranGuru : null,
                        'catatan_wali_kelas' => $catatanWali ? (string)$catatanWali : null,
                        'status_pemantauan' => $status ? (string)$status : 'Dalam Pemantauan',
                        'is_escalated_to_bk' => true,
                        'created_by' => $adminUserId,
                    ]);
                }
            }
        }

        // ==========================================
        // 2. SEED ANALISIS & MONITORING BK
        // ==========================================
        $bkPath = base_path('../Analisis & Monitoring BK-2026_2027.xlsx');
        if (!file_exists($bkPath)) {
            $bkPath = base_path('Analisis & Monitoring BK-2026_2027.xlsx');
        }

        if (file_exists($bkPath)) {
            $bkSpreadsheet = $reader->load($bkPath);
            $daftarSheet = $bkSpreadsheet->getSheetByName('DAFTAR PEMANTAUAN');

            $casesConfig = [];
            if ($daftarSheet) {
                $maxR = $daftarSheet->getHighestRow();
                for ($r = 2; $r <= $maxR; $r++) {
                    $nama = $daftarSheet->getCell('B' . $r)->getValue();
                    if (empty($nama)) continue;
                    $kelasTingkat = $daftarSheet->getCell('C' . $r)->getValue();
                    $tglMasuk = $daftarSheet->getCell('D' . $r)->getValue();
                    $sumberAwal = $daftarSheet->getCell('E' . $r)->getValue() ?: 'Wali Kelas';
                    $fokus = $daftarSheet->getCell('F' . $r)->getValue() ?: 'Pribadi';
                    $sheetTarget = $nama;

                    $casesConfig[$sheetNameMap = trim((string)$nama)] = [
                        'nama' => $nama,
                        'tingkat' => (int)$kelasTingkat,
                        'tgl_masuk' => $tglMasuk,
                        'sumber_awal' => $sumberAwal,
                        'fokus' => $fokus,
                    ];
                }
            }

            // Tab names to inspect
            $activeTabs = ['Kamayel', 'Raynar', 'Salman', 'Sadira', 'Afkar', 'Agha', 'Qiana', 'Abyy', 'Khalid'];
            foreach ($activeTabs as $tabName) {
                $sheet = $bkSpreadsheet->getSheetByName($tabName);
                if (!$sheet) continue;

                $rawStudentName = $sheet->getCell('C2')->getValue() ?: $tabName;
                $student = $findStudent((string)$rawStudentName);
                if (!$student) continue;

                $tingkat = (int)str_replace(':', '', trim((string)$sheet->getCell('C3')->getValue()));
                $class = SchoolClass::where('tingkat', $tingkat)->first() ?? $student->schoolClass;

                // Wali kelas
                $waliKelasName = trim(str_replace(':', '', (string)$sheet->getCell('C5')->getValue()));
                $waliKelas = Teacher::where('nama_lengkap', 'like', '%' . substr($waliKelasName, 0, 8) . '%')->first();

                $tglMasukRaw = $sheet->getCell('C7')->getValue();
                $sumberAwal = trim((string)$sheet->getCell('C8')->getValue()) ?: 'Wali Kelas';
                $fokus = trim((string)$sheet->getCell('C9')->getValue()) ?: 'Pribadi';
                $statusSaatIni = trim((string)$sheet->getCell('C10')->getValue()) ?: 'Dalam Pemantauan';

                $counselingCase = BkCounselingCase::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYearId,
                    'class_id' => $class?->id,
                    'wali_kelas_id' => $waliKelas?->id,
                    'guru_bk_id' => $guruBkId,
                    'tanggal_masuk' => $parseDate($tglMasukRaw),
                    'sumber_awal' => $sumberAwal,
                    'fokus_pemantauan' => $fokus,
                    'status_saat_ini' => $statusSaatIni,
                    'review_terakhir' => Carbon::now()->subDays(3)->format('Y-m-d'),
                    'review_berikutnya' => Carbon::now()->addDays(7)->format('Y-m-d'),
                    'catatan_umum' => "Pemantauan berkala fokus {$fokus} oleh Guru BK bersama wali kelas.",
                    'created_by' => $adminUserId,
                ]);

                // Read logs
                $highestRow = $sheet->getHighestRow();
                for ($r = 13; $r <= $highestRow; $r++) {
                    $tanggalLogRaw = $sheet->getCell('B' . $r)->getValue();
                    $observasi = $sheet->getCell('E' . $r)->getValue();
                    if (empty($tanggalLogRaw) && empty($observasi)) continue;
                    if (empty($observasi)) continue;

                    $tahap = $sheet->getCell('C' . $r)->getValue() ?: 'Pemantauan';
                    $sumberData = $sheet->getCell('D' . $r)->getValue() ?: $sumberAwal;
                    $telaahBk = $sheet->getCell('F' . $r)->getValue();
                    $kebutuhan = $sheet->getCell('G' . $r)->getValue();
                    $tindakLanjut = $sheet->getCell('H' . $r)->getValue();
                    $hasilEvaluasi = $sheet->getCell('I' . $r)->getValue();
                    $perubahanPola = $sheet->getCell('J' . $r)->getValue() ?: 'Belum Cukup Data';
                    $statusLog = $sheet->getCell('K' . $r)->getValue() ?: 'Dalam Pemantauan';
                    $catatanKonteks = $sheet->getCell('L' . $r)->getValue();

                    BkCaseLog::create([
                        'bk_counseling_case_id' => $counselingCase->id,
                        'tanggal' => $parseDate($tanggalLogRaw),
                        'tahap' => (string)$tahap,
                        'sumber_data' => $sumberData ? (string)$sumberData : null,
                        'observasi_temuan' => (string)$observasi,
                        'telaah_bk' => $telaahBk ? (string)$telaahBk : null,
                        'kebutuhan_tujuan' => $kebutuhan ? (string)$kebutuhan : null,
                        'tindak_lanjut' => $tindakLanjut ? (string)$tindakLanjut : null,
                        'hasil_evaluasi' => $hasilEvaluasi ? (string)$hasilEvaluasi : null,
                        'perubahan_pola' => (string)$perubahanPola,
                        'status' => (string)$statusLog,
                        'catatan_konteks' => $catatanKonteks ? (string)$catatanKonteks : null,
                        'created_by' => $adminUserId,
                    ]);
                }
            }
        }
    }
}

