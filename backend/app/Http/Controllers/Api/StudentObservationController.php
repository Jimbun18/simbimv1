<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BkCaseLog;
use App\Models\BkCounselingCase;
use App\Models\Student;
use App\Models\StudentObservation;
use App\Models\Teacher;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentObservationController extends Controller
{
    /**
     * Daftar Ringkasan Peserta Didik yang Memiliki Catatan Observasi.
     * Mengelompokkan catatan per-peserta didik sehingga siswa yang punya banyak catatan tidak berulang.
     * Memiliki indikator pengingat apakah catatan sudah dilihat/ditanggapi oleh Guru BK.
     */
    public function studentsSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $isGuruBk = $user?->isGuruBk() ?? false;
        $teacher = $user?->teacher;

        $obsQuery = StudentObservation::query();

        // Jika guru biasa, hanya hitung dan ambil observasi milik guru tersebut
        if (!$isGuruBk) {
            if ($teacher) {
                $obsQuery->where('teacher_id', $teacher->id);
            } else {
                $obsQuery->where('created_by', $user?->id);
            }
        }

        if ($request->filled('class_id')) {
            $classId = $request->query('class_id');
            $obsQuery->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)
                  ->orWhereHas('student', function ($sq) use ($classId) {
                      $sq->where('class_id', $classId);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $obsQuery->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('nama_lengkap', 'like', "%{$search}%")
                       ->orWhere('nis', 'like', "%{$search}%");
                })
                ->orWhere('perilaku_diamati', 'like', "%{$search}%");
            });
        }

        $studentIds = (clone $obsQuery)->select('student_id')->distinct()->pluck('student_id');

        $students = Student::with(['schoolClass:id,nama,tingkat,kode', 'currentClass:id,nama,tingkat,kode'])
            ->whereIn('id', $studentIds)
            ->get();

        $result = $students->map(function ($student) use ($isGuruBk, $teacher, $user) {
            $studentObsQuery = StudentObservation::where('student_id', $student->id);
            if (!$isGuruBk) {
                if ($teacher) {
                    $studentObsQuery->where('teacher_id', $teacher->id);
                } else {
                    $studentObsQuery->where('created_by', $user?->id);
                }
            }

            $allObs = (clone $studentObsQuery)
                ->with(['observer:id,nama_lengkap,jabatan', 'bkTeacher:id,nama_lengkap,jabatan', 'schoolClass:id,nama,tingkat,kode'])
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $totalCount = $allObs->count();
            $latestObs = $allObs->first();

            $unseenCount = $allObs->whereNull('dilihat_bk_at')->count();
            $unreviewedCount = $allObs->whereNull('rekomendasi_bk')->count();
            $escalatedCount = $allObs->where('is_escalated_to_bk', true)->count();

            // Status Pengingat & Pemantauan
            $statusTinjauan = 'sudah_ditanggapi';
            if ($unseenCount > 0) {
                $statusTinjauan = 'belum_dilihat';
            } elseif ($unreviewedCount > 0) {
                $statusTinjauan = 'menunggu_tanggapan';
            } elseif ($escalatedCount > 0) {
                $statusTinjauan = 'masuk_bk';
            }

            return [
                'student' => $student,
                'total_observations' => $totalCount,
                'unseen_count' => $unseenCount,
                'unreviewed_count' => $unreviewedCount,
                'escalated_count' => $escalatedCount,
                'status_tinjauan' => $statusTinjauan,
                'latest_observation' => $latestObs,
            ];
        });

        // Sorting: Dahulukan yang belum dilihat / menunggu tanggapan BK
        $sorted = $result->sort(function ($a, $b) {
            $priority = ['belum_dilihat' => 1, 'menunggu_tanggapan' => 2, 'masuk_bk' => 3, 'sudah_ditanggapi' => 4];
            $pA = $priority[$a['status_tinjauan']] ?? 5;
            $pB = $priority[$b['status_tinjauan']] ?? 5;
            if ($pA !== $pB) {
                return $pA <=> $pB;
            }
            $tA = $a['latest_observation'] ? Carbon::parse($a['latest_observation']->tanggal)->timestamp : 0;
            $tB = $b['latest_observation'] ? Carbon::parse($b['latest_observation']->tanggal)->timestamp : 0;
            return $tB <=> $tA;
        })->values();

        // Filter opsi status_tinjauan
        if ($request->filled('status_tinjauan')) {
            $filterTinjauan = $request->query('status_tinjauan');
            $sorted = $sorted->filter(function ($item) use ($filterTinjauan) {
                return $item['status_tinjauan'] === $filterTinjauan;
            })->values();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar rekap siswa dengan catatan observasi berhasil diambil.',
            'data' => $sorted,
            'meta' => [
                'total_students' => $sorted->count(),
                'total_unseen' => $sorted->where('unseen_count', '>', 0)->count(),
                'total_unreviewed' => $sorted->where('unreviewed_count', '>', 0)->count(),
            ],
        ]);
    }

    /**
     * Seluruh Riwayat Catatan Observasi untuk 1 Peserta Didik Tertentu.
     * Otomatis menandai catatan belum dilihat menjadi 'Sudah Dilihat' jika dibuka oleh Guru BK.
     */
    public function studentObservations(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();
        $isGuruBk = $user?->isGuruBk() ?? false;
        $teacher = $user?->teacher;

        // Jika Guru BK membuka rekam catatan siswa ini,
        // tandai semua observasi siswa ini yang belum dilihat menjadi 'Sudah Dilihat'
        if ($isGuruBk) {
            StudentObservation::where('student_id', $student->id)
                ->whereNull('dilihat_bk_at')
                ->update([
                    'dilihat_bk_at' => Carbon::now(),
                    'dilihat_bk_by' => $teacher?->id ?? 12,
                ]);
        }

        $query = StudentObservation::with([
            'student:id,nis,nama_lengkap,jenis_kelamin,foto_path,class_id',
            'student.schoolClass:id,nama,tingkat,kode',
            'schoolClass:id,nama,tingkat,kode',
            'observer:id,nama_lengkap,jabatan',
            'bkTeacher:id,nama_lengkap,jabatan',
            'bkViewer:id,nama_lengkap,jabatan',
        ])->where('student_id', $student->id);

        if (!$isGuruBk) {
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            } else {
                $query->where('created_by', $user?->id);
            }
        }

        $observations = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => "Seluruh riwayat catatan observasi untuk siswa {$student->nama_lengkap} berhasil diambil.",
            'data' => [
                'student' => $student->load(['schoolClass', 'currentClass']),
                'observations' => $observations,
                'total' => $observations->count(),
                'unreviewed_count' => $observations->whereNull('rekomendasi_bk')->count(),
            ],
        ]);
    }

    /**
     * Tandai Catatan Observasi Siswa Ini Sudah Dilihat oleh BK.
     */
    public function markStudentObservationsSeen(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();
        $teacher = $user?->teacher;

        StudentObservation::where('student_id', $student->id)
            ->whereNull('dilihat_bk_at')
            ->update([
                'dilihat_bk_at' => Carbon::now(),
                'dilihat_bk_by' => $teacher?->id ?? 12,
            ]);

        return response()->json([
            'success' => true,
            'message' => "Catatan observasi siswa {$student->nama_lengkap} telah ditandai sudah dilihat.",
        ]);
    }

    /**
     * Daftar Catatan Observasi Perilaku Siswa.
     * Guru kelas/mata pelajaran hanya melihat catatannya sendiri (pelaporan ke BK).
     * Guru BK dapat melihat semua laporan dari seluruh guru.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isGuruBk = $user?->isGuruBk() ?? false;

        $query = StudentObservation::with([
            'student:id,nis,nama_lengkap,jenis_kelamin,foto_path,class_id',
            'student.schoolClass:id,nama,tingkat,kode',
            'schoolClass:id,nama,tingkat,kode',
            'observer:id,nama_lengkap,jabatan',
            'bkTeacher:id,nama_lengkap,jabatan',
        ]);

        // Jika BUKAN Guru BK:
        // Guru hanya melihat catatan observasi yang dicatat oleh dirinya sendiri
        if (!$isGuruBk) {
            $teacher = $user?->teacher;
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            } else {
                $query->where('created_by', $user?->id);
            }
        }

        if ($request->filled('class_id')) {
            $classId = $request->query('class_id');
            $query->where(function ($q) use ($classId) {
                $q->where('class_id', $classId)
                  ->orWhereHas('student', function ($sq) use ($classId) {
                      $sq->where('class_id', $classId);
                  });
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->query('student_id'));
        }

        if ($request->filled('status_pemantauan')) {
            $query->where('status_pemantauan', $request->query('status_pemantauan'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('nama_lengkap', 'like', "%{$search}%")
                       ->orWhere('nis', 'like', "%{$search}%");
                })
                ->orWhere('perilaku_diamati', 'like', "%{$search}%")
                ->orWhere('tempat', 'like', "%{$search}%")
                ->orWhere('rekomendasi_bk', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->query('tanggal_dari'));
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->query('tanggal_sampai'));
        }

        $observations = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar catatan observasi siswa berhasil diambil.',
            'data' => $observations->items(),
            'meta' => [
                'current_page' => $observations->currentPage(),
                'last_page' => $observations->lastPage(),
                'per_page' => $observations->perPage(),
                'total' => $observations->total(),
            ],
        ]);
    }

    /**
     * Tambah Catatan Observasi Baru oleh Guru.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'tanggal' => ['required', 'date'],
            'waktu' => ['nullable', 'string', 'max:100'],
            'tempat' => ['nullable', 'string', 'max:150'],
            'situasi_sebelum' => ['nullable', 'string'],
            'perilaku_diamati' => ['required', 'string'],
            'respons_lingkungan' => ['nullable', 'string'],
            'rekomendasi_bk' => ['nullable', 'string'],
            'saran_untuk_guru' => ['nullable', 'string'],
            'catatan_wali_kelas' => ['nullable', 'string'],
            'status_pemantauan' => ['nullable', 'string', 'max:50'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();

        $validated['class_id'] = $student->class_id;
        $validated['academic_year_id'] = $activeYear?->id;
        $validated['teacher_id'] = $teacher ? $teacher->id : 1;
        $validated['created_by'] = $user->id;
        $validated['status_pemantauan'] = $validated['status_pemantauan'] ?? 'Baru';

        // Jika yang menginput adalah Guru BK dan langsung menyertakan rekomendasi
        if (!empty($validated['rekomendasi_bk']) && $teacher && str_contains(strtolower($teacher->jabatan ?? ''), 'bk')) {
            $validated['rekomendasi_bk_by'] = $teacher->id;
            $validated['rekomendasi_bk_at'] = Carbon::now();
        }

        $observation = StudentObservation::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'observations',
            targetType: StudentObservation::class,
            targetId: $observation->id,
            dataSesudah: $observation->toArray(),
            keterangan: "Menambahkan catatan observasi perilaku untuk siswa: {$student->nama_lengkap}."
        );

        // Notifikasi ke seluruh Guru BK (In-App Dashboard + Email via teachers.email)
        $namaPelapor = $teacher?->nama_lengkap ?? $user->nama;
        NotificationService::notifyGuruBk(
            title: "Laporan Observasi Baru: {$student->nama_lengkap}",
            message: "Ustadz/ah {$namaPelapor} melaporkan catatan observasi perilaku untuk siswa {$student->nama_lengkap}. Mohon ditinjau dan diberikan tanggapan.",
            targetUrl: "/observations",
            sendEmail: true,
            extraDetails: [
                'Nama Siswa' => $student->nama_lengkap,
                'NIS' => $student->nis,
                'Guru Pelapor' => $namaPelapor,
                'Tanggal Kejadian' => Carbon::parse($observation->tanggal)->format('d/m/Y'),
                'Tempat / Waktu' => ($observation->tempat ?? '-') . ' (' . ($observation->waktu ?? '-') . ')',
                'Perilaku Diamati' => $observation->perilaku_diamati,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Catatan observasi siswa berhasil disimpan.',
            'data' => $observation->load(['student', 'schoolClass', 'observer', 'bkTeacher']),
        ], 201);
    }

    /**
     * Detail Catatan Observasi.
     */
    public function show(StudentObservation $observation): JsonResponse
    {
        $observation->load([
            'student',
            'schoolClass',
            'observer',
            'bkTeacher',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail catatan observasi berhasil diambil.',
            'data' => $observation,
        ]);
    }

    /**
     * Update Catatan Observasi.
     */
    public function update(Request $request, StudentObservation $observation): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['sometimes', 'required', 'date'],
            'waktu' => ['nullable', 'string', 'max:100'],
            'tempat' => ['nullable', 'string', 'max:150'],
            'situasi_sebelum' => ['nullable', 'string'],
            'perilaku_diamati' => ['sometimes', 'required', 'string'],
            'respons_lingkungan' => ['nullable', 'string'],
            'catatan_wali_kelas' => ['nullable', 'string'],
            'status_pemantauan' => ['nullable', 'string', 'max:50'],
        ]);

        $old = $observation->toArray();
        $validated['updated_by'] = $request->user()->id;
        $observation->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'observations',
            targetType: StudentObservation::class,
            targetId: $observation->id,
            dataSebelum: $old,
            dataSesudah: $observation->fresh()->toArray(),
            keterangan: "Memperbarui catatan observasi siswa ID {$observation->student_id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Catatan observasi berhasil diperbarui.',
            'data' => $observation->fresh()->load(['student', 'schoolClass', 'observer', 'bkTeacher']),
        ]);
    }

    /**
     * Berikan Feedback / Rekomendasi dari Guru BK.
     * Termasuk keputusan apakah kasus ini dimasukkan ke Analisis & Monitoring BK atau tidak.
     */
    public function addFeedbackBk(Request $request, StudentObservation $observation): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;

        $validated = $request->validate([
            'rekomendasi_bk' => ['required', 'string'],
            'saran_untuk_guru' => ['nullable', 'string'],
            'masuk_pemantauan_bk' => ['required', 'boolean'],
            'fokus_pemantauan' => ['nullable', 'string', 'max:100'],
            'status_pemantauan' => ['nullable', 'string', 'max:50'],
        ]);

        $masukBk = (bool) $validated['masuk_pemantauan_bk'];
        $fokus = $validated['fokus_pemantauan'] ?? 'Perilaku';

        $updateData = [
            'rekomendasi_bk' => $validated['rekomendasi_bk'],
            'saran_untuk_guru' => $validated['saran_untuk_guru'] ?? null,
            'rekomendasi_bk_by' => $teacher ? $teacher->id : 12,
            'rekomendasi_bk_at' => Carbon::now(),
            'updated_by' => $user->id,
            'is_escalated_to_bk' => $masukBk,
        ];

        if (!empty($validated['status_pemantauan'])) {
            $updateData['status_pemantauan'] = $validated['status_pemantauan'];
        } else {
            $updateData['status_pemantauan'] = $masukBk ? 'Dalam Pemantauan' : 'Selesai / Cukup di Kelas';
        }

        $observation->update($updateData);

        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();

        if ($masukBk) {
            // Masukkan data ke Analisis & Monitoring BK
            $guruBk = $teacher ?: Teacher::where('jabatan', 'like', '%BK%')->first();

            $counselingCase = BkCounselingCase::firstOrCreate(
                [
                    'student_id' => $observation->student_id,
                    'academic_year_id' => $activeYear?->id,
                ],
                [
                    'class_id' => $observation->class_id,
                    'wali_kelas_id' => $observation->class_id ? DB::table('classes')->where('id', $observation->class_id)->value('wali_kelas_id') : null,
                    'guru_bk_id' => $guruBk?->id,
                    'tanggal_masuk' => $observation->tanggal,
                    'sumber_awal' => $observation->observer?->nama_lengkap ?? 'Guru Pelapor',
                    'fokus_pemantauan' => $fokus,
                    'status_saat_ini' => 'Dalam Pemantauan',
                    'review_berikutnya' => Carbon::now()->addDays(7)->format('Y-m-d'),
                    'catatan_umum' => "Rujukan dari Observasi Perilaku Siswa tanggal {$observation->tanggal->format('d/m/Y')}. Pelapor: " . ($observation->observer?->nama_lengkap ?? 'Guru Pelapor') . ".",
                    'created_by' => $user->id,
                ]
            );

            // Update fokus jika kasus sudah ada
            if (!$counselingCase->wasRecentlyCreated && $fokus) {
                $counselingCase->update(['fokus_pemantauan' => $fokus]);
            }

            // Catat log alur BK
            BkCaseLog::firstOrCreate(
                [
                    'bk_counseling_case_id' => $counselingCase->id,
                    'tanggal' => $observation->tanggal,
                    'tahap' => 'Informasi Masuk',
                ],
                [
                    'sumber_data' => $observation->observer?->nama_lengkap ?? 'Guru Pelapor',
                    'observasi_temuan' => $observation->perilaku_diamati . ($observation->situasi_sebelum ? " (Situasi Sebelum: {$observation->situasi_sebelum})" : ""),
                    'telaah_bk' => $validated['rekomendasi_bk'],
                    'kebutuhan_tujuan' => $validated['saran_untuk_guru'] ?? 'Pendampingan perilaku sesuai rekomendasi BK.',
                    'tindak_lanjut' => 'Diterima oleh Guru BK dan dimasukkan ke daftar pemantauan.',
                    'hasil_evaluasi' => 'Menunggu tindak lanjut observasi berkala.',
                    'perubahan_pola' => 'Belum Cukup Data',
                    'status' => 'Dalam Pemantauan',
                    'catatan_konteks' => 'Berdasarkan laporan catatan observasi kelas.',
                    'created_by' => $user->id,
                ]
            );
        } else {
            // Jika TIDAK masuk pemantauan BK: pastikan data tidak disimpan di Analisis & Monitoring
            $existingCase = BkCounselingCase::where('student_id', $observation->student_id)
                ->where('academic_year_id', $activeYear?->id)
                ->first();

            if ($existingCase) {
                // Hapus log awal yang berasal dari observasi ini jika ada
                BkCaseLog::where('bk_counseling_case_id', $existingCase->id)
                    ->where('tahap', 'Informasi Masuk')
                    ->where('tanggal', $observation->tanggal)
                    ->delete();

                // Jika kasus ini tidak memiliki log sesi lain sama sekali, hapus lembar kasusnya
                if ($existingCase->logs()->count() === 0) {
                    $existingCase->delete();
                }
            }
        }

        ActivityLogService::log(
            aksi: 'update',
            modul: 'observations',
            targetType: StudentObservation::class,
            targetId: $observation->id,
            dataSesudah: $observation->fresh()->toArray(),
            keterangan: "Guru BK memberikan tanggapan dan menentukan status pemantauan untuk observasi siswa ID {$observation->student_id}."
        );

        // Notifikasi ke Guru Pelapor bahwa laporan telah ditinjau dan ditanggapi oleh Guru BK
        if ($observation->teacher_id) {
            $student = $observation->student ?? Student::find($observation->student_id);
            $namaSiswa = $student ? $student->nama_lengkap : 'Siswa';
            NotificationService::notifyTeacher(
                teacher: $observation->teacher_id,
                title: "Tanggapan BK: Observasi Siswa {$namaSiswa}",
                message: "Guru BK telah menanggapi laporan observasi perilaku siswa {$namaSiswa}. Keputusan: " . ($masukBk ? 'Masuk Pemantauan BK' : 'Cukup Penanganan di Kelas') . ".",
                type: 'success',
                targetUrl: '/observations',
                sendEmail: true,
                extraDetails: [
                    'Nama Siswa' => $namaSiswa,
                    'Rekomendasi BK' => $validated['rekomendasi_bk'],
                    'Saran untuk Guru' => $validated['saran_untuk_guru'] ?? '-',
                    'Status Tindak Lanjut' => $masukBk ? 'Eskalasi ke Bimbingan Konseling (BK)' : 'Selesai di Tingkat Kelas',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => $masukBk 
                ? 'Tanggapan BK berhasil disimpan dan siswa dimasukkan ke Analisis & Monitoring BK.'
                : 'Tanggapan BK berhasil disimpan. Masalah diselesaikan di kelas dan tidak masuk ke Analisis & Monitoring BK.',
            'data' => $observation->fresh()->load(['student', 'schoolClass', 'observer', 'bkTeacher']),
        ]);
    }

    /**
     * Eskalasi / Rujuk Observasi ini ke Kasus Analisis & Monitoring BK.
     */
    public function escalateToBk(Request $request, StudentObservation $observation): JsonResponse
    {
        $validated = $request->validate([
            'fokus_pemantauan' => ['nullable', 'string', 'max:100'],
            'sumber_awal' => ['nullable', 'string', 'max:100'],
        ]);

        $fokus = $validated['fokus_pemantauan'] ?? 'Perilaku';
        $sumber = $validated['sumber_awal'] ?? ($observation->observer?->jabatan ?: 'Wali Kelas');

        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $guruBk = Teacher::where('jabatan', 'like', '%BK%')->first();

        // Cari atau buat kasus BK
        $counselingCase = BkCounselingCase::firstOrCreate(
            [
                'student_id' => $observation->student_id,
                'academic_year_id' => $activeYear?->id,
            ],
            [
                'class_id' => $observation->class_id,
                'wali_kelas_id' => $observation->class_id ? DB::table('classes')->where('id', $observation->class_id)->value('wali_kelas_id') : null,
                'guru_bk_id' => $guruBk?->id,
                'tanggal_masuk' => $observation->tanggal,
                'sumber_awal' => $sumber,
                'fokus_pemantauan' => $fokus,
                'status_saat_ini' => 'Dalam Pemantauan',
                'review_berikutnya' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'catatan_umum' => "Dirujuk dari Catatan Observasi Kelas tanggal {$observation->tanggal->format('d/m/Y')}.",
                'created_by' => $request->user()->id,
            ]
        );

        // Tandai observasi
        $observation->update([
            'is_escalated_to_bk' => true,
            'status_pemantauan' => 'Dalam Pemantauan',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Siswa berhasil dirujuk ke Sistem Analisis & Monitoring BK.',
            'data' => [
                'case' => $counselingCase->load(['student', 'schoolClass']),
                'observation' => $observation,
            ],
        ]);
    }

    /**
     * Hapus Catatan Observasi.
     */
    public function destroy(Request $request, StudentObservation $observation): JsonResponse
    {
        $old = $observation->toArray();
        $observation->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'observations',
            targetType: StudentObservation::class,
            targetId: $observation->id,
            dataSebelum: $old,
            keterangan: "Menghapus catatan observasi siswa."
        );

        return response()->json([
            'success' => true,
            'message' => 'Catatan observasi berhasil dihapus.',
        ]);
    }
}

