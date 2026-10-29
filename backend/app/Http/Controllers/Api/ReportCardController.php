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
     * 18 mata pelajaran standar SDI Bina Insan Mandiri (Kurikulum Merdeka).
     */
    public const DEFAULT_SUBJECTS = [
        ['nama' => 'IPS', 'kelompok' => 'Umum'],
        ['nama' => 'Pendidikan Pancasila', 'kelompok' => 'Umum'],
        ['nama' => 'Bahasa Indonesia', 'kelompok' => 'Umum'],
        ['nama' => 'Bahasa Inggris', 'kelompok' => 'Umum'],
        ['nama' => 'Bahasa Arab', 'kelompok' => 'Umum'],
        ['nama' => 'Matematika', 'kelompok' => 'Umum'],
        ['nama' => 'Ilmu Pengetahuan Alam', 'kelompok' => 'Umum'],
        ['nama' => 'Pendidikan Jasmani Olahraga dan Kesehatan', 'kelompok' => 'Umum'],
        ['nama' => 'Life Skill', 'kelompok' => 'Umum'],
        ['nama' => 'Gardening', 'kelompok' => 'Umum'],
        ['nama' => 'Crafting', 'kelompok' => 'Umum'],
        ['nama' => 'Ekonomi Jasa', 'kelompok' => 'Umum'],
        ['nama' => 'Fiqih', 'kelompok' => 'Agama'],
        ['nama' => "Qur'an Hadist", 'kelompok' => 'Agama'],
        ['nama' => 'Aqidah Akhlak', 'kelompok' => 'Agama'],
        ['nama' => 'SKI', 'kelompok' => 'Agama'],
        ['nama' => 'Seni Rupa', 'kelompok' => 'Umum'],
        ['nama' => 'Bahasa Jawa', 'kelompok' => 'Muatan Lokal'],
    ];

    /**
     * 5 Klaster Tema Rapor Triwulan Tematik Terpadu BIM (Abrisam 1).
     */
    public const DEFAULT_TEMATIC_SUBJECTS = [
        [
            'tema' => 'Mathematics and Sciences',
            'nama' => 'Matematika dan Ilmu Pengetahuan Alam',
            'kelompok' => 'Tematik',
            'default_capaian' => null,
        ],
        [
            'tema' => 'Art, Language, and Indonesian Cultures',
            'nama' => 'Seni Budaya Bahasa (Bahasa Jawa, Bahasa Indonesia, Bahasa Inggris, dan Bahasa Arab)',
            'kelompok' => 'Tematik',
            'default_capaian' => null,
        ],
        [
            'tema' => 'Social Sciences and Citizenship',
            'nama' => 'Ilmu Pengetahuan Sosial, Pendidikan Pancasila dan Kewarganegaraan',
            'kelompok' => 'Tematik',
            'default_capaian' => null,
        ],
        [
            'tema' => 'Physical Education, Health and Life Skills',
            'nama' => 'Pendidikan Jasmani Olahraga dan Kesehatan, Keterampilan Hidup',
            'kelompok' => 'Tematik',
            'default_capaian' => null,
        ],
        [
            'tema' => 'Islamic Education',
            'nama' => "Quran Hadits\nFiqih\nAkidah Akhlak\nSejarah Kebudayaan Islam",
            'kelompok' => 'Tematik',
            'default_capaian' => null,
        ],
    ];

    /**
     * 17 Butir Laporan Perkembangan Penguatan Karakter & Life Skill (Abrisam 2).
     */
    public const DEFAULT_CHARACTER_ITEMS = [
        // 1. Aspek Sikap (9 Butir)
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Motivasi diri (meyakinkan kita bahwa kita mampu, berpikir positif, visioner)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Mengendalikan emosi (mengelola emosi, mengendalikan diri, dan hati)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Empati, kepedulian pada lingkungan sosial dan lingkungan alam',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Membangun fisik dan mental yang kuat',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Akhlakul Karimah (memiliki perilaku dan sikap yang baik/Islami)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Disiplin Diri (Self Discipline - melakukan tanpa diperintah)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Manajemen waktu (mengelola waktu, scheduling)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Konsisten (istiqomah)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Sikap',
            'kelompok_karakter' => 'Emosi & Karakter yang Kuat (Emotional & Spiritual Quotient)',
            'sub_aspek' => 'Fleksibilitas (kemampuan beradaptasi)',
            'default_capaian' => null,
            'predikat' => null,
        ],

        // 2. Aspek Pengetahuan (7 Butir)
        [
            'aspek' => 'Aspek Pengetahuan',
            'kelompok_karakter' => 'Belajar Efektif (Effective Learning)',
            'sub_aspek' => 'Pembelajaran efektif (belajar di mana saja, kapan saja, dan dengan siapa saja; semua adalah ilmu)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Pengetahuan',
            'kelompok_karakter' => 'Belajar Efektif (Effective Learning)',
            'sub_aspek' => 'Membaca buku (membiasakan untuk membaca buku; jendela ilmu)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Pengetahuan',
            'kelompok_karakter' => 'Belajar Efektif (Effective Learning)',
            'sub_aspek' => 'Berfikir kritis (banyak literasi dan referensi; observasi, analisis, evaluasi, dan tindak lanjut)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Pengetahuan',
            'kelompok_karakter' => 'Komunikasi (Communication)',
            'sub_aspek' => 'Cara komunikasi yang efektif (mampu berkomunikasi dengan baik)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Pengetahuan',
            'kelompok_karakter' => 'Komunikasi (Communication)',
            'sub_aspek' => 'Persuasi (meyakinkan orang lain/presentasi)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Pengetahuan',
            'kelompok_karakter' => 'Komunikasi (Communication)',
            'sub_aspek' => 'Membangun networking (silaturahmi, membangun hubungan dan bersosialisasi)',
            'default_capaian' => null,
            'predikat' => null,
        ],
        [
            'aspek' => 'Aspek Pengetahuan',
            'kelompok_karakter' => 'Komunikasi (Communication)',
            'sub_aspek' => 'Belajar bahasa asing (berkomunikasi dan bersosialisasi dengan banyak orang dan banyak negara)',
            'default_capaian' => null,
            'predikat' => null,
        ],

        // 3. Aspek Keterampilan (1 Butir)
        [
            'aspek' => 'Aspek Keterampilan',
            'kelompok_karakter' => 'Penguasaan Teknologi dan Keterampilan Hidup (Technology Mastery and Life Skill)',
            'sub_aspek' => 'Kecakapan Hidup (bertanam, berkebun, beternak, memasak, dan membuat peralatan sederhana)',
            'default_capaian' => null,
            'predikat' => null,
        ],
    ];

    public const DEFAULT_EXTRACURRICULARS = [
        ['kegiatan' => 'Pramuka', 'predikat' => 'Baik', 'keterangan' => 'Aktif dalam kegiatan kepramukaan siaga'],
        ['kegiatan' => 'Renang', 'predikat' => 'Baik', 'keterangan' => 'Menguasai teknik dasar renang dan pengenalan air'],
        ['kegiatan' => 'Pencak Silat', 'predikat' => 'Baik', 'keterangan' => 'Disiplin dan menguasai salam serta jurus dasar'],
        ['kegiatan' => 'Mading', 'predikat' => 'Baik', 'keterangan' => 'Kreatif dalam penyusunan karya majalah dinding'],
        ['kegiatan' => 'Publikasi & Public Speaking', 'predikat' => 'Baik', 'keterangan' => 'Berani tampil di depan umum dan berpartisipasi aktif'],
    ];

    public const TEMATIC_EXTRACURRICULARS = [
        ['kegiatan' => 'Pramuka', 'predikat' => null, 'keterangan' => null],
        ['kegiatan' => 'Renang', 'predikat' => '-', 'keterangan' => '-'],
        ['kegiatan' => 'Pencak Silat', 'predikat' => null, 'keterangan' => null],
        ['kegiatan' => 'Mading', 'predikat' => null, 'keterangan' => null],
        ['kegiatan' => 'Public Speaking', 'predikat' => null, 'keterangan' => null],
    ];

    /**
     * Otorisasi hak akses menu rapor:
     * - Admin & Kepala Sekolah: akses ke semua kelas.
     * - Guru: hanya ke kelas yang diampunya sebagai Wali Kelas.
     */
    protected function authorizeAccess(Request $request, ?int $classId = null): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTeacher() && $user->teacher) {
            if ($classId) {
                $isHomeroom = $user->teacher->homeroomClasses()->where('id', $classId)->exists();
                if (!$isHomeroom) {
                    abort(403, 'Akses ditolak: Anda hanya dapat mengakses rapor untuk kelas yang Anda ampu sebagai Wali Kelas.');
                }
            } else {
                $hasHomeroom = $user->teacher->homeroomClasses()->exists();
                if (!$hasHomeroom) {
                    abort(403, 'Akses terbatas: Anda belum terdaftar sebagai Wali Kelas pada rombel aktif.');
                }
            }
            return;
        }

        abort(403, 'Akses terbatas: Menu rapor hanya dapat diakses oleh Wali Kelas, Kepala Sekolah, dan Staf Admin.');
    }

    /**
     * Otorisasi khusus validator (Kepala Sekolah & Staf Admin).
     */
    protected function authorizeValidator(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Hanya Kepala Sekolah dan Admin yang memiliki wewenang untuk memvalidasi rapor.');
        }
    }

    /**
     * Ambil data rapor kelas dengan filter jenis rapor dan periode.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['nullable', 'string', 'in:Gasal,Genap,1,2'],
            'jenis_rapor' => ['nullable', 'string', 'in:kurikulum_merdeka,triwulan_tematik,karakter_lifeskill'],
            'periode' => ['nullable', 'string', 'max:50'],
        ]);

        $classId = (int) $request->query('class_id');
        $this->authorizeAccess($request, $classId);

        $academicYearId = $request->query('academic_year_id');
        if (!$academicYearId) {
            $activeYear = AcademicYear::getActive();
            $academicYearId = $activeYear ? $activeYear->id : AcademicYear::latest()->value('id');
        }

        $semester = $request->query('semester', 'Gasal');
        if ($semester === '1') $semester = 'Gasal';
        if ($semester === '2') $semester = 'Genap';

        $jenisRapor = $request->query('jenis_rapor', 'kurikulum_merdeka');
        $periode = $request->query('periode');

        $schoolClass = SchoolClass::with(['academicYear', 'homeroomTeacher'])->findOrFail($classId);

        // Ambil semua siswa aktif di kelas ini
        $students = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nis', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'foto_path', 'tempat_lahir', 'tgl_lahir']);

        // Ambil rapor yang sudah ada sesuai jenis rapor dan periode
        $reportCards = ReportCard::with(['grades', 'extracurriculars', 'submitter', 'validator'])
            ->where('class_id', $classId)
            ->where('semester', $semester)
            ->where('jenis_rapor', $jenisRapor)
            ->when($periode, fn($q) => $q->where('periode', $periode))
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->get()
            ->keyBy('student_id');

        $items = $students->map(function ($student, $index) use ($reportCards) {
            $rc = $reportCards->get($student->id);
            $totalGrades = $rc ? $rc->grades->count() : 0;
            $filledGrades = $rc ? $rc->grades->filter(fn($g) => $g->nilai_akhir !== null || !empty($g->predikat))->count() : 0;
            $avgGrade = $rc && $rc->grades->whereNotNull('nilai_akhir')->count() > 0 
                ? round($rc->grades->whereNotNull('nilai_akhir')->avg('nilai_akhir'), 1) 
                : null;

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
                'jenis_rapor' => $rc?->jenis_rapor ?? 'kurikulum_merdeka',
                'periode' => $rc?->periode ?? 'Semester Gasal',
                'submitted_at' => $rc?->submitted_at?->format('d/m/Y H:i'),
                'submitted_by_name' => $rc?->submitter?->nama,
                'validated_at' => $rc?->validated_at?->format('d/m/Y H:i'),
                'validated_by_name' => $rc?->validator?->nama,
                'validation_notes' => $rc?->validation_notes,
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
                'jenis_rapor' => $jenisRapor,
                'periode' => $periode,
                'academic_year_id' => $academicYearId,
                'total_students' => $students->count(),
                'belum_ada_count' => $items->where('status', 'belum_ada')->count(),
                'draft_count' => $items->where('status', 'draft')->count(),
                'submitted_count' => $items->where('status', 'menunggu_validasi')->count(),
                'completed_count' => $items->where('status', 'final')->count(),
                'revision_count' => $items->where('status', 'perlu_revisi')->count(),
                'students' => $items,
            ],
        ]);
    }

    /**
     * Ambil template struktur untuk 3 jenis rapor.
     */
    public function defaultTemplates(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'kurikulum_merdeka' => [
                    'title' => 'Rapor Kurikulum Merdeka (Intrakurikuler & P5)',
                    'subjects' => self::DEFAULT_SUBJECTS,
                    'extracurriculars' => self::DEFAULT_EXTRACURRICULARS,
                ],
                'triwulan_tematik' => [
                    'title' => 'Laporan Hasil Belajar Triwulan Tematik Terpadu BIM',
                    'themes' => self::DEFAULT_TEMATIC_SUBJECTS,
                    'extracurriculars' => self::TEMATIC_EXTRACURRICULARS,
                ],
                'karakter_lifeskill' => [
                    'title' => 'Laporan Perkembangan Penguatan Karakter & Life Skill',
                    'items' => self::DEFAULT_CHARACTER_ITEMS,
                    'intervals' => [
                        ['range' => '> 92 - 100', 'predikat' => null, 'keterangan' => 'Sangat Baik'],
                        ['range' => '> 83 - 92', 'predikat' => null, 'keterangan' => 'Baik'],
                        ['range' => '75 - 83', 'predikat' => null, 'keterangan' => 'Cukup'],
                        ['range' => '< 75', 'predikat' => null, 'keterangan' => 'Kurang'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Backward-compatibility wrapper for defaultSubjects.
     */
    public function defaultSubjects(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => self::DEFAULT_SUBJECTS,
        ]);
    }

    /**
     * Helper untuk menginisialisasi butir penilaian sesuai jenis rapor.
     */
    protected function seedReportCardGrades(ReportCard $rc, string $jenisRapor, string $studentName = 'Ananda'): void
    {
        if ($jenisRapor === 'triwulan_tematik') {
            $urutan = 1;
            foreach (self::DEFAULT_TEMATIC_SUBJECTS as $subj) {
                ReportCardGrade::create([
                    'report_card_id' => $rc->id,
                    'tema' => $subj['tema'],
                    'nama_mapel' => $subj['nama'],
                    'kelompok' => 'Tematik',
                    'nilai_akhir' => null,
                    'predikat' => null,
                    'capaian_kompetensi' => !empty($subj['default_capaian']) ? str_replace('Ananda', $studentName, $subj['default_capaian']) : null,
                    'urutan' => $urutan++,
                ]);
            }
            if ($rc->extracurriculars()->count() === 0) {
                $eUrutan = 1;
                foreach (self::TEMATIC_EXTRACURRICULARS as $ekstra) {
                    ReportCardExtracurricular::create([
                        'report_card_id' => $rc->id,
                        'kegiatan' => $ekstra['kegiatan'],
                        'predikat' => $ekstra['predikat'],
                        'keterangan' => $ekstra['keterangan'],
                        'urutan' => $eUrutan++,
                    ]);
                }
            }
        } elseif ($jenisRapor === 'karakter_lifeskill') {
            $urutan = 1;
            foreach (self::DEFAULT_CHARACTER_ITEMS as $item) {
                ReportCardGrade::create([
                    'report_card_id' => $rc->id,
                    'aspek' => $item['aspek'],
                    'tema' => $item['kelompok_karakter'] ?? null,
                    'sub_aspek' => $item['sub_aspek'],
                    'nama_mapel' => $item['sub_aspek'],
                    'kelompok' => 'Karakter & Life Skill',
                    'nilai_akhir' => null,
                    'predikat' => null,
                    'capaian_kompetensi' => null,
                    'urutan' => $urutan++,
                ]);
            }
        } else {
            // kurikulum_merdeka
            $urutan = 1;
            foreach (self::DEFAULT_SUBJECTS as $subj) {
                ReportCardGrade::create([
                    'report_card_id' => $rc->id,
                    'nama_mapel' => $subj['nama'],
                    'kelompok' => $subj['kelompok'],
                    'nilai_akhir' => null,
                    'predikat' => null,
                    'capaian_kompetensi' => null,
                    'urutan' => $urutan++,
                ]);
            }
            if ($rc->extracurriculars()->count() === 0) {
                $eUrutan = 1;
                foreach (self::DEFAULT_EXTRACURRICULARS as $ekstra) {
                    ReportCardExtracurricular::create([
                        'report_card_id' => $rc->id,
                        'kegiatan' => $ekstra['kegiatan'],
                        'predikat' => $ekstra['predikat'],
                        'keterangan' => $ekstra['keterangan'],
                        'urutan' => $eUrutan++,
                    ]);
                }
            }
        }
    }

    /**
     * Memastikan seluruh mapel (18 mapel Kurikulum Merdeka atau 5 Tematik), ekstrakurikuler, dan P5 selalu lengkap.
     */
    protected function ensureCompleteReportCardGradesAndEkstra(ReportCard $rc, string $studentName = 'Ananda'): void
    {
        $jenisRapor = $rc->jenis_rapor;

        if ($jenisRapor === 'triwulan_tematik') {
            $existingTema = $rc->grades()->pluck('tema')->filter()->map(fn($t) => strtolower(trim($t)))->toArray();
            $existingMapel = $rc->grades()->pluck('nama_mapel')->filter()->map(fn($m) => strtolower(trim($m)))->toArray();

            foreach (self::DEFAULT_TEMATIC_SUBJECTS as $idx => $subj) {
                $temaLower = strtolower(trim($subj['tema']));
                $namaLower = strtolower(trim($subj['nama']));
                if (!in_array($temaLower, $existingTema) && !in_array($namaLower, $existingMapel)) {
                    ReportCardGrade::create([
                        'report_card_id' => $rc->id,
                        'tema' => $subj['tema'],
                        'nama_mapel' => $subj['nama'],
                        'kelompok' => 'Tematik',
                        'nilai_akhir' => null,
                        'predikat' => null,
                        'capaian_kompetensi' => !empty($subj['default_capaian']) ? str_replace('Ananda', $studentName, $subj['default_capaian']) : null,
                        'urutan' => $idx + 1,
                    ]);
                }
            }

            if ($rc->extracurriculars()->count() === 0) {
                foreach (self::TEMATIC_EXTRACURRICULARS as $eIdx => $ekstra) {
                    ReportCardExtracurricular::create([
                        'report_card_id' => $rc->id,
                        'kegiatan' => $ekstra['kegiatan'],
                        'predikat' => $ekstra['predikat'],
                        'keterangan' => $ekstra['keterangan'],
                        'urutan' => $eIdx + 1,
                    ]);
                }
            }
        } elseif ($jenisRapor === 'karakter_lifeskill') {
            $existingMapel = $rc->grades()->pluck('nama_mapel')->filter()->map(fn($m) => strtolower(trim($m)))->toArray();

            foreach (self::DEFAULT_CHARACTER_ITEMS as $cIdx => $item) {
                $subLower = strtolower(trim($item['sub_aspek']));
                if (!in_array($subLower, $existingMapel)) {
                    ReportCardGrade::create([
                        'report_card_id' => $rc->id,
                        'aspek' => $item['aspek'],
                        'tema' => $item['kelompok_karakter'] ?? null,
                        'sub_aspek' => $item['sub_aspek'],
                        'nama_mapel' => $item['sub_aspek'],
                        'kelompok' => 'Karakter & Life Skill',
                        'nilai_akhir' => null,
                        'predikat' => null,
                        'capaian_kompetensi' => null,
                        'urutan' => $cIdx + 1,
                    ]);
                }
            }
        } else {
            // kurikulum_merdeka
            $existingMapel = $rc->grades()->pluck('nama_mapel')->filter()->map(fn($m) => strtolower(trim($m)))->toArray();

            foreach (self::DEFAULT_SUBJECTS as $idx => $subj) {
                $namaLower = strtolower(trim($subj['nama']));
                if (!in_array($namaLower, $existingMapel)) {
                    ReportCardGrade::create([
                        'report_card_id' => $rc->id,
                        'nama_mapel' => $subj['nama'],
                        'kelompok' => $subj['kelompok'],
                        'nilai_akhir' => null,
                        'predikat' => null,
                        'capaian_kompetensi' => null,
                        'urutan' => $idx + 1,
                    ]);
                }
            }

            if ($rc->extracurriculars()->count() === 0) {
                foreach (self::DEFAULT_EXTRACURRICULARS as $eIdx => $ekstra) {
                    ReportCardExtracurricular::create([
                        'report_card_id' => $rc->id,
                        'kegiatan' => $ekstra['kegiatan'],
                        'predikat' => $ekstra['predikat'],
                        'keterangan' => $ekstra['keterangan'],
                        'urutan' => $eIdx + 1,
                    ]);
                }
            }
        }

        // Lengkapi P5 dan Catatan jika kosong
        $updates = [];
        if (empty($rc->kokurikuler_tema)) {
            $updates['kokurikuler_tema'] = 'Sampahku bersih lingkunganku sehat';
            $updates['kokurikuler_dimensi'] = 'Kolaborasi, Kemandirian, dan Kreativitas';
            $updates['kokurikuler_target'] = 'Peserta didik mampu berkolaborasi menjaga kebersihan lingkungan sekolah dan memilah sampah secara mandiri.';
            $updates['kokurikuler_capaian'] = 'Berkembang Sesuai Harapan';
            $updates['kokurikuler_deskripsi'] = "Ananda {$studentName} telah menunjukkan sikap aktif dan bertanggung jawab.";
        }
        if (empty($rc->catatan_wali_kelas)) {
            $updates['catatan_wali_kelas'] = ($jenisRapor === 'triwulan_tematik')
                ? 'Pertahankan prestasi. Lebih sungguh-sungguh lagi dalam belajar. Baarakallahu fiik.'
                : 'Alhamdulillah, ananda menunjukkan perkembangan belajar yang baik. Pertahankan prestasimu dan terus bersemangat menuntut ilmu.';
        }
        if (!empty($updates)) {
            $rc->update($updates);
        }
    }

    /**
     * Inisialisasi massal rapor untuk semua siswa dalam satu kelas.
     */
    public function initializeClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'in:Gasal,Genap,1,2'],
            'jenis_rapor' => ['nullable', 'string', 'in:kurikulum_merdeka,triwulan_tematik,karakter_lifeskill'],
            'periode' => ['nullable', 'string', 'max:50'],
            'tempat_tanggal' => ['nullable', 'string', 'max:100'],
            'overwrite_existing' => ['nullable', 'boolean'],
        ]);

        $classId = (int) $validated['class_id'];
        $this->authorizeAccess($request, $classId);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($validated['semester'], ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $jenisRapor = $validated['jenis_rapor'] ?? 'kurikulum_merdeka';
        $periode = $validated['periode'] ?? ($jenisRapor === 'kurikulum_merdeka' ? 'Semester ' . $semester : ($semester === 'Genap' ? 'Triwulan III' : 'Triwulan I'));
        $overwrite = $validated['overwrite_existing'] ?? false;

        $schoolClass = SchoolClass::with(['homeroomTeacher', 'academicYear'])->findOrFail($classId);
        $schoolProfile = SchoolProfile::with('headmaster')->first();

        $level = (int) $schoolClass->tingkat;
        $fase = ($level <= 2) ? 'A' : (($level <= 4) ? 'B' : 'C');

        $headmasterName = $schoolProfile?->headmaster?->nama_lengkap ?? 'Yuniatul Inayah, A.Md., S.Pd.';
        $headmasterNip = $schoolProfile?->headmaster?->nip ?? '197707202024211001';
        $homeroomName = $schoolClass->homeroomTeacher?->nama_lengkap ?? '-';
        $homeroomNip = $schoolClass->homeroomTeacher?->nip ?? '-';
        $tempatTanggal = $validated['tempat_tanggal'] ?? 'Purwokerto, ' . now()->isoFormat('D MMMM Y');

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
                    ->where('jenis_rapor', $jenisRapor)
                    ->when($periode, fn($q) => $q->where('periode', $periode))
                    ->first();

                if ($rc && !$overwrite) {
                    continue;
                }

                $attCounts = StudentAttendance::where('student_id', $student->id)
                    ->where('class_id', $classId)
                    ->when($semModel?->tgl_mulai && $semModel?->tgl_selesai, function ($q) use ($semModel) {
                        $q->whereBetween('tanggal', [$semModel->tgl_mulai, $semModel->tgl_selesai]);
                    })
                    ->selectRaw("
                        COUNT(DISTINCT CASE WHEN status = 'sakit' THEN tanggal END) as sakit,
                        COUNT(DISTINCT CASE WHEN status = 'izin' THEN tanggal END) as izin,
                        COUNT(DISTINCT CASE WHEN status = 'alpa' THEN tanggal END) as alpa
                    ")->first();

                if (!$rc) {
                    $rc = ReportCard::create([
                        'student_id' => $student->id,
                        'class_id' => $classId,
                        'academic_year_id' => $academicYearId,
                        'semester_id' => $semModel?->id,
                        'semester' => $semester,
                        'jenis_rapor' => $jenisRapor,
                        'periode' => $periode,
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
                        'kokurikuler_deskripsi' => "Ananda {$student->nama_lengkap} telah menunjukkan sikap aktif dan bertanggung jawab dalam kegiatan sekolah.",
                        'sakit' => $attCounts->sakit ?? 0,
                        'izin' => $attCounts->izin ?? 0,
                        'alpa' => $attCounts->alpa ?? 0,
                        'catatan_wali_kelas' => 'Alhamdulillah, ananda menunjukkan perkembangan belajar dan adab yang baik. Pertahankan prestasimu dan terus bersemangat menuntut ilmu.',
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

                if ($rc->grades()->count() === 0) {
                    $this->seedReportCardGrades($rc, $jenisRapor, $student->nama_lengkap);
                }
            }

            DB::commit();

            ActivityLogService::log(
                'Inisialisasi',
                'Rapor',
                'ReportCard',
                $classId,
                null,
                null,
                "Menginisialisasi rapor ({$jenisRapor}) kelas {$schoolClass->nama} semester {$semester} ({$createdCount} dibuat, {$updatedCount} diperbarui)"
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
        $reportCard = ReportCard::with([
            'student.religion',
            'student.schoolClass',
            'schoolClass.homeroomTeacher',
            'academicYear',
            'grades',
            'extracurriculars',
            'submitter',
            'validator',
        ])->findOrFail($id);

        $this->authorizeAccess($request, $reportCard->class_id);

        $this->ensureCompleteReportCardGradesAndEkstra($reportCard, $reportCard->student?->nama_lengkap ?? 'Ananda');
        $reportCard->load('grades', 'extracurriculars');

        $schoolProfile = SchoolProfile::first();

        return response()->json([
            'success' => true,
            'message' => 'Detail rapor berhasil dimuat.',
            'data' => [
                'report_card' => $reportCard,
                'school_profile' => $schoolProfile ? [
                    'nama_sekolah' => $schoolProfile->nama_sekolah ?? 'SDI Bina Insan Mandiri Purwokerto',
                    'yayasan' => $schoolProfile->yayasan ?? 'Yayasan Bina Insan Mandiri',
                    'npsn' => $schoolProfile->npsn ?? '70036695',
                    'nss' => $schoolProfile->nss ?? '102030405060',
                    'akreditasi' => $schoolProfile->akreditasi,
                    'alamat' => $schoolProfile->alamat_lengkap ?? 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144',
                    'telepon' => $schoolProfile->telepon ?? '0281-635812',
                    'email' => $schoolProfile->email ?? 'sdibim.pwt@gmail.com',
                    'website' => $schoolProfile->website ?? 'https://sdibim.sch.id',
                    'logo_path' => $schoolProfile->logo_path ?: '/logo.png',
                ] : [
                    'nama_sekolah' => 'SDI Bina Insan Mandiri Purwokerto',
                    'yayasan' => 'Yayasan Bina Insan Mandiri',
                    'npsn' => '70036695',
                    'nss' => '102030405060',
                    'akreditasi' => null,
                    'alamat' => 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144',
                    'telepon' => '0281-635812',
                    'email' => 'sdibim.pwt@gmail.com',
                    'website' => 'https://sdibim.sch.id',
                    'logo_path' => '/logo.png',
                ],
            ],
        ]);
    }

    /**
     * Ambil atau inisialisasi rapor untuk satu siswa tertentu.
     */
    public function getOrCreateForStudent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'semester' => ['nullable', 'string', 'in:Gasal,Genap,1,2'],
            'jenis_rapor' => ['nullable', 'string', 'in:kurikulum_merdeka,triwulan_tematik,karakter_lifeskill'],
            'periode' => ['nullable', 'string', 'max:50'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
        ]);

        $student = Student::with('schoolClass.homeroomTeacher')->findOrFail($validated['student_id']);
        $classId = $student->class_id;
        $this->authorizeAccess($request, $classId);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($validated['semester'] ?? 'Gasal', ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $jenisRapor = $validated['jenis_rapor'] ?? 'kurikulum_merdeka';
        $periode = $validated['periode'] ?? ($jenisRapor === 'kurikulum_merdeka' ? 'Semester ' . $semester : ($semester === 'Genap' ? 'Triwulan III' : 'Triwulan I'));

        $semModel = Semester::where('academic_year_id', $academicYearId)
            ->where('semester', strtolower($semester))
            ->first();

        $rc = ReportCard::with(['grades', 'extracurriculars', 'student', 'schoolClass', 'academicYear', 'submitter', 'validator'])
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('jenis_rapor', $jenisRapor)
            ->when($periode, fn($q) => $q->where('periode', $periode))
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
                ->when($semModel?->tgl_mulai && $semModel?->tgl_selesai, function ($q) use ($semModel) {
                    $q->whereBetween('tanggal', [$semModel->tgl_mulai, $semModel->tgl_selesai]);
                })
                ->selectRaw("
                    COUNT(DISTINCT CASE WHEN status = 'sakit' THEN tanggal END) as sakit,
                    COUNT(DISTINCT CASE WHEN status = 'izin' THEN tanggal END) as izin,
                    COUNT(DISTINCT CASE WHEN status = 'alpa' THEN tanggal END) as alpa
                ")->first();

            $rc = ReportCard::create([
                'student_id' => $student->id,
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
                'semester_id' => $semModel?->id,
                'semester' => $semester,
                'jenis_rapor' => $jenisRapor,
                'periode' => $periode,
                'fase' => $fase,
                'tempat_tanggal' => 'Purwokerto, ' . now()->isoFormat('D MMMM Y'),
                'wali_kelas_nama' => $schoolClass?->homeroomTeacher?->nama_lengkap ?? '-',
                'wali_kelas_nip' => $schoolClass?->homeroomTeacher?->nip ?? '-',
                'kepala_sekolah_nama' => $schoolProfile?->headmaster?->nama_lengkap ?? 'Yuniatul Inayah, A.Md., S.Pd.',
                'kepala_sekolah_nip' => $schoolProfile?->headmaster?->nip ?? '197707202024211001',
                'kokurikuler_tema' => 'Sampahku bersih lingkunganku sehat',
                'kokurikuler_dimensi' => 'Kolaborasi, Kemandirian, dan Kreativitas',
                'kokurikuler_target' => 'Peserta didik mampu berkolaborasi menjaga kebersihan lingkungan sekolah dan memilah sampah secara mandiri.',
                'kokurikuler_capaian' => 'Berkembang Sesuai Harapan',
                'kokurikuler_deskripsi' => "Ananda {$student->nama_lengkap} telah menunjukkan sikap aktif dan bertanggung jawab.",
                'sakit' => $attCounts->sakit ?? 0,
                'izin' => $attCounts->izin ?? 0,
                'alpa' => $attCounts->alpa ?? 0,
                'catatan_wali_kelas' => 'Alhamdulillah, ananda menunjukkan perkembangan belajar yang baik. Pertahankan prestasimu dan terus bersemangat menuntut ilmu.',
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            $this->ensureCompleteReportCardGradesAndEkstra($rc, $student->nama_lengkap ?? 'Ananda');
            $rc->load(['grades', 'extracurriculars', 'student', 'schoolClass', 'academicYear', 'submitter', 'validator']);
        } else {
            $this->ensureCompleteReportCardGradesAndEkstra($rc, $student->nama_lengkap ?? 'Ananda');
            $rc->load(['grades', 'extracurriculars', 'student', 'schoolClass', 'academicYear', 'submitter', 'validator']);
        }

        $schoolProfile = SchoolProfile::with('headmaster')->first();
        $schoolProfileData = $schoolProfile ? [
            'nama_sekolah' => $schoolProfile->nama_sekolah ?? 'SDI Bina Insan Mandiri Purwokerto',
            'yayasan' => $schoolProfile->yayasan ?? 'Yayasan Bina Insan Mandiri',
            'npsn' => $schoolProfile->npsn ?? '70036695',
            'nss' => $schoolProfile->nss ?? '102030405060',
            'akreditasi' => $schoolProfile->akreditasi,
            'alamat' => $schoolProfile->alamat_lengkap ?? 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144',
            'telepon' => $schoolProfile->telepon ?? '0281-635812',
            'email' => $schoolProfile->email ?? 'sdibim.pwt@gmail.com',
            'website' => $schoolProfile->website ?? 'https://sdibim.sch.id',
            'logo_path' => $schoolProfile->logo_path ?: '/logo.png',
        ] : [
            'nama_sekolah' => 'SDI Bina Insan Mandiri Purwokerto',
            'yayasan' => 'Yayasan Bina Insan Mandiri',
            'npsn' => '70036695',
            'nss' => '102030405060',
            'akreditasi' => null,
            'alamat' => 'Jl. Wadas Kelir RT.04 RW.05 Kel. Karangklesem, Kec. Purwokerto Selatan, Kab. Banyumas 53144',
            'telepon' => '0281-635812',
            'email' => 'sdibim.pwt@gmail.com',
            'website' => 'https://sdibim.sch.id',
            'logo_path' => '/logo.png',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'report_card' => $rc,
                'school_profile' => $schoolProfileData,
            ],
        ]);
    }

    /**
     * Simpan / Perbarui Rapor Siswa.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $reportCard = ReportCard::findOrFail($id);
        $this->authorizeAccess($request, $reportCard->class_id);

        $user = $request->user();
        if ($reportCard->status === 'final' && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Rapor ini telah divalidasi dan berstatus final. Hanya Kepala Sekolah atau Admin yang dapat mengubahnya.',
            ], 403);
        }

        $validated = $request->validate([
            'fase' => ['nullable', 'string', 'max:10'],
            'periode' => ['nullable', 'string', 'max:50'],
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
            'status' => ['nullable', 'string', 'in:draft,menunggu_validasi,final,perlu_revisi'],
            'validation_notes' => ['nullable', 'string'],
            'revision_notes' => ['nullable', 'string', 'max:500'],
            'grades' => ['nullable', 'array'],
            'grades.*.id' => ['nullable', 'integer'],
            'grades.*.nama_mapel' => ['required_with:grades', 'string', 'max:150'],
            'grades.*.kelompok' => ['nullable', 'string', 'max:50'],
            'grades.*.tema' => ['nullable', 'string', 'max:150'],
            'grades.*.aspek' => ['nullable', 'string', 'max:150'],
            'grades.*.sub_aspek' => ['nullable', 'string', 'max:150'],
            'grades.*.nilai_uh' => ['nullable', 'array'],
            'grades.*.rata_uh' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.nilai_sas' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.nilai_akhir' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.predikat' => ['nullable', 'string', 'max:20'],
            'grades.*.capaian_kompetensi' => ['nullable', 'string'],
            'grades.*.urutan' => ['nullable', 'integer'],
            'extracurriculars' => ['nullable', 'array'],
            'extracurriculars.*.id' => ['nullable', 'integer'],
            'extracurriculars.*.kegiatan' => ['required_with:extracurriculars', 'string', 'max:150'],
            'extracurriculars.*.predikat' => ['nullable', 'string', 'max:50'],
            'extracurriculars.*.keterangan' => ['nullable', 'string', 'max:255'],
            'extracurriculars.*.urutan' => ['nullable', 'integer'],
        ]);

        $sem = $reportCard->semesterRel;
        if ($sem && $sem->status === 'terkunci' && $sem->isReopened()) {
            if ($reportCard->status === 'final') {
                $revisionNotes = $request->input('revision_notes');
                if (empty($revisionNotes)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Catatan revisi (revision_notes) wajib diisi saat mengubah rapor berstatus final pada masa pembukaan sementara semester.',
                    ], 422);
                }
                $reportCard->createRevision($revisionNotes, $user->id);
            }
        } else {
            PeriodLockService::validateSemesterNotLocked($reportCard->semester_id);
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'fase' => $validated['fase'] ?? $reportCard->fase,
                'periode' => $validated['periode'] ?? $reportCard->periode,
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
                'updated_by' => $user->id,
            ];

            if (isset($validated['status'])) {
                $newStatus = $validated['status'];
                $updateData['status'] = $newStatus;
                if ($newStatus === 'menunggu_validasi') {
                    $updateData['submitted_at'] = now();
                    $updateData['submitted_by'] = $user->id;
                } elseif ($newStatus === 'final' && $user->isAdmin()) {
                    $updateData['validated_at'] = now();
                    $updateData['validated_by'] = $user->id;
                }
            }

            if (isset($validated['validation_notes'])) {
                $updateData['validation_notes'] = $validated['validation_notes'];
            }

            $reportCard->update($updateData);

            if (isset($validated['grades'])) {
                $keptGradeIds = [];
                foreach ($validated['grades'] as $gIndex => $gData) {
                    $rawUh = isset($gData['nilai_uh']) ? (array) $gData['nilai_uh'] : null;
                    $validUh = $rawUh !== null ? array_values(array_filter($rawUh, fn($v) => is_numeric($v) && $v !== '')) : null;
                    $rataUh = (isset($gData['rata_uh']) && $gData['rata_uh'] !== '') ? (float) $gData['rata_uh'] : ($validUh && count($validUh) > 0 ? round(array_sum($validUh) / count($validUh), 1) : null);
                    $nilaiSas = (isset($gData['nilai_sas']) && $gData['nilai_sas'] !== '') ? (float) $gData['nilai_sas'] : null;

                    // Utamakan nilai akhir eksplisit dari form, fallback ke hitung rumus NR jika tidak disediakan
                    $nilaiAkhir = (isset($gData['nilai_akhir']) && $gData['nilai_akhir'] !== '') 
                        ? (float) $gData['nilai_akhir'] 
                        : ReportCardGrade::calculateNR($rataUh, $nilaiSas);
                    $predikat = $gData['predikat'] ?? ($nilaiAkhir !== null ? $this->getPredicateFromScore($nilaiAkhir) : null);

                    $gradePayload = [
                        'nama_mapel' => $gData['nama_mapel'],
                        'kelompok' => $gData['kelompok'] ?? 'Umum',
                        'tema' => $gData['tema'] ?? null,
                        'aspek' => $gData['aspek'] ?? null,
                        'sub_aspek' => $gData['sub_aspek'] ?? null,
                        'nilai_uh' => $validUh,
                        'rata_uh' => $rataUh,
                        'nilai_sas' => $nilaiSas,
                        'nilai_akhir' => $nilaiAkhir,
                        'predikat' => $predikat,
                        'capaian_kompetensi' => $gData['capaian_kompetensi'] ?? null,
                        'urutan' => $gData['urutan'] ?? ($gIndex + 1),
                    ];

                    $gradeId = $gData['id'] ?? null;
                    if ($gradeId) {
                        $grade = ReportCardGrade::where('report_card_id', $reportCard->id)->where('id', $gradeId)->first();
                        if ($grade) {
                            $grade->update($gradePayload);
                            $keptGradeIds[] = $grade->id;
                            continue;
                        }
                    }

                    $newGrade = ReportCardGrade::create(array_merge($gradePayload, [
                        'report_card_id' => $reportCard->id,
                    ]));
                    $keptGradeIds[] = $newGrade->id;
                }
                ReportCardGrade::where('report_card_id', $reportCard->id)
                    ->whereNotIn('id', $keptGradeIds)
                    ->delete();
            }

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

            $reportCard->load(['grades', 'extracurriculars', 'student', 'submitter', 'validator']);

            ActivityLogService::log(
                'Update',
                'Rapor',
                'ReportCard',
                $reportCard->id,
                null,
                null,
                "Memperbarui rapor siswa {$reportCard->student->nama_lengkap} ({$reportCard->jenis_rapor}, Status: {$reportCard->status})"
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
     * Wali Kelas mengajukan rapor satu siswa ke Kepala Sekolah untuk divalidasi.
     */
    public function submitForValidation(Request $request, int $id): JsonResponse
    {
        $reportCard = ReportCard::with('student')->findOrFail($id);
        $this->authorizeAccess($request, $reportCard->class_id);

        if ($reportCard->status === 'final') {
            return response()->json([
                'success' => false,
                'message' => 'Rapor ini sudah divalidasi dan berstatus final.',
            ], 422);
        }

        // Validasi: Pengajuan ke KS hanya dapat dilakukan jika seluruh rapor seluruh siswa yang diampu sudah diisi lengkap
        $activeStudents = Student::where('class_id', $reportCard->class_id)
            ->where('status', 'aktif')
            ->get(['id', 'nama_lengkap']);

        $classReportCards = ReportCard::with('grades')
            ->where('class_id', $reportCard->class_id)
            ->where('academic_year_id', $reportCard->academic_year_id)
            ->where('semester', $reportCard->semester)
            ->where('jenis_rapor', $reportCard->jenis_rapor)
            ->when($reportCard->periode, fn($q) => $q->where('periode', $reportCard->periode))
            ->get()
            ->keyBy('student_id');

        $incompleteCount = 0;
        foreach ($activeStudents as $st) {
            $rc = $classReportCards->get($st->id);
            if (!$rc) {
                $incompleteCount++;
                continue;
            }
            $totalGrades = $rc->grades->count();
            $filledGrades = $rc->grades->filter(function ($g) {
                return ($g->nilai_akhir !== null && $g->nilai_akhir !== '') || (!empty($g->predikat) && $g->predikat !== '-');
            })->count();

            if ($totalGrades === 0 || $filledGrades < $totalGrades) {
                $incompleteCount++;
            }
        }

        if ($incompleteCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Pengajuan ke Kepala Sekolah hanya dapat dilakukan jika seluruh rapor seluruh siswa yang diampu sudah diisi lengkap. Masih ada {$incompleteCount} siswa yang belum lengkap.",
            ], 422);
        }

        $reportCard->update([
            'status' => 'menunggu_validasi',
            'submitted_at' => now(),
            'submitted_by' => $request->user()->id,
        ]);

        ActivityLogService::log(
            'Ajukan Validasi',
            'Rapor',
            'ReportCard',
            $reportCard->id,
            null,
            null,
            "Wali Kelas mengajukan validasi rapor {$reportCard->student->nama_lengkap} ({$reportCard->jenis_rapor}) ke Kepala Sekolah"
        );

        return response()->json([
            'success' => true,
            'message' => "Rapor {$reportCard->student->nama_lengkap} berhasil diajukan untuk validasi Kepala Sekolah.",
            'data' => $reportCard->fresh(['submitter', 'validator']),
        ]);
    }

    /**
     * Wali Kelas mengajukan seluruh rapor satu kelas sekaligus ke Kepala Sekolah.
     */
    public function submitClassForValidation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'in:Gasal,Genap,1,2'],
            'jenis_rapor' => ['nullable', 'string'],
            'periode' => ['nullable', 'string'],
        ]);

        $classId = (int) $validated['class_id'];
        $this->authorizeAccess($request, $classId);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($validated['semester'], ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $jenisRapor = $validated['jenis_rapor'] ?? 'kurikulum_merdeka';
        $periode = $validated['periode'] ?? null;

        // Validasi: Seluruh siswa aktif di kelas harus sudah memiliki rapor dan seluruh penilaian terisi lengkap
        $activeStudents = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap']);

        if ($activeStudents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada siswa aktif di kelas ini.',
            ], 422);
        }

        $reportCards = ReportCard::with('grades')
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('jenis_rapor', $jenisRapor)
            ->when($periode, fn($q) => $q->where('periode', $periode))
            ->get()
            ->keyBy('student_id');

        $incompleteStudents = [];
        foreach ($activeStudents as $student) {
            $rc = $reportCards->get($student->id);
            if (!$rc) {
                $incompleteStudents[] = "{$student->nama_lengkap} (Belum dibuatkan draf)";
                continue;
            }

            $totalGrades = $rc->grades->count();
            $filledGrades = $rc->grades->filter(function ($g) {
                return ($g->nilai_akhir !== null && $g->nilai_akhir !== '') || (!empty($g->predikat) && $g->predikat !== '-');
            })->count();

            if ($totalGrades === 0 || $filledGrades < $totalGrades) {
                $incompleteStudents[] = "{$student->nama_lengkap} (Baru terisi {$filledGrades}/{$totalGrades} penilaian)";
            }
        }

        if (!empty($incompleteStudents)) {
            $countIncomplete = count($incompleteStudents);
            $totalStudents = $activeStudents->count();
            $preview = implode(', ', array_slice($incompleteStudents, 0, 3));
            if ($countIncomplete > 3) {
                $preview .= ', dan ' . ($countIncomplete - 3) . ' siswa lainnya';
            }

            return response()->json([
                'success' => false,
                'message' => "Pengajuan ke Kepala Sekolah hanya dapat dilakukan jika seluruh rapor seluruh siswa yang diampu sudah diisi lengkap. Masih ada {$countIncomplete} dari {$totalStudents} siswa yang belum lengkap ({$preview}).",
                'incomplete_students' => $incompleteStudents,
            ], 422);
        }

        $query = ReportCard::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('jenis_rapor', $jenisRapor)
            ->whereIn('status', ['draft', 'perlu_revisi']);

        if ($periode) {
            $query->where('periode', $periode);
        }

        $updatedCount = $query->update([
            'status' => 'menunggu_validasi',
            'submitted_at' => now(),
            'submitted_by' => $request->user()->id,
        ]);

        ActivityLogService::log(
            'Ajukan Validasi Kelas',
            'Rapor',
            'ReportCard',
            $classId,
            null,
            null,
            "Wali Kelas mengajukan {$updatedCount} rapor kelas {$classId} ({$jenisRapor}) ke Kepala Sekolah"
        );

        return response()->json([
            'success' => true,
            'message' => "Seluruh rapor siswa ({$updatedCount} siswa) berhasil diajukan untuk validasi Kepala Sekolah.",
            'data' => ['submitted_count' => $updatedCount],
        ]);
    }

    /**
     * Kepala Sekolah memvalidasi dan menyetujui rapor siswa (status menjadi 'final').
     */
    public function validateReportCard(Request $request, int $id): JsonResponse
    {
        $this->authorizeValidator($request);

        $reportCard = ReportCard::with('student')->findOrFail($id);

        $reportCard->update([
            'status' => 'final',
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
            'validation_notes' => $request->input('notes'),
        ]);

        ActivityLogService::log(
            'Validasi',
            'Rapor',
            'ReportCard',
            $reportCard->id,
            null,
            null,
            "Kepala Sekolah memvalidasi dan menyetujui rapor {$reportCard->student->nama_lengkap} ({$reportCard->jenis_rapor})"
        );

        return response()->json([
            'success' => true,
            'message' => "Rapor {$reportCard->student->nama_lengkap} berhasil disetujui dan berstatus resmi (Final).",
            'data' => $reportCard->fresh(['submitter', 'validator']),
        ]);
    }

    /**
     * Kepala Sekolah memvalidasi seluruh rapor kelas secara massal.
     */
    public function validateClass(Request $request): JsonResponse
    {
        $this->authorizeValidator($request);

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'in:Gasal,Genap,1,2'],
            'jenis_rapor' => ['nullable', 'string'],
            'periode' => ['nullable', 'string'],
        ]);

        $classId = (int) $validated['class_id'];
        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($validated['semester'], ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $jenisRapor = $validated['jenis_rapor'] ?? 'kurikulum_merdeka';
        $periode = $validated['periode'] ?? null;

        $query = ReportCard::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('jenis_rapor', $jenisRapor)
            ->whereIn('status', ['menunggu_validasi', 'draft']);

        if ($periode) {
            $query->where('periode', $periode);
        }

        $count = $query->update([
            'status' => 'final',
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
        ]);

        ActivityLogService::log(
            'Validasi Massal',
            'Rapor',
            'ReportCard',
            $classId,
            null,
            null,
            "Kepala Sekolah memvalidasi massal {$count} rapor kelas {$classId} ({$jenisRapor})"
        );

        return response()->json([
            'success' => true,
            'message' => "{$count} rapor berhasil divalidasi dan disetujui oleh Kepala Sekolah.",
            'data' => ['validated_count' => $count],
        ]);
    }

    /**
     * Kepala Sekolah meminta revisi rapor kepada Wali Kelas.
     */
    public function requestRevision(Request $request, int $id): JsonResponse
    {
        $this->authorizeValidator($request);

        $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $reportCard = ReportCard::with('student')->findOrFail($id);

        $reportCard->update([
            'status' => 'perlu_revisi',
            'validation_notes' => $request->input('notes'),
            'validated_by' => $request->user()->id,
        ]);

        ActivityLogService::log(
            'Minta Revisi',
            'Rapor',
            'ReportCard',
            $reportCard->id,
            null,
            null,
            "Kepala Sekolah mengembalikan rapor {$reportCard->student->nama_lengkap} untuk direvisi: {$request->input('notes')}"
        );

        return response()->json([
            'success' => true,
            'message' => "Rapor {$reportCard->student->nama_lengkap} dikembalikan kepada Wali Kelas untuk perbaikan.",
            'data' => $reportCard->fresh(['submitter', 'validator']),
        ]);
    }

    /**
     * Sinkronkan presensi siswa secara instan dari StudentAttendance.
     */
        public function syncAttendance(Request $request, int $id): JsonResponse
    {
        $reportCard = ReportCard::with('student')->findOrFail($id);
        $this->authorizeAccess($request, $reportCard->class_id);

        PeriodLockService::validateSemesterNotLocked($reportCard->semester_id);

        $tglMulai = $request->input('tgl_mulai');
        $tglSelesai = $request->input('tgl_selesai');

        $semModel = $reportCard->semesterRel;
        $attQuery = StudentAttendance::where('student_id', $reportCard->student_id)
            ->where('class_id', $reportCard->class_id);

        if ($tglMulai && $tglSelesai) {
            $attQuery->whereBetween('tanggal', [$tglMulai, $tglSelesai]);
        } elseif ($semModel?->tgl_mulai && $semModel?->tgl_selesai) {
            $attQuery->whereBetween('tanggal', [$semModel->tgl_mulai, $semModel->tgl_selesai]);
        }

        $attCounts = $attQuery->selectRaw("
                COUNT(DISTINCT CASE WHEN status = 'sakit' THEN tanggal END) as sakit,
                COUNT(DISTINCT CASE WHEN status = 'izin' THEN tanggal END) as izin,
                COUNT(DISTINCT CASE WHEN status = 'alpa' THEN tanggal END) as alpa
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
     * Sinkronkan rekap presensi kelas secara massal berdasarkan rentang tanggal kustom (FR-RAPOR-ATT).
     */
    public function syncClassAttendance(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string'],
            'periode' => ['nullable', 'string'],
            'jenis_rapor' => ['nullable', 'string'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['required', 'date', 'after_or_equal:tgl_mulai'],
        ]);

        $classId = (int) $request->input('class_id');
        $academicYearId = $request->input('academic_year_id') ? (int) $request->input('academic_year_id') : null;
        $semester = strtolower($request->input('semester')) === 'genap' ? 'Genap' : 'Gasal';
        $periode = $request->input('periode') ?? ($semester === 'Genap' ? 'Triwulan III' : 'Triwulan I');
        $jenisRapor = $request->input('jenis_rapor') ?? 'karakter_lifeskill';
        $tglMulai = $request->input('tgl_mulai');
        $tglSelesai = $request->input('tgl_selesai');

        $this->authorizeAccess($request, $classId);

        $schoolClass = SchoolClass::with(['homeroomTeacher.user', 'academicYear'])->findOrFail($classId);
        $schoolProfile = SchoolProfile::with('headmaster')->first();

        $activeAy = $academicYearId 
            ? AcademicYear::find($academicYearId) 
            : AcademicYear::getActive();

        if (!$activeAy) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun ajaran aktif tidak ditemukan.',
            ], 422);
        }

        $level = (int) $schoolClass->tingkat;
        $fase = ($level <= 2) ? 'A' : (($level <= 4) ? 'B' : 'C');
        $headmasterName = $schoolProfile?->headmaster?->nama_lengkap ?? 'Yuniatul Inayah, A.Md., S.Pd.';
        $headmasterNip = $schoolProfile?->headmaster?->nip ?? '197707202024211001';
        $homeroomName = $schoolClass->homeroomTeacher?->nama_lengkap ?? '-';
        $homeroomNip = $schoolClass->homeroomTeacher?->nip ?? '-';
        $tempatTanggal = 'Purwokerto, ' . now()->isoFormat('D MMMM Y');

        $semModel = Semester::where('academic_year_id', $activeAy->id)
            ->whereRaw('LOWER(semester) = ?', [strtolower($semester)])
            ->first();

        if ($semModel) {
            PeriodLockService::validateSemesterNotLocked($semModel->id);
        }

        $students = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada siswa aktif di kelas ini.',
            ], 422);
        }

        $updatedCount = 0;
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlpa = 0;
        $totalHadir = 0;

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // Hitung presensi siswa dalam rentang tanggal
                $att = StudentAttendance::where('student_id', $student->id)
                    ->where('class_id', $classId)
                    ->whereBetween('tanggal', [$tglMulai, $tglSelesai])
                    ->selectRaw("
                        COUNT(DISTINCT CASE WHEN status = 'sakit' THEN tanggal END) as sakit,
                        COUNT(DISTINCT CASE WHEN status = 'izin' THEN tanggal END) as izin,
                        COUNT(DISTINCT CASE WHEN status = 'alpa' THEN tanggal END) as alpa,
                        COUNT(DISTINCT CASE WHEN status = 'hadir' THEN tanggal END) as hadir
                    ")->first();

                $sakit = (int) ($att->sakit ?? 0);
                $izin = (int) ($att->izin ?? 0);
                $alpa = (int) ($att->alpa ?? 0);
                $hadir = (int) ($att->hadir ?? 0);

                $totalSakit += $sakit;
                $totalIzin += $izin;
                $totalAlpa += $alpa;
                $totalHadir += $hadir;

                // Cari atau buat draf rapor siswa
                $rcQuery = ReportCard::where('student_id', $student->id)
                    ->where('academic_year_id', $activeAy->id)
                    ->where('semester', $semester);

                if ($periode) {
                    $rcQuery->where('periode', $periode);
                }

                $rc = $rcQuery->first();

                if ($rc) {
                    $rc->update([
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'alpa' => $alpa,
                        'updated_by' => $request->user()->id,
                    ]);
                    $updatedCount++;
                } else {
                    $rc = ReportCard::create([
                        'student_id' => $student->id,
                        'class_id' => $classId,
                        'academic_year_id' => $activeAy->id,
                        'semester_id' => $semModel?->id,
                        'semester' => $semester,
                        'jenis_rapor' => $jenisRapor,
                        'periode' => $periode,
                        'fase' => $fase,
                        'tempat_tanggal' => $tempatTanggal,
                        'wali_kelas_nama' => $homeroomName,
                        'wali_kelas_nip' => $homeroomNip,
                        'kepala_sekolah_nama' => $headmasterName,
                        'kepala_sekolah_nip' => $headmasterNip,
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'alpa' => $alpa,
                        'status' => 'draft',
                        'created_by' => $request->user()->id,
                    ]);

                    if ($rc->grades()->count() === 0) {
                        $this->seedReportCardGrades($rc, $jenisRapor, $student->nama_lengkap);
                    }
                    $updatedCount++;
                }
            }

            DB::commit();

            $dateDisplay = date('d/m/Y', strtotime($tglMulai)) . ' - ' . date('d/m/Y', strtotime($tglSelesai));

            return response()->json([
                'success' => true,
                'message' => "Presensi kelas berhasil disinkronkan untuk {$updatedCount} rapor siswa (Rentang: {$dateDisplay}).",
                'data' => [
                    'updated_students' => $updatedCount,
                    'total_students' => $students->count(),
                    'range' => [
                        'tgl_mulai' => $tglMulai,
                        'tgl_selesai' => $tglSelesai,
                    ],
                    'summary' => [
                        'total_hadir' => $totalHadir,
                        'total_sakit' => $totalSakit,
                        'total_izin' => $totalIzin,
                        'total_alpa' => $totalAlpa,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyinkronkan presensi kelas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export data rapor kelas ke format Excel spreadsheet.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['nullable', 'string', 'in:Gasal,Genap,1,2'],
            'jenis_rapor' => ['nullable', 'string'],
            'periode' => ['nullable', 'string'],
        ]);

        $classId = (int) $request->query('class_id');
        $this->authorizeAccess($request, $classId);

        $academicYearId = $request->query('academic_year_id') ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($request->query('semester', 'Gasal'), ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $jenisRapor = $request->query('jenis_rapor', 'kurikulum_merdeka');
        $periode = $request->query('periode');

        $schoolClass = SchoolClass::with(['academicYear', 'homeroomTeacher'])->findOrFail($classId);
        $schoolProfile = SchoolProfile::first();

        $students = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $reportCards = ReportCard::with(['grades', 'extracurriculars', 'submitter', 'validator'])
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('jenis_rapor', $jenisRapor)
            ->when($periode, fn($q) => $q->where('periode', $periode))
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
        $sheetDb->setCellValue('A8', 'Jenis Rapor');
        $sheetDb->setCellValue('C8', strtoupper(str_replace('_', ' ', $jenisRapor)));
        $sheetDb->setCellValue('A9', 'Periode');
        $sheetDb->setCellValue('C9', $periode ?: ($semester === 'Gasal' ? 'Semester Gasal' : 'Semester Genap'));
        $sheetDb->setCellValue('A10', 'Kepala Sekolah');
        $sheetDb->setCellValue('C10', 'Yuniatul Inayah, A.Md., S.Pd.');
        $sheetDb->setCellValue('A11', 'Wali Kelas');
        $sheetDb->setCellValue('C11', $schoolClass->homeroomTeacher?->nama_lengkap ?? '-');

        // 2. Sheet DATA PENILAIAN
        $sheetIntra = $spreadsheet->createSheet();
        $sheetIntra->setTitle('DATA PENILAIAN');
        $sheetIntra->setCellValue('A1', 'DATA HASIL PENILAIAN SISWA');
        $sheetIntra->setCellValue('A3', 'Kelas: ' . $schoolClass->nama . ' | Semester: ' . $semester . ' | Format: ' . strtoupper(str_replace('_', ' ', $jenisRapor)));

        $sheetIntra->setCellValue('A5', 'No');
        $sheetIntra->setCellValue('B5', 'NIS');
        $sheetIntra->setCellValue('C5', 'NISN');
        $sheetIntra->setCellValue('D5', 'Nama Siswa');
        $sheetIntra->setCellValue('E5', 'Status');

        // Tentukan daftar kolom penilaian
        $sampleGrades = $reportCards->first()?->grades ?? collect();
        $colIdx = 6;
        $colMap = [];
        if ($sampleGrades->count() > 0) {
            foreach ($sampleGrades as $sg) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $title = $sg->sub_aspek ?: ($sg->tema ?: $sg->nama_mapel);
                $sheetIntra->setCellValue($colLetter . '5', $title);
                $colMap[$sg->nama_mapel] = $colLetter;
                $colIdx++;
            }
        }

        $sakitCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);
        $izinCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);
        $alpaCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);
        $notesCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx++);

        $sheetIntra->setCellValue($sakitCol . '5', 'Sakit');
        $sheetIntra->setCellValue($izinCol . '5', 'Izin');
        $sheetIntra->setCellValue($alpaCol . '5', 'Alpa');
        $sheetIntra->setCellValue($notesCol . '5', 'Catatan');

        $rowIdx = 6;
        foreach ($students as $idx => $st) {
            $rc = $reportCards->get($st->id);
            $sheetIntra->setCellValue('A' . $rowIdx, $idx + 1);
            $sheetIntra->setCellValue('B' . $rowIdx, $st->nis ?: '-');
            $sheetIntra->setCellValue('C' . $rowIdx, $st->nisn ?: '-');
            $sheetIntra->setCellValue('D' . $rowIdx, $st->nama_lengkap);
            $sheetIntra->setCellValue('E' . $rowIdx, $rc ? strtoupper($rc->status) : 'BELUM ADA');

            if ($rc) {
                foreach ($rc->grades as $g) {
                    if (isset($colMap[$g->nama_mapel])) {
                        $cLet = $colMap[$g->nama_mapel];
                        $val = $g->nilai_akhir !== null ? $g->nilai_akhir : ($g->predikat ?: '-');
                        $sheetIntra->setCellValue($cLet . $rowIdx, $val);
                    }
                }
                $sheetIntra->setCellValue($sakitCol . $rowIdx, $rc->sakit);
                $sheetIntra->setCellValue($izinCol . $rowIdx, $rc->izin);
                $sheetIntra->setCellValue($alpaCol . $rowIdx, $rc->alpa);
                $sheetIntra->setCellValue($notesCol . $rowIdx, $rc->catatan_wali_kelas);
            }
            $rowIdx++;
        }

        $filename = "Rapor_{$jenisRapor}_{$schoolClass->nama}_{$semester}.xlsx";

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import nilai rapor dari spreadsheet Excel.
     */
    public function importExcel(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'in:Gasal,Genap,1,2'],
            'jenis_rapor' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $classId = (int) $request->input('class_id');
        $this->authorizeAccess($request, $classId);

        $academicYearId = $request->input('academic_year_id') ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($request->input('semester'), ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $jenisRapor = $request->input('jenis_rapor', 'kurikulum_merdeka');

        $uploadedFile = $request->file('file');

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadedFile->getRealPath());
            $sheet = $spreadsheet->getSheetByName('DATA PENILAIAN') ?? $spreadsheet->getSheetByName('DATA INTRAKURIKULER') ?? $spreadsheet->getActiveSheet();

            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

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

            $subjectColMap = [];
            for ($c = 6; $c <= $highestColIndex; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $subjName = trim((string) $sheet->getCell($colLetter . $headerRow)->getValue());
                if (!empty($subjName) && !in_array(strtolower($subjName), ['sakit', 'izin', 'alpa', 'catatan', 'status'])) {
                    $subjectColMap[$colLetter] = $subjName;
                }
            }

            $updatedStudents = 0;
            $updatedGrades = 0;

            DB::beginTransaction();

            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $nis = trim((string) $sheet->getCell('B' . $row)->getValue());
                $nisn = trim((string) $sheet->getCell('C' . $row)->getValue());
                $nama = trim((string) $sheet->getCell('D' . $row)->getValue());

                if (empty($nama) && empty($nisn) && empty($nis)) {
                    continue;
                }

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

                $rc = ReportCard::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYearId,
                        'semester' => $semester,
                        'jenis_rapor' => $jenisRapor,
                    ],
                    [
                        'class_id' => $classId,
                        'status' => 'draft',
                        'periode' => $semester === 'Gasal' ? 'Semester Gasal' : 'Semester Genap',
                        'tempat_tanggal' => 'Purwokerto, ' . now()->isoFormat('D MMMM Y'),
                        'created_by' => $request->user()->id,
                    ]
                );

                $updatedStudents++;

                foreach ($subjectColMap as $colLetter => $subjName) {
                    $scoreVal = $sheet->getCell($colLetter . $row)->getValue();
                    if ($scoreVal !== null && trim((string)$scoreVal) !== '') {
                        $strVal = trim((string)$scoreVal);
                        $numericVal = is_numeric($strVal) ? (float) $strVal : null;
                        $predikatVal = (!is_numeric($strVal) && in_array(strtoupper($strVal), ['A', 'B', 'C', 'D'])) ? strtoupper($strVal) : null;

                        $grade = ReportCardGrade::where('report_card_id', $rc->id)
                            ->where(function ($q) use ($subjName) {
                                $q->where('nama_mapel', $subjName)
                                  ->orWhere('sub_aspek', $subjName)
                                  ->orWhere('tema', $subjName);
                            })->first();

                        if (!$grade) {
                            $grade = new ReportCardGrade([
                                'report_card_id' => $rc->id,
                                'nama_mapel' => $subjName,
                                'kelompok' => 'Umum',
                            ]);
                        }

                        if ($numericVal !== null) {
                            $grade->nilai_akhir = $numericVal;
                        }
                        if ($predikatVal !== null) {
                            $grade->predikat = $predikatVal;
                        }
                        $grade->save();
                        $updatedGrades++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Import berhasil. {$updatedStudents} rapor siswa terdeteksi, {$updatedGrades} data nilai/predikat diperbarui.",
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

    /**
     * Mengambil seluruh riwayat arsip rapor siswa berdasarkan tahun ajaran & kelas (Timeline Profil Siswa)
     */
    public function studentHistory(Request $request, int $studentId): JsonResponse
    {
        $student = Student::with(['schoolClass'])->findOrFail($studentId);

        // Ambil semua riwayat pendaftaran kelas (student_enrollments)
        $enrollments = \App\Models\StudentEnrollment::where('student_id', $studentId)
            ->with(['academicYear:id,nama,status', 'schoolClass:id,nama,nama_kelas,tingkat'])
            ->orderBy('academic_year_id', 'desc')
            ->get();

        // Ambil semua rapor yang pernah dibuat untuk siswa ini
        $reportCards = ReportCard::where('student_id', $studentId)
            ->with([
                'academicYear:id,nama,status',
                'schoolClass:id,nama,nama_kelas,tingkat',
                'submitter:id,nama',
                'validator:id,nama',
            ])
            ->withCount('grades')
            ->orderBy('academic_year_id', 'desc')
            ->orderBy('semester', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        // Kumpulkan ID tahun ajaran yang terkait
        $yearIds = $reportCards->pluck('academic_year_id')
            ->merge($enrollments->pluck('academic_year_id'))
            ->filter()
            ->unique();

        $academicYears = \App\Models\AcademicYear::whereIn('id', $yearIds)
            ->orderBy('tgl_mulai', 'desc')
            ->get();

        // Jika belum ada data tahun ajaran di riwayat, ambil tahun ajaran aktif
        if ($academicYears->isEmpty()) {
            $activeYear = \App\Models\AcademicYear::where('status', 'aktif')->first();
            if ($activeYear) {
                $academicYears = collect([$activeYear]);
            }
        }

        $groupedByYear = [];
        foreach ($academicYears as $ay) {
            $enrollment = $enrollments->firstWhere('academic_year_id', $ay->id);
            $yearReports = $reportCards->where('academic_year_id', $ay->id)->values();

            $groupedByYear[] = [
                'academic_year' => [
                    'id' => $ay->id,
                    'nama' => $ay->nama,
                    'status' => $ay->status,
                ],
                'class' => $enrollment?->schoolClass ?? ($student->schoolClass ?? null),
                'status_kelas' => $enrollment?->status_kelas ?? 'aktif',
                'nomor_absen' => $enrollment?->nomor_absen ?? null,
                'total_rapor' => $yearReports->count(),
                'final_count' => $yearReports->where('status', 'final')->count(),
                'report_cards' => $yearReports,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "Riwayat arsip rapor {$student->nama_lengkap} berhasil diambil.",
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'nama_lengkap' => $student->nama_lengkap,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'foto_url' => $student->foto_url ?? null,
                    'foto_path' => $student->foto_path ?? null,
                    'current_class' => $student->schoolClass?->nama_kelas ?? $student->schoolClass?->nama ?? '-',
                ],
                'timeline' => $groupedByYear,
                'all_report_cards' => $reportCards,
            ],
        ]);
    }

    /**
     * Konversi nilai akhir angka ke predikat standar BIM
     */
    protected function getPredicateFromScore(?float $score): ?string
    {
        if ($score === null) return null;
        if ($score >= 85) return 'A';
        if ($score >= 75) return 'B';
        if ($score >= 65) return 'C';
        return 'D';
    }

    /**
     * Ambil buku catatan nilai ulangan harian (RUH) dan sumatif akhir semester (PSAS)
     * untuk seluruh siswa dalam satu kelas pada semester terpilih.
     */
    public function getClassGradeBook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'in:Gasal,Genap,1,2'],
            'subject_name' => ['nullable', 'string'],
        ]);

        $classId = (int) $validated['class_id'];
        $this->authorizeAccess($request, $classId);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($validated['semester'], ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $periode = 'Semester ' . $semester;

        $schoolClass = SchoolClass::with('homeroomTeacher')->findOrFail($classId);
        $students = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nis', 'nisn', 'nama_lengkap', 'jenis_kelamin']);

        $reportCards = ReportCard::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('jenis_rapor', 'kurikulum_merdeka')
            ->with(['grades' => fn($q) => $q->orderBy('urutan', 'asc')])
            ->get()
            ->keyBy('student_id');

        $studentItems = $students->map(function ($student, $idx) use ($reportCards) {
            $rc = $reportCards->get($student->id);
            $grades = $rc ? $rc->grades : collect();

            $gradesMap = [];
            foreach ($grades as $g) {
                $gradesMap[$g->nama_mapel] = [
                    'id' => $g->id,
                    'nama_mapel' => $g->nama_mapel,
                    'kelompok' => $g->kelompok,
                    'nilai_uh' => $g->nilai_uh ?? [],
                    'rata_uh' => $g->rata_uh,
                    'nilai_sas' => $g->nilai_sas,
                    'nilai_akhir' => $g->nilai_akhir,
                    'predikat' => $g->predikat,
                    'capaian_kompetensi' => $g->capaian_kompetensi,
                ];
            }

            return [
                'no_urut' => $idx + 1,
                'student_id' => $student->id,
                'nama_lengkap' => $student->nama_lengkap,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'report_card_id' => $rc?->id,
                'grades' => $gradesMap,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'class' => $schoolClass,
                'academic_year_id' => $academicYearId,
                'semester' => $semester,
                'periode' => $periode,
                'default_subjects' => self::DEFAULT_SUBJECTS,
                'students' => $studentItems,
            ],
        ]);
    }

    /**
     * Simpan catatan nilai ulangan harian (RUH) dan sumatif akhir semester (PSAS)
     * untuk seluruh siswa kelas pada mata pelajaran tertentu, otomatis masuk ke nilai rapor.
     */
    public function saveClassGradeBook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester' => ['required', 'string', 'in:Gasal,Genap,1,2'],
            'subject_name' => ['required', 'string', 'max:150'],
            'grades' => ['required', 'array'],
            'grades.*.student_id' => ['required', 'exists:students,id'],
            'grades.*.nilai_uh' => ['nullable', 'array'],
            'grades.*.rata_uh' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.nilai_sas' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.nilai_akhir' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grades.*.predikat' => ['nullable', 'string', 'max:10'],
            'grades.*.capaian_kompetensi' => ['nullable', 'string'],
        ]);

        $classId = (int) $validated['class_id'];
        $this->authorizeAccess($request, $classId);

        $academicYearId = $validated['academic_year_id'] ?? AcademicYear::getActive()?->id ?? AcademicYear::latest()->value('id');
        $semester = in_array($validated['semester'], ['1', 'Gasal']) ? 'Gasal' : 'Genap';
        $periode = 'Semester ' . $semester;
        $subjectName = $validated['subject_name'];

        // Tentukan kelompok mapel dari default atau Umum
        $kelompok = 'Umum';
        $urutan = 99;
        foreach (self::DEFAULT_SUBJECTS as $idx => $subj) {
            if (strtolower($subj['nama']) === strtolower($subjectName)) {
                $kelompok = $subj['kelompok'];
                $urutan = $idx + 1;
                break;
            }
        }

        DB::beginTransaction();
        try {
            $savedCount = 0;
            foreach ($validated['grades'] as $g) {
                $studentId = (int) $g['student_id'];

                $rc = ReportCard::firstOrCreate(
                    [
                        'student_id' => $studentId,
                        'academic_year_id' => $academicYearId,
                        'semester' => $semester,
                        'jenis_rapor' => 'kurikulum_merdeka',
                        'periode' => $periode,
                    ],
                    [
                        'class_id' => $classId,
                        'status' => 'draft',
                        'fase' => 'A',
                        'tempat_tanggal' => 'Purwokerto, ' . ($semester === 'Gasal' ? '20 Desember 2026' : '20 Juni 2026'),
                        'created_by' => $request->user()->id,
                    ]
                );
                $this->ensureCompleteReportCardGradesAndEkstra($rc, $g['nama_lengkap'] ?? 'Ananda');

                // Hitung nilai akhir dengan rumus resmi: NR = (3 * RUH + 2 * PSAS) / 5
                $rawUh = isset($g['nilai_uh']) ? (array) $g['nilai_uh'] : [];
                $validUh = array_values(array_filter($rawUh, fn($v) => is_numeric($v) && $v !== ''));
                $rataUh = count($validUh) > 0 ? round(array_sum($validUh) / count($validUh), 1) : (isset($g['rata_uh']) && is_numeric($g['rata_uh']) ? (float) $g['rata_uh'] : null);
                $nilaiSas = isset($g['nilai_sas']) && is_numeric($g['nilai_sas']) ? (float) $g['nilai_sas'] : null;

                $nr = ReportCardGrade::calculateNR($rataUh, $nilaiSas);
                $nilaiAkhir = $nr ?? (isset($g['nilai_akhir']) && is_numeric($g['nilai_akhir']) ? (float) $g['nilai_akhir'] : null);
                $predikat = $nilaiAkhir !== null ? $this->getPredicateFromScore($nilaiAkhir) : ($g['predikat'] ?? null);

                $grade = ReportCardGrade::firstOrNew([
                    'report_card_id' => $rc->id,
                    'nama_mapel' => $subjectName,
                ]);

                $grade->kelompok = $kelompok;
                $grade->urutan = $grade->urutan ?: $urutan;
                $grade->nilai_uh = $validUh;
                $grade->rata_uh = $rataUh;
                $grade->nilai_sas = $nilaiSas;
                $grade->nilai_akhir = $nilaiAkhir;
                $grade->predikat = $predikat;
                if (isset($g['capaian_kompetensi']) && $g['capaian_kompetensi'] !== '') {
                    $grade->capaian_kompetensi = $g['capaian_kompetensi'];
                }
                $grade->save();
                $savedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Catatan nilai {$subjectName} untuk {$savedCount} siswa di {$periode} berhasil disimpan dan otomatis masuk nilai rapor.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan catatan nilai: ' . $e->getMessage(),
            ], 500);
        }
    }
}
