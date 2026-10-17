<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\TeachingEvidence;
use App\Services\ActivityLogService;
use App\Services\Export\AttendanceRecapExportService;
use App\Services\PeriodLockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentAttendanceController extends Controller
{
    public function sessionSheet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required_without:schedule_id', 'nullable', 'exists:classes,id'],
            'schedule_id' => ['required_without:class_id', 'nullable', 'exists:schedules,id'],
            'tanggal' => ['nullable', 'date'],
        ]);

        $tanggal = $validated['tanggal'] ?? now()->toDateString();
        $schedule = null;
        $classId = $validated['class_id'] ?? null;

        if (!empty($validated['schedule_id'])) {
            $schedule = Schedule::with(['schoolClass', 'subject', 'teacher'])->findOrFail($validated['schedule_id']);
            $classId = $schedule->class_id;
        }

        $schoolClass = SchoolClass::with(['academicYear', 'homeroomTeacher'])->findOrFail($classId);
        $user = $request->user();

        // Validasi: Presensi siswa harian hanya boleh diakses oleh Wali Kelas atau Admin/Kepala Sekolah
        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher || (int) $schoolClass->wali_kelas_id !== (int) $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presensi siswa harian hanya dapat diakses oleh Wali Kelas rombel ini.',
                    'data' => null,
                ], 403);
            }
        }

        // Ambil siswa aktif di kelas ini
        $students = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->select(['id', 'nis', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'foto_path'])
            ->get();

        // Cek data absensi harian yang sudah tercatat pada tanggal ini (1x sehari)
        $existingRecords = StudentAttendance::where('class_id', $classId)
            ->whereDate('tanggal', $tanggal)
            ->with('recorder:id,nama')
            ->get()
            ->keyBy('student_id');

        $isRecorded = $existingRecords->isNotEmpty();
        $summary = [
            'total' => $students->count(),
            'hadir' => $existingRecords->where('status', 'hadir')->count(),
            'izin' => $existingRecords->where('status', 'izin')->count(),
            'sakit' => $existingRecords->where('status', 'sakit')->count(),
            'alpa' => $existingRecords->where('status', 'alpa')->count(),
        ];
        $firstRecord = $existingRecords->first();
        $lastRecord = $existingRecords->sortByDesc('updated_at')->first();

        $items = $students->map(function ($student) use ($existingRecords) {
            $record = $existingRecords->get($student->id);
            return [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'nisn' => $student->nisn,
                'nama_lengkap' => $student->nama_lengkap,
                'jenis_kelamin' => $student->jenis_kelamin,
                'foto_path' => $student->foto_path,
                'status' => $record ? $record->status : 'hadir',
                'keterangan' => $record ? $record->keterangan : null,
                'attendance_id' => $record?->id,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Lembar presensi harian siswa berhasil disiapkan.',
            'data' => [
                'class' => $schoolClass,
                'tanggal' => $tanggal,
                'is_wali_kelas' => true,
                'already_recorded' => $isRecorded,
                'recorded_count' => $existingRecords->count(),
                'recorded_at' => $firstRecord?->created_at?->toIso8601String(),
                'updated_at' => $lastRecord?->updated_at?->toIso8601String(),
                'recorded_by' => $lastRecord?->recorder?->nama ?? $firstRecord?->recorder?->nama,
                'summary' => $summary,
                'students' => $items,
            ],
        ]);
    }

    /**
     * Monitoring Status Pengisian Presensi Harian Siswa oleh Seluruh Wali Kelas
     * Khusus untuk Administrator & Kepala Sekolah
     */
    public function homeroomMonitoring(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Administrator dan Kepala Sekolah yang dapat mengakses monitoring presensi rombel.',
            ], 403);
        }

        $tanggal = $request->query('tanggal', now()->toDateString());

        $classes = SchoolClass::with(['homeroomTeacher.user', 'academicYear'])
            ->orderBy('tingkat', 'asc')
            ->orderBy('nama', 'asc')
            ->get();

        $allClassAttendances = StudentAttendance::whereDate('tanggal', $tanggal)
            ->with(['recorder:id,nama'])
            ->get()
            ->groupBy('class_id');

        $activeStudentsCountPerClass = Student::where('status', 'aktif')
            ->selectRaw('class_id, count(*) as count')
            ->groupBy('class_id')
            ->pluck('count', 'class_id');

        $items = [];
        $totalClasses = $classes->count();
        $completedClasses = 0;
        $totalAllStudents = 0;
        $totalHadir = 0;
        $totalIzin = 0;
        $totalSakit = 0;
        $totalAlpa = 0;

        foreach ($classes as $cls) {
            $records = $allClassAttendances->get($cls->id, collect());
            $isRecorded = $records->isNotEmpty();
            if ($isRecorded) {
                $completedClasses++;
            }

            $studentCount = (int) ($activeStudentsCountPerClass[$cls->id] ?? 0);
            $totalAllStudents += $studentCount;

            $hadir = $records->where('status', 'hadir')->count();
            $izin = $records->where('status', 'izin')->count();
            $sakit = $records->where('status', 'sakit')->count();
            $alpa = $records->where('status', 'alpa')->count();

            $totalHadir += $hadir;
            $totalIzin += $izin;
            $totalSakit += $sakit;
            $totalAlpa += $alpa;

            $firstRecord = $records->first();
            $lastRecord = $records->sortByDesc('updated_at')->first();

            $items[] = [
                'class_id' => $cls->id,
                'class_nama' => $cls->nama,
                'class_kode' => $cls->kode,
                'tingkat' => $cls->tingkat,
                'wali_kelas_id' => $cls->wali_kelas_id,
                'wali_kelas_nama' => $cls->homeroomTeacher?->nama_lengkap ?? 'Belum Ditugaskan',
                'wali_kelas_nip' => $cls->homeroomTeacher?->nip,
                'is_recorded' => $isRecorded,
                'total_students' => $studentCount,
                'recorded_students_count' => $records->count(),
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'persentase_kehadiran' => $studentCount > 0 ? round(($hadir / $studentCount) * 100, 1) : 0,
                'recorded_at' => $firstRecord?->created_at?->toIso8601String(),
                'updated_at' => $lastRecord?->updated_at?->toIso8601String(),
                'recorded_by' => $lastRecord?->recorder?->nama ?? $firstRecord?->recorder?->nama,
            ];
        }

        $globalSummary = [
            'tanggal' => $tanggal,
            'total_classes' => $totalClasses,
            'completed_classes' => $completedClasses,
            'pending_classes' => max(0, $totalClasses - $completedClasses),
            'completion_rate' => $totalClasses > 0 ? round(($completedClasses / $totalClasses) * 100, 1) : 0,
            'total_students' => $totalAllStudents,
            'total_hadir' => $totalHadir,
            'total_izin' => $totalIzin,
            'total_sakit' => $totalSakit,
            'total_alpa' => $totalAlpa,
            'overall_attendance_rate' => $totalAllStudents > 0 ? round(($totalHadir / $totalAllStudents) * 100, 1) : 0,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Status monitoring presensi rombel berhasil diambil.',
            'data' => [
                'summary' => $globalSummary,
                'classes' => $items,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'schedule_id' => ['nullable', 'exists:schedules,id'],
            'tanggal' => ['sometimes', 'required', 'date'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'exists:students,id'],
            'attendances.*.status' => ['required', 'in:hadir,izin,sakit,alpa'],
            'attendances.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $tanggal = $validated['tanggal'] ?? now()->toDateString();
        $classId = (int) $validated['class_id'];
        $scheduleId = $validated['schedule_id'] ?? null;

        // Validasi: Apakah tanggal berada dalam rentang semester yang aktif dan tidak terkunci?
        PeriodLockService::validateDate($tanggal);

        $schoolClass = SchoolClass::findOrFail($classId);

        // Validasi Guru: Hanya Wali Kelas yang boleh mencatat presensi harian siswa (kecuali Admin/Kepala Sekolah)
        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher || (int) $schoolClass->wali_kelas_id !== (int) $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presensi siswa hanya dapat dilakukan sekali sehari oleh Wali Kelas rombel ini.',
                    'data' => null,
                ], 403);
            }

            // Validasi Pengabsenan Berulang: Wali Kelas tidak boleh mengabsen kedua kalinya jika sudah divalidasi/dicatat hari ini
            $alreadyRecorded = StudentAttendance::where('class_id', $classId)
                ->whereDate('tanggal', $tanggal)
                ->exists();
            if ($alreadyRecorded) {
                return response()->json([
                    'success' => false,
                    'message' => 'Presensi siswa untuk tanggal ' . $tanggal . ' telah tervalidasi dan sudah tercatat. Wali Kelas tidak dapat mengabsen untuk kedua kalinya. Silakan hubungi Administrator atau Kepala Sekolah jika memerlukan perbaikan data.',
                    'data' => null,
                ], 422);
            }
        }

        // Simpan bukti mengajar jika ada foto disertakan (opsional)
        if ($request->hasFile('foto') && $scheduleId) {
            $schedule = Schedule::with(['subject', 'schoolClass', 'teacher'])->find($scheduleId);
            $subjectName = $schedule?->subject?->nama ?? 'Mapel';
            $className = $schedule?->schoolClass?->nama ?? 'Kelas';
            $teacherName = $schedule?->teacher?->nama_lengkap ?? 'Guru';
            $watermark = "SIM-BIM | {$subjectName} ({$className}) | {$teacherName} | " . now()->format('d/m/Y H:i') . " WIB";

            $photoPath = app(\App\Services\PhotoCompressionService::class)->compressAndStore(
                $request->file('foto'),
                'attendance/evidence',
                $watermark
            );

            TeachingEvidence::updateOrCreate(
                [
                    'schedule_id' => $scheduleId,
                    'teacher_id' => $user->teacher?->id ?? $schedule->teacher_id,
                    'tanggal' => $tanggal,
                ],
                [
                    'photo_path' => $photoPath,
                    'materi_ajar' => $request->input('materi_ajar'),
                    'catatan' => $request->input('catatan_kbm') ?? $request->input('catatan'),
                    'lat' => $request->input('lat'),
                    'lng' => $request->input('lng'),
                    'captured_at' => now(),
                    'created_by' => $user->id,
                ]
            );
        }

        $recorded = [];
        $unattended = [];

        DB::transaction(function () use ($validated, $tanggal, $classId, $scheduleId, $user, &$recorded, &$unattended) {
            foreach ($validated['attendances'] as $item) {
                // Presensi siswa dicatat 1 kali sehari per siswa
                $record = StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $item['student_id'],
                        'tanggal' => $tanggal,
                    ],
                    [
                        'class_id' => $classId,
                        'schedule_id' => $scheduleId,
                        'status' => $item['status'],
                        'keterangan' => $item['keterangan'] ?? null,
                        'dicatat_oleh' => $user->id,
                        'updated_by' => $user->id,
                    ]
                );

                $recorded[] = $record;
                if ($item['status'] !== 'hadir') {
                    $unattended[] = [
                        'student_id' => $item['student_id'],
                        'status' => $item['status'],
                        'keterangan' => $item['keterangan'] ?? null,
                    ];
                }
            }
        });

        ActivityLogService::log(
            aksi: 'submit_student_attendance',
            modul: 'student_attendance',
            targetType: StudentAttendance::class,
            targetId: $recorded[0]->id ?? null,
            keterangan: "Mencatat presensi harian untuk " . count($recorded) . " siswa kelas ID #{$classId} pada tanggal {$tanggal}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Presensi harian siswa berhasil disimpan oleh Wali Kelas.',
            'data' => [
                'total_siswa' => count($recorded),
                'tidak_hadir' => count($unattended),
                'unattended_students' => $unattended,
            ],
        ], 200);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = StudentAttendance::with([
            'student:id,nis,nisn,nama_lengkap,jenis_kelamin,class_id,foto_path',
            'schoolClass:id,nama,tingkat',
            'schedule.subject:id,nama,kode,is_tematik,tema_nomor,muatan_pelajaran',
            'schedule.teacher:id,nama_lengkap,nip',
            'schedule.timeSlot:id,nama,jam_mulai,jam_selesai,urutan,hari',
            'schedule.room:id,nama',
            'recorder:id,nama',
        ]);

        if ($user->isGuardian()) {
            $guardian = $user->guardian;
            if (!$guardian) {
                return response()->json(['success' => true, 'data' => [], 'summary' => []]);
            }
            $childIds = $guardian->students()->pluck('students.id');
            $query->whereIn('student_id', $childIds);
        } elseif ($user->isTeacher() && !$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher) {
                return response()->json(['success' => true, 'data' => [], 'summary' => []]);
            }
            // Guru dapat melihat kelas binaannya (wali kelas) atau kelas yang diajar
            $homeroomClassIds = SchoolClass::where('wali_kelas_id', $teacher->id)->pluck('id');
            $assignedClassIds = Schedule::where('teacher_id', $teacher->id)->pluck('class_id');
            $directAssignmentIds = DB::table('teacher_assignments')->where('teacher_id', $teacher->id)->pluck('class_id');
            $allowedClassIds = $homeroomClassIds->merge($assignedClassIds)->merge($directAssignmentIds)->unique();

            $query->whereIn('class_id', $allowedClassIds);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->query('student_id'));
        }

        if ($request->filled('schedule_id')) {
            $query->where('schedule_id', $request->query('schedule_id'));
        }

        if ($request->filled('teacher_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('teacher_id', $request->query('teacher_id')));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->query('tanggal'));
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'))
                ->whereMonth('tanggal', $request->query('bulan'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $allRecords = (clone $query)->get();
        $total = $allRecords->count();
        $hadir = $allRecords->where('status', 'hadir')->count();
        $summary = [
            'total_record' => $total,
            'hadir' => $hadir,
            'izin' => $allRecords->where('status', 'izin')->count(),
            'sakit' => $allRecords->where('status', 'sakit')->count(),
            'alpa' => $allRecords->where('status', 'alpa')->count(),
            'persentase_kehadiran' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0,
        ];

        $perPage = min((int) $request->query('per_page', 25), 100);
        $attendances = $query->orderBy('tanggal', 'desc')->orderBy('student_id', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Rekap absensi murid berhasil diambil.',
            'data' => $attendances->items(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $attendance = StudentAttendance::with('student')->findOrFail($id);

        // Validasi: Apakah semester pada tanggal absensi ini terkunci?
        PeriodLockService::validateDate($attendance->tanggal);

        $user = $request->user();

        // FR-ABS-07: Batasan waktu edit oleh guru
        if (!$user->isAdmin()) {
            $limitHours = (int) Setting::get('student_attendance_edit_limit_hours', 24);
            if ($attendance->created_at->diffInHours(now()) > $limitHours) {
                return response()->json([
                    'success' => false,
                    'message' => "Batas waktu pengubahan absensi ({$limitHours} jam) telah berakhir. Perubahan setelah batas waktu hanya dapat dilakukan oleh Admin.",
                    'data' => null,
                ], 422);
            }
        }

        $rules = [
            'status' => ['required', 'in:hadir,izin,sakit,alpa'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];

        if ($user->isAdmin()) {
            $rules['alasan_koreksi'] = ['required', 'string', 'min:5', 'max:255'];
        }

        $validated = $request->validate($rules);

        $oldData = $attendance->toArray();

        $updateData = [
            'status' => $validated['status'],
            'keterangan' => $validated['keterangan'] ?? $attendance->keterangan,
            'updated_by' => $user->id,
        ];

        if (!empty($validated['alasan_koreksi'])) {
            $updateData['keterangan'] = ($updateData['keterangan'] ? $updateData['keterangan'] . ' | ' : '') . "[Koreksi Admin: {$validated['alasan_koreksi']}]";
        }

        $attendance->update($updateData);

        ActivityLogService::log(
            aksi: 'update_student_attendance',
            modul: 'student_attendance',
            targetType: StudentAttendance::class,
            targetId: $attendance->id,
            dataSebelum: $oldData,
            dataSesudah: $attendance->fresh()->toArray(),
            keterangan: "Mengubah status absensi siswa {$attendance->student->nama_lengkap} menjadi {$validated['status']}." . (!empty($validated['alasan_koreksi']) ? " Alasan: {$validated['alasan_koreksi']}." : "")
        );

        return response()->json([
            'success' => true,
            'message' => 'Data absensi siswa berhasil diperbarui.',
            'data' => $attendance->fresh(),
        ]);
    }

    public function childAttendance(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        if ($guardian) {
            $isChild = $guardian->students()->where('students.id', $studentId)->exists();
            if (!$isChild) {
                return response()->json([
                    'success' => false,
                    'message' => 'Siswa ini bukan anak atau tanggungan Anda.',
                ], 403);
            }
        }

        $student = Student::with('schoolClass')->findOrFail($studentId);

        $query = StudentAttendance::where('student_id', $studentId)
            ->with(['schedule.subject', 'schoolClass']);

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'))
                ->whereMonth('tanggal', $request->query('bulan'));
        }

        $allRecords = (clone $query)->get();
        $total = $allRecords->count();
        $hadir = $allRecords->where('status', 'hadir')->count();

        $summary = [
            'total_pertemuan' => $total,
            'hadir' => $hadir,
            'izin' => $allRecords->where('status', 'izin')->count(),
            'sakit' => $allRecords->where('status', 'sakit')->count(),
            'alpa' => $allRecords->where('status', 'alpa')->count(),
            'persentase_kehadiran' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0,
        ];

        $perPage = min((int) $request->query('per_page', 30), 100);
        $attendances = $query->orderBy('tanggal', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => "Riwayat kehadiran {$student->nama_lengkap} berhasil diambil.",
            'data' => [
                'student' => $student,
                'summary' => $summary,
                'records' => $attendances->items(),
                'meta' => [
                    'current_page' => $attendances->currentPage(),
                    'last_page' => $attendances->lastPage(),
                    'per_page' => $attendances->perPage(),
                    'total' => $attendances->total(),
                ],
            ],
        ]);
    }

    /**
     * Rekap Presensi Per Kelas & Mapel (FR-ABS)
     */
    public function classRecap(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $classId = (int) $validated['class_id'];
        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $subjectId = !empty($validated['subject_id']) ? (int) $validated['subject_id'] : null;

        // Validasi Hak Akses Guru
        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
            $isHomeroom = SchoolClass::where('id', $classId)->where('wali_kelas_id', $teacher->id)->exists();
            $isAssigned = Schedule::where('class_id', $classId)->where('teacher_id', $teacher->id)->exists();
            $isDirectAssigned = DB::table('teacher_assignments')->where('class_id', $classId)->where('teacher_id', $teacher->id)->exists();
            if (!$isHomeroom && !$isAssigned && !$isDirectAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke rekap kelas ini.',
                ], 403);
            }
        }

        $recap = $this->buildClassRecapData($classId, $startDate, $endDate, $subjectId, $validated['search'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Data rekap presensi kelas berhasil dimuat.',
            'data' => $recap,
        ]);
    }

    /**
     * Export Rekap Presensi Per Kelas & Mapel ke Excel
     */
    public function exportClassRecap(Request $request, AttendanceRecapExportService $exportService): StreamedResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
        ]);

        $classId = (int) $validated['class_id'];
        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $subjectId = !empty($validated['subject_id']) ? (int) $validated['subject_id'] : null;

        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher) {
                abort(403, 'Akses ditolak.');
            }
            $isHomeroom = SchoolClass::where('id', $classId)->where('wali_kelas_id', $teacher->id)->exists();
            $isAssigned = Schedule::where('class_id', $classId)->where('teacher_id', $teacher->id)->exists();
            $isDirectAssigned = DB::table('teacher_assignments')->where('class_id', $classId)->where('teacher_id', $teacher->id)->exists();
            if (!$isHomeroom && !$isAssigned && !$isDirectAssigned) {
                abort(403, 'Anda tidak memiliki akses ke rekap kelas ini.');
            }
        }

        $schoolClass = SchoolClass::with(['homeroomTeacher', 'academicYear'])->findOrFail($classId);
        $subject = $subjectId ? Subject::find($subjectId) : null;
        $recap = $this->buildClassRecapData($classId, $startDate, $endDate, $subjectId);

        return $exportService->exportClassRecap($schoolClass, $recap, $startDate, $endDate, $subject);
    }

    /**
     * Helper penyusun data agregasi presensi kelas
     */
    protected function buildClassRecapData(int $classId, string $startDate, string $endDate, ?int $subjectId = null, ?string $search = null): array
    {
        $schoolClass = SchoolClass::with(['homeroomTeacher', 'academicYear'])->findOrFail($classId);

        // Ambil murid di kelas ini
        $studentsQuery = Student::where('class_id', $classId)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc');

        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery->get(['id', 'nis', 'nisn', 'nama_lengkap', 'jenis_kelamin', 'foto_path']);

        // Ambil semua absensi siswa pada rentang waktu ini
        $attendancesQuery = StudentAttendance::where('class_id', $classId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->with(['schedule.subject', 'schedule.teacher', 'schedule.timeSlot']);

        if ($subjectId) {
            $attendancesQuery->whereHas('schedule', fn($q) => $q->where('subject_id', $subjectId));
        }

        $attendances = $attendancesQuery->get();

        // Hitung total pertemuan KBM yang terlaksana dalam rentang ini
        // Sesi dihitung dari kombinasi unik tanggal dan jadwal (atau per hari jika tanpa jadwal)
        $sessionKeys = $attendances->map(function ($att) {
            return $att->tanggal->toDateString() . '_' . ($att->schedule_id ?? 'general');
        })->unique();
        $totalMeetings = $sessionKeys->count();

        // Rekap per mata pelajaran yang diajarkan pada kelas ini
        $schedulesInClass = Schedule::where('class_id', $classId)
            ->with(['subject', 'teacher'])
            ->get();

        $subjectsMap = [];
        foreach ($schedulesInClass as $sch) {
            if ($sch->subject) {
                $sid = $sch->subject_id;
                if (!isset($subjectsMap[$sid])) {
                    $subjectsMap[$sid] = [
                        'subject_id' => $sid,
                        'subject_nama' => $sch->subject->nama,
                        'subject_kode' => $sch->subject->kode,
                        'teacher_nama' => $sch->teacher?->nama_lengkap ?? '-',
                        'total_pertemuan' => 0,
                    ];
                }
            }
        }

        // Hitung pertemuan tiap subject dari data absensi
        $subjectSessions = $attendances->whereNotNull('schedule_id')->groupBy(function ($att) {
            return ($att->schedule?->subject_id ?? 0) . '_' . $att->tanggal->toDateString() . '_' . $att->schedule_id;
        });

        foreach ($subjectSessions as $group) {
            $first = $group->first();
            $sid = $first->schedule?->subject_id;
            if ($sid && isset($subjectsMap[$sid])) {
                $subjectsMap[$sid]['total_pertemuan']++;
            }
        }

        // Kelompokkan data per siswa
        $studentAttendancesGrouped = $attendances->groupBy('student_id');

        $studentRecap = [];
        $classTotalHadir = 0;
        $classTotalIzin = 0;
        $classTotalSakit = 0;
        $classTotalAlpa = 0;

        foreach ($students as $stu) {
            $stuRecords = $studentAttendancesGrouped->get($stu->id, collect());
            $hadir = $stuRecords->where('status', 'hadir')->count();
            $izin = $stuRecords->where('status', 'izin')->count();
            $sakit = $stuRecords->where('status', 'sakit')->count();
            $alpa = $stuRecords->where('status', 'alpa')->count();
            $totalRecorded = $hadir + $izin + $sakit + $alpa;

            // Dasar pembagi persentase
            $denominator = $totalMeetings > 0 ? $totalMeetings : ($totalRecorded > 0 ? $totalRecorded : 1);
            $persentase = round(($hadir / $denominator) * 100, 1);
            if ($persentase > 100) {
                $persentase = 100.0;
            }

            // Rincian per mapel untuk siswa ini (siap dihubungkan ke rapor)
            $perSubject = [];
            foreach ($subjectsMap as $sid => $subMeta) {
                $subRecords = $stuRecords->filter(fn($r) => $r->schedule?->subject_id == $sid);
                $subH = $subRecords->where('status', 'hadir')->count();
                $subI = $subRecords->where('status', 'izin')->count();
                $subS = $subRecords->where('status', 'sakit')->count();
                $subA = $subRecords->where('status', 'alpa')->count();
                $subTotalRec = $subH + $subI + $subS + $subA;
                $subTotalMeetings = $subMeta['total_pertemuan'];
                $subDenom = $subTotalMeetings > 0 ? $subTotalMeetings : max(1, $subTotalRec);
                $subPercent = round(($subH / $subDenom) * 100, 1);

                $perSubject[] = [
                    'subject_id' => $sid,
                    'subject_nama' => $subMeta['subject_nama'],
                    'subject_kode' => $subMeta['subject_kode'],
                    'total_pertemuan' => $subTotalMeetings,
                    'hadir' => $subH,
                    'izin' => $subI,
                    'sakit' => $subS,
                    'alpa' => $subA,
                    'persentase' => min(100.0, $subPercent),
                ];
            }

            $classTotalHadir += $hadir;
            $classTotalIzin += $izin;
            $classTotalSakit += $sakit;
            $classTotalAlpa += $alpa;

            $studentRecap[] = [
                'student_id' => $stu->id,
                'nis' => $stu->nis,
                'nisn' => $stu->nisn,
                'nama_lengkap' => $stu->nama_lengkap,
                'jenis_kelamin' => $stu->jenis_kelamin,
                'foto_path' => $stu->foto_path,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total_recorded' => $totalRecorded,
                'persentase_kehadiran' => $persentase,
                'subjects' => $perSubject,
            ];
        }

        $classTotalRecorded = $classTotalHadir + $classTotalIzin + $classTotalSakit + $classTotalAlpa;
        $classAvgPersen = $classTotalRecorded > 0 ? round(($classTotalHadir / $classTotalRecorded) * 100, 1) : 0;

        return [
            'class' => $schoolClass,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_subject_id' => $subjectId,
            'total_students' => $students->count(),
            'total_meetings' => $totalMeetings,
            'subjects' => array_values($subjectsMap),
            'summary' => [
                'total_hadir' => $classTotalHadir,
                'total_sakit' => $classTotalSakit,
                'total_izin' => $classTotalIzin,
                'total_alpa' => $classTotalAlpa,
                'total_recorded' => $classTotalRecorded,
                'persentase_kehadiran' => $classAvgPersen,
            ],
            'students' => $studentRecap,
        ];
    }
}
