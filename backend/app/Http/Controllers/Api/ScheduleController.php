<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Services\ActivityLogService;
use App\Services\ScheduleConflictService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        protected ScheduleConflictService $conflictService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Schedule::with([
            'academicYear:id,nama,status',
            'semester:id,semester,status',
            'schoolClass' => function ($q) {
                $q->select('id', 'nama', 'kode', 'tingkat')->withCount('activeStudents');
            },
            'subject:id,nama,kode,kelompok,is_tematik,tema_nomor,muatan_pelajaran',
            'teacher:id,nama_lengkap,nip',
            'room:id,nama,kode',
            'timeSlot:id,nama,jam_mulai,jam_selesai,urutan,hari',
        ]);

        $today = $request->query('tanggal', now()->toDateString());
        $query->withCount([
            'studentAttendances as today_attendance_count' => function ($q) use ($today) {
                $q->whereDate('tanggal', $today);
            },
            'teachingEvidences as today_evidence_count' => function ($q) use ($today) {
                $q->whereDate('tanggal', $today);
            },
        ]);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        } else {
            $activeYear = AcademicYear::getActive();
            if ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            }
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        // Filter: khusus jadwal guru yang sedang login (my_schedules)
        if ($request->boolean('my_schedules')) {
            $teacher = $request->user()->teacher;
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($request->user() && !$request->user()->isAdmin() && $request->user()->isTeacher() && !$request->boolean('all')) {
            // Jika user adalah Guru (bukan Admin) dan tidak meminta 'all', otomatis tampilkan jadwal miliknya saja
            $teacher = $request->user()->teacher;
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            }
        } elseif ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        if ($request->boolean('today')) {
            $days = [
                0 => 'minggu',
                1 => 'senin',
                2 => 'selasa',
                3 => 'rabu',
                4 => 'kamis',
                5 => 'jumat',
                6 => 'sabtu',
            ];
            $todayName = $days[now()->dayOfWeek] ?? 'senin';
            $query->where('hari', $todayName);
        } elseif ($request->filled('hari')) {
            $query->where('hari', $request->query('hari'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->query('all') === 'true' || $request->filled('class_id')) {
            $allSchedules = $query->orderByRaw("CASE hari 
                WHEN 'senin' THEN 1 
                WHEN 'selasa' THEN 2 
                WHEN 'rabu' THEN 3 
                WHEN 'kamis' THEN 4 
                WHEN 'jumat' THEN 5 
                WHEN 'sabtu' THEN 6 
                ELSE 7 END ASC")
                ->orderBy('time_slot_id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Jadwal pelajaran berhasil diambil.',
                'data' => $allSchedules,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $allSchedules->count(),
                    'total' => $allSchedules->count(),
                ],
            ]);
        }

        $schedules = $query->orderByRaw("CASE hari 
            WHEN 'senin' THEN 1 
            WHEN 'selasa' THEN 2 
            WHEN 'rabu' THEN 3 
            WHEN 'kamis' THEN 4 
            WHEN 'jumat' THEN 5 
            WHEN 'sabtu' THEN 6 
            ELSE 7 END ASC")
            ->orderBy('time_slot_id', 'asc')
            ->paginate(min((int) $request->query('per_page', 50), 100));

        return response()->json([
            'success' => true,
            'message' => 'Jadwal pelajaran berhasil diambil.',
            'data' => $schedules->items(),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'hari' => ['required', 'in:senin,selasa,rabu,kamis,jumat,sabtu'],
            'time_slot_id' => ['required', 'exists:time_slots,id'],
            'judul_kegiatan' => ['nullable', 'string', 'max:100'],
            'tipe_kegiatan' => ['nullable', 'in:kbm,pembiasaan,istirahat,ekskul'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
        ]);

        // If subject_id is empty, require judul_kegiatan
        if (empty($validated['subject_id']) && empty($validated['judul_kegiatan'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih mata pelajaran atau masukkan judul kegiatan.',
            ], 422);
        }

        // Validate schedule conflicts (FR-DATA-13)
        $this->conflictService->validateNoConflict($validated);

        $validated['tipe_kegiatan'] = $validated['tipe_kegiatan'] ?? (!empty($validated['subject_id']) ? 'kbm' : 'pembiasaan');
        $validated['status'] = $validated['status'] ?? 'aktif';
        $validated['created_by'] = auth()->id();
        $schedule = Schedule::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'schedules',
            targetType: Schedule::class,
            targetId: $schedule->id,
            dataSesudah: $schedule->toArray(),
            keterangan: "Menambahkan jadwal pelajaran baru ID: {$schedule->id} ({$schedule->hari})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jadwal pelajaran berhasil ditambahkan.',
            'data' => $schedule->load(['academicYear', 'schoolClass', 'subject', 'teacher', 'room', 'timeSlot']),
        ], 201);
    }

    public function show(Schedule $schedule): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail jadwal pelajaran berhasil diambil.',
            'data' => $schedule->load(['academicYear', 'schoolClass', 'subject', 'teacher', 'room', 'timeSlot']),
        ]);
    }

    public function update(Request $request, Schedule $schedule): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['sometimes', 'required', 'exists:academic_years,id'],
            'class_id' => ['sometimes', 'required', 'exists:classes,id'],
            'subject_id' => ['sometimes', 'required', 'exists:subjects,id'],
            'teacher_id' => ['sometimes', 'required', 'exists:teachers,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'hari' => ['sometimes', 'required', 'in:senin,selasa,rabu,kamis,jumat,sabtu'],
            'time_slot_id' => ['sometimes', 'required', 'exists:time_slots,id'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
        ]);

        $mergedData = array_merge($schedule->toArray(), $validated);

        // Validate conflicts excluding current schedule
        $this->conflictService->validateNoConflict($mergedData, $schedule->id);

        $oldData = $schedule->toArray();
        $validated['updated_by'] = auth()->id();
        $schedule->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'schedules',
            targetType: Schedule::class,
            targetId: $schedule->id,
            dataSebelum: $oldData,
            dataSesudah: $schedule->fresh()->toArray(),
            keterangan: "Memperbarui jadwal pelajaran ID: {$schedule->id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jadwal pelajaran berhasil diperbarui.',
            'data' => $schedule->fresh()->load(['academicYear', 'schoolClass', 'subject', 'teacher', 'room', 'timeSlot']),
        ]);
    }

    public function destroy(Schedule $schedule): JsonResponse
    {
        if ($schedule->studentAttendances()->exists() || $schedule->teachingEvidences()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal pelajaran tidak dapat dihapus karena sudah memiliki rekaman absensi atau bukti mengajar.',
                'data' => null,
            ], 422);
        }

        $oldData = $schedule->toArray();
        $schedule->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'schedules',
            targetType: Schedule::class,
            targetId: $schedule->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus jadwal pelajaran ID: {$schedule->id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jadwal pelajaran berhasil dihapus.',
            'data' => null,
        ]);
    }

    public function quickSet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'hari' => ['required', 'in:senin,selasa,rabu,kamis,jumat,sabtu'],
            'time_slot_id' => ['required', 'exists:time_slots,id'],
            'action' => ['required', 'in:save,set,delete'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'judul_kegiatan' => ['nullable', 'string', 'max:100'],
            'tipe_kegiatan' => ['nullable', 'in:kbm,pembiasaan,istirahat,ekskul'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'jam_mulai' => ['nullable', 'string'],
            'jam_selesai' => ['nullable', 'string'],
            'force' => ['nullable', 'boolean'],
        ]);

        $existing = Schedule::where('academic_year_id', $validated['academic_year_id'])
            ->where('class_id', $validated['class_id'])
            ->where('hari', $validated['hari'])
            ->where('time_slot_id', $validated['time_slot_id'])
            ->first();

        if ($validated['action'] === 'delete') {
            if ($existing) {
                $existing->delete();
            }
            return response()->json([
                'success' => true,
                'message' => 'Slot jadwal berhasil dikosongkan.',
            ]);
        }

        // Optional: Update time slot jam if changed
        if (!empty($validated['jam_mulai']) && !empty($validated['jam_selesai'])) {
            $slot = \App\Models\TimeSlot::find($validated['time_slot_id']);
            if ($slot) {
                $slot->update([
                    'jam_mulai' => $validated['jam_mulai'],
                    'jam_selesai' => $validated['jam_selesai'],
                ]);
            }
        }

        // Validate conflicts if KBM unless forced
        if (!$request->boolean('force')) {
            $this->conflictService->validateNoConflict($validated, $existing?->id);
        }

        $payload = [
            'academic_year_id' => $validated['academic_year_id'],
            'class_id' => $validated['class_id'],
            'hari' => $validated['hari'],
            'time_slot_id' => $validated['time_slot_id'],
            'subject_id' => $validated['subject_id'] ?? null,
            'teacher_id' => $validated['teacher_id'] ?? null,
            'room_id' => $validated['room_id'] ?? null,
            'judul_kegiatan' => $validated['judul_kegiatan'] ?? null,
            'tipe_kegiatan' => $validated['tipe_kegiatan'] ?? (!empty($validated['subject_id']) ? 'kbm' : 'pembiasaan'),
            'catatan' => $validated['catatan'] ?? null,
            'status' => 'aktif',
            'updated_by' => auth()->id(),
        ];

        if ($existing) {
            $existing->update($payload);
            $schedule = $existing;
        } else {
            $payload['created_by'] = auth()->id();
            $schedule = Schedule::create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil disimpan.',
            'data' => $schedule->load(['academicYear', 'schoolClass', 'subject', 'teacher', 'room', 'timeSlot']),
        ]);
    }

    public function clearClassSchedules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
        ]);

        Schedule::where('class_id', $validated['class_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seluruh jadwal untuk kelas ini berhasil dibersihkan.',
        ]);
    }

    public function applyOfficialTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
        ]);

        $targetClassId = (int)$validated['class_id'];
        $ayId = (int)$validated['academic_year_id'];

        // Get template schedules from class 3
        $templates = Schedule::where('class_id', 3)
            ->where('academic_year_id', $ayId)
            ->get();

        if ($templates->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Template jadwal resmi kelas 3 tidak ditemukan di database.',
            ], 404);
        }

        // Target class
        $targetClass = \App\Models\SchoolClass::find($targetClassId);

        // Delete existing schedules for this target class
        Schedule::where('class_id', $targetClassId)
            ->where('academic_year_id', $ayId)
            ->delete();

        // Replicate records
        foreach ($templates as $t) {
            Schedule::create([
                'academic_year_id' => $ayId,
                'class_id' => $targetClassId,
                'hari' => $t->hari,
                'time_slot_id' => $t->time_slot_id,
                'subject_id' => $t->subject_id,
                'teacher_id' => $t->teacher_id,
                'room_id' => $targetClass?->room_id ?? $t->room_id,
                'judul_kegiatan' => $t->judul_kegiatan,
                'tipe_kegiatan' => $t->tipe_kegiatan,
                'catatan' => $t->catatan,
                'status' => 'aktif',
                'created_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Jadwal resmi berhasil diterapkan untuk {$targetClass->nama}.",
        ]);
    }
}
