<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BkCaseLog;
use App\Models\BkCounselingCase;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BkMonitoringController extends Controller
{
    /**
     * Memastikan akses KHUSUS untuk Guru Bimbingan Konseling (Guru BK).
     */
    protected function checkGuruBk(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $teacher = $user->teacher;
        $jabatan = strtolower($teacher?->jabatan ?? '');
        $tugas = strtolower($teacher?->tugas_tambahan ?? '');

        $isBk = $user->isTeacher() && (
            str_contains($jabatan, 'bk') ||
            str_contains($jabatan, 'bimbingan') ||
            str_contains($tugas, 'bk') ||
            str_contains($tugas, 'bimbingan')
        );

        if (!$isBk) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Terbatas: Menu Analisis & Monitoring BK hanya dapat diakses oleh Guru Bimbingan Konseling (BK).',
            ], 403);
        }

        return null;
    }

    /**
     * Dashboard Analisis BK (Statistik, 4 Status, Agenda Review, Distribusi Kelas, Fokus, Sumber).
     */
    public function dashboard(Request $request): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $baseQuery = BkCounselingCase::query();
        if ($activeYear) {
            $baseQuery->where('academic_year_id', $activeYear->id);
        }

        $totalCases = (clone $baseQuery)->count();
        $dalamPemantauan = (clone $baseQuery)->where('status_saat_ini', 'Dalam Pemantauan')->count();
        $menunjukkanPerkembangan = (clone $baseQuery)->where('status_saat_ini', 'Menunjukkan Perkembangan')->count();
        $sesuaiHarapan = (clone $baseQuery)->where('status_saat_ini', 'Sesuai Harapan')->count();
        $memerlukanPerhatian = (clone $baseQuery)->where('status_saat_ini', 'Memerlukan Perhatian')->count();

        // 2. Agenda Review
        $today = Carbon::today()->format('Y-m-d');
        $sevenDaysLater = Carbon::today()->addDays(7)->format('Y-m-d');

        $perluReview = (clone $baseQuery)->whereNotNull('review_berikutnya')->whereDate('review_berikutnya', '<=', $today)->count();
        $reviewMingguIni = (clone $baseQuery)->whereNotNull('review_berikutnya')->whereBetween('review_berikutnya', [$today, $sevenDaysLater])->count();
        $reviewBerikutnya = (clone $baseQuery)->whereNotNull('review_berikutnya')->whereDate('review_berikutnya', '>', $sevenDaysLater)->count();

        $upcomingReviews = (clone $baseQuery)
            ->with(['student:id,nis,nama_lengkap,foto_path', 'schoolClass:id,nama,tingkat'])
            ->whereNotNull('review_berikutnya')
            ->orderBy('review_berikutnya', 'asc')
            ->limit(8)
            ->get();

        // 3. Distribusi Berdasarkan Kelas (1, 2, 3, 4, dst.)
        $classes = SchoolClass::orderBy('tingkat')->get();
        $distribusiKelas = [];
        foreach ($classes as $c) {
            $count = (clone $baseQuery)->where('class_id', $c->id)->count();
            $distribusiKelas[] = [
                'class_id' => $c->id,
                'tingkat' => $c->tingkat,
                'nama' => $c->nama,
                'jumlah' => $count,
            ];
        }

        // 4. Distribusi Fokus Pemantauan (10 Kategori Resmi BK)
        $kategoriFokus = [
            'Pribadi' => 'Penyesuaian diri, konsep diri, kepercayaan diri, kemandirian',
            'Sosial' => 'Relasi teman, kerja kelompok, interaksi sosial',
            'Belajar' => 'Motivasi, konsentrasi, strategi belajar, kesulitan akademik',
            'Perkembangan' => 'Pencapaian tugas perkembangan, perubahan yang perlu diamati',
            'Emosi/Regulasi Diri' => 'Mudah menangis, marah, cemas, sulit mengelola emosi',
            'Perilaku' => 'Perilaku berulang yang perlu diamati dan diarahkan',
            'Kedisiplinan' => 'Aturan, tanggung jawab, keteraturan',
            'Kehadiran' => 'Sering tidak masuk, keterlambatan, pola kehadiran',
            'Potensi/Bakat' => 'Kemampuan, minat, kekuatan yang perlu dikembangkan',
            'Lainnya' => 'Hal khusus yang tidak sesuai kategori di atas',
        ];

        $distribusiFokus = [];
        foreach ($kategoriFokus as $fokus => $contoh) {
            $count = (clone $baseQuery)->where('fokus_pemantauan', $fokus)->count();
            $distribusiFokus[] = [
                'fokus' => $fokus,
                'keterangan' => $contoh,
                'jumlah' => $count,
            ];
        }

        // 5. Distribusi Sumber Awal
        $sumberAwalList = ['Wali Kelas', 'Guru Tema', 'Guru BK', 'Orang Tua', 'Siswa'];
        $distribusiSumber = [];
        foreach ($sumberAwalList as $sumber) {
            $count = (clone $baseQuery)->where('sumber_awal', 'like', "%{$sumber}%")->count();
            $distribusiSumber[] = [
                'sumber' => $sumber,
                'jumlah' => $count,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard BK berhasil dimuat.',
            'data' => [
                'ringkasan' => [
                    'total_kasus' => $totalCases,
                    'dalam_pemantauan' => $dalamPemantauan,
                    'menunjukkan_perkembangan' => $menunjukkanPerkembangan,
                    'sesuai_harapan' => $sesuaiHarapan,
                    'memerlukan_perhatian' => $memerlukanPerhatian,
                ],
                'agenda_review' => [
                    'perlu_review' => $perluReview,
                    'review_minggu_ini' => $reviewMingguIni,
                    'review_berikutnya' => $reviewBerikutnya,
                    'daftar' => $upcomingReviews,
                ],
                'distribusi_kelas' => $distribusiKelas,
                'distribusi_fokus' => $distribusiFokus,
                'distribusi_sumber' => $distribusiSumber,
            ],
        ]);
    }

    /**
     * Daftar Kasus Pemantauan BK (Sheet: DAFTAR PEMANTAUAN).
     */
    public function indexCases(Request $request): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $query = BkCounselingCase::with([
            'student:id,nis,nama_lengkap,jenis_kelamin,foto_path,class_id',
            'schoolClass:id,nama,tingkat',
            'homeroomTeacher:id,nama_lengkap,jabatan',
            'bkTeacher:id,nama_lengkap,jabatan',
        ])->withCount('logs');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('status')) {
            $query->where('status_saat_ini', $request->query('status'));
        }

        if ($request->filled('fokus')) {
            $query->where('fokus_pemantauan', $request->query('fokus'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('nama_lengkap', 'like', "%{$search}%")
                       ->orWhere('nis', 'like', "%{$search}%");
                })
                ->orWhere('catatan_umum', 'like', "%{$search}%");
            });
        }

        $cases = $query->orderBy('tanggal_masuk', 'desc')->paginate($request->query('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Daftar pemantauan kasus BK berhasil diambil.',
            'data' => $cases->items(),
            'meta' => [
                'current_page' => $cases->currentPage(),
                'last_page' => $cases->lastPage(),
                'per_page' => $cases->perPage(),
                'total' => $cases->total(),
            ],
        ]);
    }

    /**
     * Tambah Kasus Pemantauan Siswa Baru.
     */
    public function storeCase(Request $request): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'tanggal_masuk' => ['required', 'date'],
            'sumber_awal' => ['required', 'string', 'max:100'],
            'fokus_pemantauan' => ['required', 'string', 'max:100'],
            'status_saat_ini' => ['required', 'string', 'max:50'],
            'review_berikutnya' => ['nullable', 'date'],
            'catatan_umum' => ['nullable', 'string'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $user = $request->user();

        $validated['academic_year_id'] = $activeYear?->id;
        $validated['class_id'] = $student->class_id;
        $validated['wali_kelas_id'] = $student->class_id ? SchoolClass::find($student->class_id)?->wali_kelas_id : null;
        $validated['guru_bk_id'] = $user->teacher?->id;
        $validated['created_by'] = $user->id;

        $case = BkCounselingCase::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'bk_monitoring',
            targetType: BkCounselingCase::class,
            targetId: $case->id,
            dataSesudah: $case->toArray(),
            keterangan: "Menambahkan siswa {$student->nama_lengkap} ke dalam Sistem Analisis & Monitoring BK."
        );

        return response()->json([
            'success' => true,
            'message' => 'Kasus pemantauan BK berhasil dibuat.',
            'data' => $case->load(['student', 'schoolClass', 'homeroomTeacher', 'bkTeacher']),
        ], 201);
    }

    /**
     * Detail Kasus Siswa & Riwayat Log Longitudinal (Tab Siswa).
     */
    public function showCase(Request $request, BkCounselingCase $counselingCase): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $counselingCase->load([
            'student.currentClass',
            'student.guardians',
            'schoolClass.homeroomTeacher',
            'homeroomTeacher',
            'bkTeacher',
            'logs' => function ($q) {
                $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail lembar pemantauan BK berhasil diambil.',
            'data' => $counselingCase,
        ]);
    }

    /**
     * Update Kasus Pemantauan Siswa.
     */
    public function updateCase(Request $request, BkCounselingCase $counselingCase): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $validated = $request->validate([
            'fokus_pemantauan' => ['sometimes', 'required', 'string', 'max:100'],
            'status_saat_ini' => ['sometimes', 'required', 'string', 'max:50'],
            'sumber_awal' => ['nullable', 'string', 'max:100'],
            'review_terakhir' => ['nullable', 'date'],
            'review_berikutnya' => ['nullable', 'date'],
            'catatan_umum' => ['nullable', 'string'],
        ]);

        $old = $counselingCase->toArray();
        $validated['updated_by'] = $request->user()->id;
        $counselingCase->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'bk_monitoring',
            targetType: BkCounselingCase::class,
            targetId: $counselingCase->id,
            dataSebelum: $old,
            dataSesudah: $counselingCase->fresh()->toArray(),
            keterangan: "Memperbarui kasus pemantauan BK siswa ID {$counselingCase->student_id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data kasus pemantauan BK berhasil diperbarui.',
            'data' => $counselingCase->fresh()->load(['student', 'schoolClass', 'homeroomTeacher', 'bkTeacher']),
        ]);
    }

    /**
     * Hapus Kasus BK.
     */
    public function destroyCase(Request $request, BkCounselingCase $counselingCase): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $counselingCase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kasus pemantauan BK berhasil dihapus.',
        ]);
    }

    /**
     * Tambah Catatan Tahapan / Intervensi Log Kasus Siswa (Row di Tab Siswa).
     */
    public function storeLog(Request $request, BkCounselingCase $counselingCase): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'tahap' => ['required', 'string', 'max:100'],
            'sumber_data' => ['nullable', 'string', 'max:100'],
            'observasi_temuan' => ['required', 'string'],
            'telaah_bk' => ['nullable', 'string'],
            'kebutuhan_tujuan' => ['nullable', 'string'],
            'tindak_lanjut' => ['nullable', 'string'],
            'hasil_evaluasi' => ['nullable', 'string'],
            'perubahan_pola' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'catatan_konteks' => ['nullable', 'string'],
        ]);

        $validated['bk_counseling_case_id'] = $counselingCase->id;
        $validated['created_by'] = $request->user()->id;

        $log = BkCaseLog::create($validated);

        // Otomatis sinkronkan status terkini dan review terakhir di induk kasus
        $counselingCase->update([
            'status_saat_ini' => $validated['status'],
            'review_terakhir' => $validated['tanggal'],
        ]);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'bk_monitoring',
            targetType: BkCaseLog::class,
            targetId: $log->id,
            dataSesudah: $log->toArray(),
            keterangan: "Menambahkan log tahap {$log->tahap} untuk siswa kasus BK ID {$counselingCase->student_id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Log pemantauan BK berhasil ditambahkan.',
            'data' => $log,
        ], 201);
    }

    /**
     * Update Log Kasus BK.
     */
    public function updateLog(Request $request, BkCaseLog $log): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $validated = $request->validate([
            'tanggal' => ['sometimes', 'required', 'date'],
            'tahap' => ['sometimes', 'required', 'string', 'max:100'],
            'sumber_data' => ['nullable', 'string', 'max:100'],
            'observasi_temuan' => ['sometimes', 'required', 'string'],
            'telaah_bk' => ['nullable', 'string'],
            'kebutuhan_tujuan' => ['nullable', 'string'],
            'tindak_lanjut' => ['nullable', 'string'],
            'hasil_evaluasi' => ['nullable', 'string'],
            'perubahan_pola' => ['sometimes', 'required', 'string', 'max:50'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
            'catatan_konteks' => ['nullable', 'string'],
        ]);

        $validated['updated_by'] = $request->user()->id;
        $log->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Log pemantauan BK berhasil diperbarui.',
            'data' => $log,
        ]);
    }

    /**
     * Hapus Log Kasus BK.
     */
    public function destroyLog(Request $request, BkCaseLog $log): JsonResponse
    {
        if ($res = $this->checkGuruBk($request)) return $res;

        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log pemantauan BK berhasil dihapus.',
        ]);
    }

    /**
     * Ekspor Rekapitulasi Pemantauan BK ke Excel.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DAFTAR PEMANTAUAN');

        // Header Style
        $sheet->setCellValue('A1', 'No.');
        $sheet->setCellValue('B1', 'Nama Siswa');
        $sheet->setCellValue('C1', 'Kelas');
        $sheet->setCellValue('D1', 'Tanggal Masuk');
        $sheet->setCellValue('E1', 'Sumber Awal');
        $sheet->setCellValue('F1', 'Fokus Pemantauan');
        $sheet->setCellValue('G1', 'Status Saat Ini');
        $sheet->setCellValue('H1', 'Review Terakhir');
        $sheet->setCellValue('I1', 'Review Berikutnya');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF15803D']], // Emerald-700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $cases = BkCounselingCase::with(['student', 'schoolClass'])->orderBy('tanggal_masuk', 'desc')->get();
        $row = 2;
        foreach ($cases as $idx => $c) {
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $c->student?->nama_lengkap ?? '-');
            $sheet->setCellValue('C' . $row, $c->schoolClass?->nama ?? '-');
            $sheet->setCellValue('D' . $row, $c->tanggal_masuk ? $c->tanggal_masuk->format('d/m/Y') : '-');
            $sheet->setCellValue('E' . $row, $c->sumber_awal);
            $sheet->setCellValue('F' . $row, $c->fokus_pemantauan);
            $sheet->setCellValue('G' . $row, $c->status_saat_ini);
            $sheet->setCellValue('H' . $row, $c->review_terakhir ? $c->review_terakhir->format('d/m/Y') : '-');
            $sheet->setCellValue('I' . $row, $c->review_berikutnya ? $c->review_berikutnya->format('d/m/Y') : '-');
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Rekap_Analisis_dan_Monitoring_BK_' . date('Ymd_His') . '.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
