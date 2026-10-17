<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ReportCard;
use App\Models\ReportCardExtracurricular;
use App\Models\ReportCardGrade;
use App\Models\SchoolClass;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Semester;
use App\Models\Subject;
use App\Services\ActivityLogService;
use App\Services\PeriodLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCardController extends Controller
{
    /**
     * Daftar 18 mata pelajaran standar SDI Bina Insan Mandiri (Kurikulum Merdeka).
     */
    public const DEFAULT_SUBJECTS = [
        ['nama' => 'Pendidikan Agama dan Budi Pekerti', 'kelompok' => 'Agama'],
        ['nama' => 'Pendidikan Pancasila', 'kelompok' => 'Umum'],
        ['nama' => 'Bahasa Indonesia', 'kelompok' => 'Umum'],
        ['nama' => 'Bahasa Jawa', 'kelompok' => 'Muatan Lokal'],
        ['nama' => 'Bahasa Inggris', 'kelompok' => 'Umum'],
        ['nama' => 'Bahasa Arab', 'kelompok' => 'Muatan Lokal'],
        ['nama' => 'Matematika', 'kelompok' => 'Umum'],
        ['nama' => 'Ilmu Pengetahuan Alam dan Sosial', 'kelompok' => 'Umum'],
        ['nama' => 'Pendidikan Jasmani Olahraga dan Kesehatan', 'kelompok' => 'Umum'],
        ['nama' => 'Seni Rupa', 'kelompok' => 'Umum'],
        ['nama' => 'Life Skill', 'kelompok' => 'Umum'],
        ['nama' => 'Gardening', 'kelompok' => 'Umum'],
        ['nama' => 'Crafting', 'kelompok' => 'Umum'],
        ['nama' => 'Ekonomi Jasa', 'kelompok' => 'Umum'],
        ['nama' => 'Fiqih', 'kelompok' => 'Agama'],
        ['nama' => "Qur'an Hadist", 'kelompok' => 'Agama'],
        ['nama' => 'Aqidah Akhlak', 'kelompok' => 'Agama'],
        ['nama' => 'SKI', 'kelompok' => 'Agama'],
    ];

    public const DEFAULT_EXTRACURRICULARS = [
        ['kegiatan' => 'Pramuka', 'predikat' => 'Baik', 'keterangan' => 'Aktif dalam kegiatan kepramukaan'],
        ['kegiatan' => 'Renang', 'predikat' => 'Baik', 'keterangan' => 'Menguasai teknik dasar renang'],
        ['kegiatan' => 'Pencak Silat', 'predikat' => 'Baik', 'keterangan' => 'Disiplin dan menguasai jurus dasar'],
        ['kegiatan' => 'Mading', 'predikat' => 'Baik', 'keterangan' => 'Kreatif dalam penyusunan majalah dinding'],
        ['kegiatan' => 'Publikasi', 'predikat' => 'Baik', 'keterangan' => 'Berpartisipasi aktif dalam dokumentasi'],
    ];

    /**
     * Pastikan hanya Staf Admin dan Kepala Sekolah yang memiliki akses.
     */
    protected function authorizeAdminOrHeadmaster(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Akses terbatas: Menu rapor saat ini hanya dapat diakses oleh Admin dan Kepala Sekolah.');
        }
    }

    /**
     * Ambil data rapor kelas dengan daftar siswa dan status pengerjaan.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdminOrHeadmaster($request);

        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['nullable', 'string', 'in:Gasal,Genap,1,2'],
        ]);

        $classId = (int) $request->query('class_id');
        $academicYearId = $request->query('academic_year_id');
        if (!$academicYearId) {
            $activeYear = AcademicYear::getActive();
            $academicYearId = $activeYear ? $activeYear->id : AcademicYear::latest()->value('id');
        }

        $semester = $request->query('semester', 'Gasal');
        if ($semester === '1') $semester = 'Gasal';
        if ($semester === '2') $semester = 'Genap';

        $schoolClass = SchoolClass::with(['academicYear', 'homeroomTeacher'])->findOrFail($classId);

        // Ambil semua siswa di kelas ini
        $students = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nis', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'foto_path', 'tempat_lahir', 'tgl_lahir']);

        // Ambil rapor yang sudah ada
        $reportCards = ReportCard::with(['grades', 'extracurriculars'])
            ->where('class_id', $classId)
            ->where('semester', $semester)
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->get()
            ->keyBy('student_id');

        $items = $students->map(function ($student, $index) use ($reportCards) {
            $rc = $reportCards->get($student->id);
            $totalGrades = $rc ? $rc->grades->count() : 0;
            $filledGrades = $rc ? $rc->grades->whereNotNull('nilai_akhir')->count() : 0;
            $avgGrade = $rc && $filledGrades > 0 ? round($rc->grades->whereNotNull('nilai_akhir')->avg('nilai_akhir'), 1) : null;

            return [
                'no_urut' => $index + 1,
                'student_id' => $student->id,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'nama_lengkap' => $student->nama_lengkap,
                'jenis_kelamin' => $student->jenis_kelamin,
                'tempat_lahir' => $student->tempat_lahir,
                'tgl_lahir' => $student->tgl_lahir?->format('Y-m-d'),
                'report_card_id' => $rc ? $rc->id : null,
                'status' => $rc ? $rc->status : 'belum_ada',
                'total_mapel' => $totalGrades,
                'mapel_terisi' => $filledGrades,
                'rata_rata' => $avgGrade,
                'kehadiran' => $rc ? [
                    'sakit' => $rc->sakit,
                    'izin' => $rc->izin,
                    'alpa' => $rc->alpa,
                ] : null,
                'catatan_wali_kelas' => $rc?->catatan_wali_kelas,
                'kokurikuler_tema' => $rc?->kokurikuler_tema,
                'updated_at' => $rc?->updated_at?->format('d/m/Y H:i'),
            ];
        });

        // Tentukan fase otomatis
        $level = (int) $schoolClass->tingkat;
        $fase = ($level <= 2) ? 'A' : (($level <= 4) ? 'B' : 'C');

        return response()->json([
            'success' => true,
            'message' => 'Data rapor kelas berhasil dimuat.',
            'data' => [
                'class' => [
                    'id' => $schoolClass->id,
                    'nama' => $schoolClass->nama,
                    'tingkat' => $schoolClass->tingkat,
                    'fase' => $fase,
                    'wali_kelas' => $schoolClass->homeroomTeacher?->nama_lengkap ?? '-',
                    'wali_kelas_nip' => $schoolClass->homeroomTeacher?->nip ?? '-',
                    'academic_year' => $schoolClass->academicYear?->nama ?? '-',
                ],
                'semester' => $semester,
                'academic_year_id' => $academicYearId,
                'total_students' => $students->count(),
                'completed_count' => $items->where('status', 'final')->count(),
                'draft_count' => $items->where('status', 'draft')->count(),
                'students' => $items,
            ],
        ]);
    }

    /**
     * Ambil template mata pelajaran default.
     */
    public function defaultSubjects(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => self::DEFAULT_SUBJECTS,
        ]);
    }

    /**
     * Inisialisasi massal rapor untuk semua siswa dalam satu kelas.
     */
    public function initializeClass(Request $request): JsonResponse
    {
        $this->authorizeAdminOrHeadmaster($request);

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'in:Gasal,Genap,1,2'],
            'tempat_tanggal' => ['nullable', 'string', 'max:100'],
            'overwrite_existing' => ['nullable', 'boolean'],
        ]);

        $classId = (int) $validated['class_id'];
        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($validated['semester'], ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $overwrite = $validated['overwrite_existing'] ?? false;

        $schoolClass = SchoolClass::with(['homeroomTeacher', 'academicYear'])->findOrFail($classId);
        $schoolProfile = SchoolProfile::with('headmaster')->first();

        // Tentukan fase
        $level = (int) $schoolClass->tingkat;
        $fase = ($level <= 2) ? 'A' : (($level <= 4) ? 'B' : 'C');

        $headmasterName = $schoolProfile?->headmaster?->nama_lengkap ?? 'Yuniatul Inayah, A.Md., S.Pd.';
        $headmasterNip = $schoolProfile?->headmaster?->nip ?? '197707202024211001';
        $homeroomName = $schoolClass->homeroomTeacher?->nama_lengkap ?? '-';
        $homeroomNip = $schoolClass->homeroomTeacher?->nip ?? '-';
        $tempatTanggal = $validated['tempat_tanggal'] ?? 'Purwokerto, 20 Desember 2025';

        $semModel = Semester::where('academic_year_id', $academicYearId)
            ->where('semester', strtolower($semester))
            ->first();
        if ($semModel) {
            PeriodLockService::validateSemesterNotLocked($semModel->id);
        }

        $students = Student::where('class_id', $classId)->where('status', 'aktif')->get();

        $createdCount = 0;
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                $rc = ReportCard::where('student_id', $student->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('semester', $semester)
                    ->first();

                if ($rc && !$overwrite) {
                    continue;
                }

                // Hitung presensi otomatis dari StudentAttendance
                $attCounts = StudentAttendance::where('student_id', $student->id)
                    ->where('class_id', $classId)
                    ->selectRaw("
                        COUNT(CASE WHEN status = 'sakit' THEN 1 END) as sakit,
                        COUNT(CASE WHEN status = 'izin' THEN 1 END) as izin,
                        COUNT(CASE WHEN status = 'alpa' THEN 1 END) as alpa
                    ")->first();

                if (!$rc) {
                    $rc = ReportCard::create([
                        'student_id' => $student->id,
                        'class_id' => $classId,
                        'academic_year_id' => $academicYearId,
                        'semester_id' => $semModel?->id,
                        'semester' => $semester,
                        'fase' => $fase,
                        'tempat_tanggal' => $tempatTanggal,
                        'wali_kelas_nama' => $homeroomName,
                        'wali_kelas_nip' => $homeroomNip,
                        'kepala_sekolah_nama' => $headmasterName,
                        'kepala_sekolah_nip' => $headmasterNip,
                        'kokurikuler_tema' => 'Sampahku bersih lingkunganku sehat',
                        'kokurikuler_dimensi' => 'Kolaborasi, Kemandirian, dan Kreativitas',
                        'kokurikuler_target' => 'Peserta didik mampu berkolaborasi menjaga kebersihan lingkungan sekolah dan memilah sampah secara mandiri.',
                        'kokurikuler_capaian' => 'Berkembang Sesuai Harapan',
                        'kokurikuler_deskripsi' => "Ananda {$student->nama_lengkap} telah menunjukkan sikap aktif dan bertanggung jawab dalam kegiatan kokurikuler.",
                        'sakit' => $attCounts->sakit ?? 0,
                        'izin' => $attCounts->izin ?? 0,
                        'alpa' => $attCounts->alpa ?? 0,
                        'catatan_wali_kelas' => 'Alhamdulillah, ananda menunjukkan perkembangan belajar dan adab yang baik. Pertahankan dan terus tingkatkan prestasimu.',
                        'status' => 'draft',
                        'created_by' => $request->user()->id,
                    ]);
                    $createdCount++;
                } else {
                    $rc->update([
                        'tempat_tanggal' => $tempatTanggal,
                        'wali_kelas_nama' => $homeroomName,
                        'wali_kelas_nip' => $homeroomNip,
                        'kepala_sekolah_nama' => $headmasterName,
                        'kepala_sekolah_nip' => $headmasterNip,
                        'updated_by' => $request->user()->id,
                    ]);
                    $updatedCount++;
                }

                // Inisialisasi 18 mata pelajaran jika belum ada
                if ($rc->grades()->count() === 0) {
                    $urutan = 1;
                    foreach (self::DEFAULT_SUBJECTS as $subj) {
                        ReportCardGrade::create([
                            'report_card_id' => $rc->id,
                            'nama_mapel' => $subj['nama'],
                            'kelompok' => $subj['kelompok'],
                            'nilai_akhir' => null,
                            'capaian_kompetensi' => null,
                            'urutan' => $urutan++,
                        ]);
                    }
                }

                // Inisialisasi ekstrakurikuler jika belum ada
                if ($rc->extracurriculars()->count() === 0) {
                    $urutan = 1;
                    foreach (self::DEFAULT_EXTRACURRICULARS as $ekstra) {
                        ReportCardExtracurricular::create([
                            'report_card_id' => $rc->id,
                            'kegiatan' => $ekstra['kegiatan'],
                            'predikat' => $ekstra['predikat'],
                            'keterangan' => $ekstra['keterangan'],
                            'urutan' => $urutan++,
                        ]);
                    }
                }
            }

            DB::commit();

            ActivityLogService::log(
                'Inisialisasi Rapor Kelas',
                "Menginisialisasi rapor kelas {$schoolClass->nama} semester {$semester} ({$createdCount} dibuat, {$updatedCount} diperbarui)",
                'ReportCard',
                $classId
            );

            return response()->json([
                'success' => true,
                'message' => "Inisialisasi selesai. {$createdCount} rapor siswa baru dibuat, {$updatedCount} diperbarui.",
                'data' => [
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menginisialisasi rapor kelas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ambil detail rapor siswa lengkap untuk tampilan form / edit / view.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdminOrHeadmaster($request);

        $reportCard = ReportCard::with([
            'student.religion',
            'student.schoolClass',
            'schoolClass.homeroomTeacher',
            'academicYear',
            'grades',
            'extracurriculars',
        ])->findOrFail($id);

        $schoolProfile = SchoolProfile::first();

        return response()->json([
            'success' => true,
            'message' => 'Detail rapor berhasil dimuat.',
            'data' => [
                'report_card' => $reportCard,
                'school_profile' => $schoolProfile ? [
                    'nama_sekolah' => $schoolProfile->nama_sekolah ?? 'SD Islam Bina Insan Mandiri Purwokerto',
                    'npsn' => $schoolProfile->npsn ?? '70036695',
                    'alamat' => $schoolProfile->alamat_lengkap ?? 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem Kec. Purwokerto Selatan Kab. Banyumas Kode pos 53144',
                    'logo_path' => $schoolProfile->logo_path,
                ] : null,
            ],
        ]);
    }

    /**
     * Ambil atau inisialisasi rapor untuk satu siswa tertentu.
     */
    public function getOrCreateForStudent(Request $request): JsonResponse
    {
        $this->authorizeAdminOrHeadmaster($request);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'semester' => ['nullable', 'string', 'in:Gasal,Genap,1,2'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
        ]);

        $student = Student::with('schoolClass.homeroomTeacher')->findOrFail($validated['student_id']);
        $classId = $student->class_id;
        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semModel = Semester::where('academic_year_id', $academicYearId)
            ->where('semester', strtolower($semester))
            ->first();

        $rc = ReportCard::with(['grades', 'extracurriculars', 'student', 'schoolClass', 'academicYear'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->first();

        if (!$rc) {
            if ($semModel) {
                PeriodLockService::validateSemesterNotLocked($semModel->id);
            }

            $schoolClass = $student->schoolClass;
            $level = $schoolClass ? (int) $schoolClass->tingkat : 1;
            $fase = ($level <= 2) ? 'A' : (($level <= 4) ? 'B' : 'C');
            $schoolProfile = SchoolProfile::with('headmaster')->first();

            $attCounts = StudentAttendance::where('student_id', $student->id)
                ->where('class_id', $classId)
                ->selectRaw("
                    COUNT(CASE WHEN status = 'sakit' THEN 1 END) as sakit,
                    COUNT(CASE WHEN status = 'izin' THEN 1 END) as izin,
                    COUNT(CASE WHEN status = 'alpa' THEN 1 END) as alpa
                ")->first();

            $rc = ReportCard::create([
                'student_id' => $student->id,
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semModel?->id,
                'semester' => $semester,
                'fase' => $fase,
                'tempat_tanggal' => 'Purwokerto, 20 Desember 2025',
                'wali_kelas_nama' => $schoolClass?->homeroomTeacher?->nama_lengkap ?? '-',
                'wali_kelas_nip' => $schoolClass?->homeroomTeacher?->nip ?? '-',
                'kepala_sekolah_nama' => $schoolProfile?->headmaster?->nama_lengkap ?? 'Yuniatul Inayah, A.Md., S.Pd.',
                'kepala_sekolah_nip' => $schoolProfile?->headmaster?->nip ?? '197707202024211001',
                'kokurikuler_tema' => 'Sampahku bersih lingkunganku sehat',
                'kokurikuler_dimensi' => 'Kolaborasi, Kemandirian, dan Kreativitas',
                'kokurikuler_target' => 'Peserta didik mampu berkolaborasi menjaga kebersihan lingkungan sekolah dan memilah sampah secara mandiri.',
                'kokurikuler_capaian' => 'Berkembang Sesuai Harapan',
                'kokurikuler_deskripsi' => "Ananda {$student->nama_lengkap} telah menunjukkan sikap aktif dalam kegiatan kokurikuler.",
                'sakit' => $attCounts->sakit ?? 0,
                'izin' => $attCounts->izin ?? 0,
                'alpa' => $attCounts->alpa ?? 0,
                'catatan_wali_kelas' => 'Pertahankan prestasimu dan terus bersemangat dalam menuntut ilmu.',
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            $urutan = 1;
            foreach (self::DEFAULT_SUBJECTS as $subj) {
                ReportCardGrade::create([
                    'report_card_id' => $rc->id,
                    'nama_mapel' => $subj['nama'],
                    'kelompok' => $subj['kelompok'],
                    'nilai_akhir' => null,
                    'capaian_kompetensi' => null,
                    'urutan' => $urutan++,
                ]);
            }

            $urutan = 1;
            foreach (self::DEFAULT_EXTRACURRICULARS as $ekstra) {
                ReportCardExtracurricular::create([
                    'report_card_id' => $rc->id,
                    'kegiatan' => $ekstra['kegiatan'],
                    'predikat' => $ekstra['predikat'],
                    'keterangan' => $ekstra['keterangan'],
                    'urutan' => $urutan++,
                ]);
            }

            $rc->load(['grades', 'extracurriculars', 'student', 'schoolClass', 'academicYear']);
        }

        return response()->json([
            'success' => true,
            'data' => $rc,
        ]);
    }

    /**
     * Simpan / Perbarui Rapor Siswa.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdminOrHeadmaster($request);

        $reportCard = ReportCard::findOrFail($id);

        $validated = $request->validate([
            'fase' => ['nullable', 'string', 'max:10'],
            'tempat_tanggal' => ['nullable', 'string', 'max:100'],
            'tanggal_rapor' => ['nullable', 'date'],
            'wali_kelas_nama' => ['nullable', 'string', 'max:150'],
            'wali_kelas_nip' => ['nullable', 'string', 'max:50'],
            'kepala_sekolah_nama' => ['nullable', 'string', 'max:150'],
            'kepala_sekolah_nip' => ['nullable', 'string', 'max:50'],
            'kokurikuler_tema' => ['nullable', 'string', 'max:255'],
            'kokurikuler_dimensi' => ['nullable', 'string', 'max:150'],
            'kokurikuler_target' => ['nullable', 'string', 'max:255'],
            'kokurikuler_capaian' => ['nullable', 'string', 'max:100'],
            'kokurikuler_deskripsi' => ['nullable', 'string'],
            'sakit' => ['nullable', 'integer', 'min:0'],
            'izin' => ['nullable', 'integer', 'min:0'],
            'alpa' => ['nullable', 'integer', 'min:0'],
            'catatan_wali_kelas' => ['nullable', 'string'],
            'tanggapan_orang_tua' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,final'],
            'revision_notes' => ['nullable', 'string', 'max:500'],
            'grades' => ['nullable', 'array'],
            'grades.*.id' => ['nullable', 'integer'],
            'grades.*.nama_mapel' => ['required_with:grades', 'string', 'max:150'],
            'grades.*.kelompok' => ['nullable', 'string', 'max:50'],
            'grades.*.nilai_akhir' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.capaian_kompetensi' => ['nullable', 'string'],
            'grades.*.urutan' => ['nullable', 'integer'],
            'extracurriculars' => ['nullable', 'array'],
            'extracurriculars.*.id' => ['nullable', 'integer'],
            'extracurriculars.*.kegiatan' => ['required_with:extracurriculars', 'string', 'max:150'],
            'extracurriculars.*.predikat' => ['nullable', 'string', 'max:50'],
            'extracurriculars.*.keterangan' => ['nullable', 'string', 'max:255'],
            'extracurriculars.*.urutan' => ['nullable', 'integer'],
        ]);

        $sem = $reportCard->semesterModel;
        if ($sem && $sem->status === 'terkunci' && $sem->isReopened()) {
            if ($reportCard->status === 'final') {
                $revisionNotes = $request->input('revision_notes');
                if (empty($revisionNotes)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Catatan revisi (revision_notes) wajib diisi saat mengubah rapor berstatus final pada masa pembukaan sementara semester.',
                    ], 422);
                }
                $reportCard->createRevision($request->user()->id, $revisionNotes);
            }
        } else {
            PeriodLockService::validateSemesterNotLocked($reportCard->semester_id);
        }

        DB::beginTransaction();
        try {
            // Update ReportCard info
            $reportCard->update([
                'fase' => $validated['fase'] ?? $reportCard->fase,
                'tempat_tanggal' => $validated['tempat_tanggal'] ?? $reportCard->tempat_tanggal,
                'tanggal_rapor' => $validated['tanggal_rapor'] ?? $reportCard->tanggal_rapor,
                'wali_kelas_nama' => $validated['wali_kelas_nama'] ?? $reportCard->wali_kelas_nama,
                'wali_kelas_nip' => $validated['wali_kelas_nip'] ?? $reportCard->wali_kelas_nip,
                'kepala_sekolah_nama' => $validated['kepala_sekolah_nama'] ?? $reportCard->kepala_sekolah_nama,
                'kepala_sekolah_nip' => $validated['kepala_sekolah_nip'] ?? $reportCard->kepala_sekolah_nip,
                'kokurikuler_tema' => $validated['kokurikuler_tema'] ?? $reportCard->kokurikuler_tema,
                'kokurikuler_dimensi' => $validated['kokurikuler_dimensi'] ?? $reportCard->kokurikuler_dimensi,
                'kokurikuler_target' => $validated['kokurikuler_target'] ?? $reportCard->kokurikuler_target,
                'kokurikuler_capaian' => $validated['kokurikuler_capaian'] ?? $reportCard->kokurikuler_capaian,
                'kokurikuler_deskripsi' => $validated['kokurikuler_deskripsi'] ?? $reportCard->kokurikuler_deskripsi,
                'sakit' => $validated['sakit'] ?? $reportCard->sakit,
                'izin' => $validated['izin'] ?? $reportCard->izin,
                'alpa' => $validated['alpa'] ?? $reportCard->alpa,
                'catatan_wali_kelas' => $validated['catatan_wali_kelas'] ?? $reportCard->catatan_wali_kelas,
                'tanggapan_orang_tua' => $validated['tanggapan_orang_tua'] ?? $reportCard->tanggapan_orang_tua,
                'status' => $validated['status'] ?? $reportCard->status,
                'updated_by' => $request->user()->id,
            ]);

            // Simpan / update grades
            if (isset($validated['grades'])) {
                $keptGradeIds = [];
                foreach ($validated['grades'] as $gIndex => $gData) {
                    $gradeId = $gData['id'] ?? null;
                    if ($gradeId) {
                        $grade = ReportCardGrade::where('report_card_id', $reportCard->id)->where('id', $gradeId)->first();
                        if ($grade) {
                            $grade->update([
                                'nama_mapel' => $gData['nama_mapel'],
                                'kelompok' => $gData['kelompok'] ?? 'Umum',
                                'nilai_akhir' => $gData['nilai_akhir'] !== '' ? $gData['nilai_akhir'] : null,
                                'capaian_kompetensi' => $gData['capaian_kompetensi'] ?? null,
                                'urutan' => $gData['urutan'] ?? ($gIndex + 1),
                            ]);
                            $keptGradeIds[] = $grade->id;
                            continue;
                        }
                    }

                    $newGrade = ReportCardGrade::create([
                        'report_card_id' => $reportCard->id,
                        'nama_mapel' => $gData['nama_mapel'],
                        'kelompok' => $gData['kelompok'] ?? 'Umum',
                        'nilai_akhir' => $gData['nilai_akhir'] !== '' ? $gData['nilai_akhir'] : null,
                        'capaian_kompetensi' => $gData['capaian_kompetensi'] ?? null,
                        'urutan' => $gData['urutan'] ?? ($gIndex + 1),
                    ]);
                    $keptGradeIds[] = $newGrade->id;
                }
                // Hapus mapel yang dihapus pengguna
                ReportCardGrade::where('report_card_id', $reportCard->id)
                    ->whereNotIn('id', $keptGradeIds)
                    ->delete();
            }

            // Simpan / update extracurriculars
            if (isset($validated['extracurriculars'])) {
                $keptEkstraIds = [];
                foreach ($validated['extracurriculars'] as $eIndex => $eData) {
                    $ekstraId = $eData['id'] ?? null;
                    if ($ekstraId) {
                        $ekstra = ReportCardExtracurricular::where('report_card_id', $reportCard->id)->where('id', $ekstraId)->first();
                        if ($ekstra) {
                            $ekstra->update([
                                'kegiatan' => $eData['kegiatan'],
                                'predikat' => $eData['predikat'] ?? 'Baik',
                                'keterangan' => $eData['keterangan'] ?? null,
                                'urutan' => $eData['urutan'] ?? ($eIndex + 1),
                            ]);
                            $keptEkstraIds[] = $ekstra->id;
                            continue;
                        }
                    }

                    $newEkstra = ReportCardExtracurricular::create([
                        'report_card_id' => $reportCard->id,
                        'kegiatan' => $eData['kegiatan'],
                        'predikat' => $eData['predikat'] ?? 'Baik',
                        'keterangan' => $eData['keterangan'] ?? null,
                        'urutan' => $eData['urutan'] ?? ($eIndex + 1),
                    ]);
                    $keptEkstraIds[] = $newEkstra->id;
                }
                ReportCardExtracurricular::where('report_card_id', $reportCard->id)
                    ->whereNotIn('id', $keptEkstraIds)
                    ->delete();
            }

            DB::commit();

            $reportCard->load(['grades', 'extracurriculars', 'student']);

            ActivityLogService::log(
                'Update Rapor Siswa',
                "Memperbarui rapor siswa {$reportCard->student->nama_lengkap} (Status: {$reportCard->status})",
                'ReportCard',
                $reportCard->id
            );

            return response()->json([
                'success' => true,
                'message' => "Rapor {$reportCard->student->nama_lengkap} berhasil disimpan.",
                'data' => $reportCard,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan rapor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sinkronkan presensi siswa secara instan dari StudentAttendance.
     */
    public function syncAttendance(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdminOrHeadmaster($request);

        $reportCard = ReportCard::with('student')->findOrFail($id);
        PeriodLockService::validateSemesterNotLocked($reportCard->semester_id);

        $attCounts = StudentAttendance::where('student_id', $reportCard->student_id)
            ->where('class_id', $reportCard->class_id)
            ->selectRaw("
                COUNT(CASE WHEN status = 'sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN status = 'izin' THEN 1 END) as izin,
                COUNT(CASE WHEN status = 'alpa' THEN 1 END) as alpa
            ")->first();

        $reportCard->update([
            'sakit' => $attCounts->sakit ?? 0,
            'izin' => $attCounts->izin ?? 0,
            'alpa' => $attCounts->alpa ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Presensi {$reportCard->student->nama_lengkap} berhasil disinkronkan: S={$reportCard->sakit}, I={$reportCard->izin}, A={$reportCard->alpa}.",
            'data' => [
                'sakit' => $reportCard->sakit,
                'izin' => $reportCard->izin,
                'alpa' => $reportCard->alpa,
            ],
        ]);
    }

    /**
     * Export data rapor satu kelas ke format Excel yang identik dengan template sekolah.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeAdminOrHeadmaster($request);

        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['nullable', 'string', 'in:Gasal,Genap,1,2'],
        ]);

        $classId = (int) $request->query('class_id');
        $academicYearId = $request->query('academic_year_id') ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($request->query('semester', 'Gasal'), ['1', 'Gasal']) ? 'Gasal' : 'Genap';

        $schoolClass = SchoolClass::with(['academicYear', 'homeroomTeacher'])->findOrFail($classId);
        $schoolProfile = SchoolProfile::first();

        $students = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $reportCards = ReportCard::with(['grades', 'extracurriculars'])
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->get()
            ->keyBy('student_id');

        $spreadsheet = new Spreadsheet();

        // 1. Sheet DATABASE
        $sheetDb = $spreadsheet->getActiveSheet();
        $sheetDb->setTitle('DATABASE');
        $sheetDb->setCellValue('A1', 'DATABASE RAPOR SDI BINA INSAN MANDIRI');
        $sheetDb->setCellValue('A3', 'Nama Sekolah');
        $sheetDb->setCellValue('C3', $schoolProfile?->nama_sekolah ?? 'SD Islam Bina Insan Mandiri Purwokerto');
        $sheetDb->setCellValue('A4', 'Kelas');
        $sheetDb->setCellValue('C4', $schoolClass->nama);
        $sheetDb->setCellValue('A5', 'Fase');
        $level = (int) $schoolClass->tingkat;
        $sheetDb->setCellValue('C5', ($level <= 2) ? 'A' : (($level <= 4) ? 'B' : 'C'));
        $sheetDb->setCellValue('A6', 'Tahun Ajaran');
        $sheetDb->setCellValue('C6', $schoolClass->academicYear?->nama ?? '2025/2026');
        $sheetDb->setCellValue('A7', 'Semester');
        $sheetDb->setCellValue('C7', $semester);
        $sheetDb->setCellValue('A8', 'Kepala Sekolah');
        $sheetDb->setCellValue('C8', 'Yuniatul Inayah, A.Md., S.Pd.');
        $sheetDb->setCellValue('A9', 'Wali Kelas');
        $sheetDb->setCellValue('C9', $schoolClass->homeroomTeacher?->nama_lengkap ?? '-');

        // 2. Sheet DATA INTRAKURIKULER
        $sheetIntra = $spreadsheet->createSheet();
        $sheetIntra->setTitle('DATA INTRAKURIKULER');
        $sheetIntra->setCellValue('A1', 'DATA INTRAKURIKULER & NILAI SISWA');
        $sheetIntra->setCellValue('A3', 'Kelas: ' . $schoolClass->nama . ' | Semester: ' . $semester);

        // Header tabel intrakurikuler
        $sheetIntra->setCellValue('A5', 'No');
        $sheetIntra->setCellValue('B5', 'NIS');
        $sheetIntra->setCellValue('C5', 'NISN');
        $sheetIntra->setCellValue('D5', 'Nama Murid');

        $colIdx = 5; // Column E
        foreach (self::DEFAULT_SUBJECTS as $subj) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheetIntra->setCellValue($colLetter . '5', $subj['nama']);
            $colIdx++;
        }

        // Tulis baris siswa
        $rowIdx = 6;
        foreach ($students as $sIdx => $st) {
            $rc = $reportCards->get($st->id);
            $sheetIntra->setCellValue('A' . $rowIdx, $sIdx + 1);
            $sheetIntra->setCellValue('B' . $rowIdx, $st->nis);
            $sheetIntra->setCellValue('C' . $rowIdx, $st->nisn);
            $sheetIntra->setCellValue('D' . $rowIdx, $st->nama_lengkap);

            $colIdx = 5;
            foreach (self::DEFAULT_SUBJECTS as $subj) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $gradeVal = null;
                if ($rc) {
                    $foundGrade = $rc->grades->firstWhere('nama_mapel', $subj['nama']);
                    $gradeVal = $foundGrade?->nilai_akhir;
                }
                $sheetIntra->setCellValue($colLetter . $rowIdx, $gradeVal);
                $colIdx++;
            }
            $rowIdx++;
        }

        // 3. Sheet KEHADIRAN & EKSTRA
        $sheetEkstra = $spreadsheet->createSheet();
        $sheetEkstra->setTitle('KEHADIRAN & EKSTRA');
        $sheetEkstra->setCellValue('A1', 'DATA KEHADIRAN DAN EKSTRAKURIKULER');
        $sheetEkstra->setCellValue('A4', 'No');
        $sheetEkstra->setCellValue('B4', 'NISN');
        $sheetEkstra->setCellValue('C4', 'Nama Murid');
        $sheetEkstra->setCellValue('D4', 'Sakit');
        $sheetEkstra->setCellValue('E4', 'Izin');
        $sheetEkstra->setCellValue('F4', 'Alpa');
        $sheetEkstra->setCellValue('G4', 'Catatan Wali Kelas');

        $rowIdx = 5;
        foreach ($students as $sIdx => $st) {
            $rc = $reportCards->get($st->id);
            $sheetEkstra->setCellValue('A' . $rowIdx, $sIdx + 1);
            $sheetEkstra->setCellValue('B' . $rowIdx, $st->nisn);
            $sheetEkstra->setCellValue('C' . $rowIdx, $st->nama_lengkap);
            $sheetEkstra->setCellValue('D' . $rowIdx, $rc?->sakit ?? 0);
            $sheetEkstra->setCellValue('E' . $rowIdx, $rc?->izin ?? 0);
            $sheetEkstra->setCellValue('F' . $rowIdx, $rc?->alpa ?? 0);
            $sheetEkstra->setCellValue('G' . $rowIdx, $rc?->catatan_wali_kelas ?? '');
            $rowIdx++;
        }

        $classNameClean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $schoolClass->nama);
        $fileName = "RAPOR_{$classNameClean}_{$semester}_" . date('Ymd_His') . ".xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Import Nilai Rapor dari file Excel (format yang diunduh atau format database sekolah).
     */
    public function importExcel(Request $request): JsonResponse
    {
        $this->authorizeAdminOrHeadmaster($request);

        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'in:Gasal,Genap,1,2'],
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $classId = (int) $request->input('class_id');
        $academicYearId = $request->input('academic_year_id') ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($request->input('semester'), ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $file = $request->file('file');

        $semModel = Semester::where('academic_year_id', $academicYearId)
            ->where('semester', strtolower($semester))
            ->first();
        if ($semModel) {
            PeriodLockService::validateSemesterNotLocked($semModel->id);
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());

            // Cari sheet nilai: "DATA INTRAKURIKULER" atau sheet pertama
            $sheet = $spreadsheet->getSheetByName('DATA INTRAKURIKULER');
            if (!$sheet) {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // Cari baris header yang memuat NISN / Nama
            $headerRow = 5;
            for ($r = 1; $r <= min(10, $highestRow); $r++) {
                $valB = (string) $sheet->getCell('B' . $r)->getValue();
                $valC = (string) $sheet->getCell('C' . $r)->getValue();
                $valD = (string) $sheet->getCell('D' . $r)->getValue();
                if (stripos($valB, 'nis') !== false || stripos($valC, 'nis') !== false || stripos($valD, 'nama') !== false) {
                    $headerRow = $r;
                    break;
                }
            }

            // Map header column ke nama mapel
            $subjectColMap = [];
            for ($c = 5; $c <= $highestColIndex; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $subjName = trim((string) $sheet->getCell($colLetter . $headerRow)->getValue());
                if (!empty($subjName)) {
                    $subjectColMap[$colLetter] = $subjName;
                }
            }

            $updatedStudents = 0;
            $updatedGrades = 0;

            DB::beginTransaction();

            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $nisn = trim((string) $sheet->getCell('C' . $row)->getValue());
                $nis = trim((string) $sheet->getCell('B' . $row)->getValue());
                $nama = trim((string) $sheet->getCell('D' . $row)->getValue());

                if (empty($nama) && empty($nisn) && empty($nis)) {
                    continue;
                }

                // Cari siswa berdasarkan NISN, NIS, atau Nama
                $student = null;
                if (!empty($nisn) && $nisn !== '0') {
                    $student = Student::where('class_id', $classId)->where('nisn', $nisn)->first();
                }
                if (!$student && !empty($nis) && $nis !== '0') {
                    $student = Student::where('class_id', $classId)->where('nis', $nis)->first();
                }
                if (!$student && !empty($nama)) {
                    $student = Student::where('class_id', $classId)->where('nama_lengkap', 'like', "%{$nama}%")->first();
                }

                if (!$student) {
                    continue;
                }

                // Cari atau buat Rapor
                $rc = ReportCard::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYearId,
                        'semester' => $semester,
                    ],
                    [
                        'class_id' => $classId,
                        'status' => 'draft',
                        'tempat_tanggal' => 'Purwokerto, 20 Desember 2025',
                        'created_by' => $request->user()->id,
                    ]
                );

                $updatedStudents++;

                // Update nilai tiap mata pelajaran
                foreach ($subjectColMap as $colLetter => $subjName) {
                    $scoreVal = $sheet->getCell($colLetter . $row)->getValue();
                    if ($scoreVal !== null && trim((string)$scoreVal) !== '') {
                        $numericVal = is_numeric($scoreVal) ? (float) $scoreVal : null;
                        if ($numericVal !== null) {
                            $grade = ReportCardGrade::firstOrNew([
                                'report_card_id' => $rc->id,
                                'nama_mapel' => $subjName,
                            ]);
                            $grade->nilai_akhir = $numericVal;
                            if (!$grade->exists) {
                                $grade->kelompok = 'Umum';
                            }
                            $grade->save();
                            $updatedGrades++;
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Import berhasil. {$updatedStudents} rapor siswa terdeteksi, {$updatedGrades} nilai mata pelajaran diperbarui.",
                'data' => [
                    'updated_students' => $updatedStudents,
                    'updated_grades' => $updatedGrades,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimpor file Excel: ' . $e->getMessage(),
            ], 422);
        }
    }
}
