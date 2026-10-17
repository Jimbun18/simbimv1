<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\TeacherAssignment;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TeacherAssignment::with(['teacher:id,nama_lengkap,nip', 'subject:id,nama,kode', 'schoolClass:id,nama,kode', 'academicYear:id,nama,status', 'semesterModel:id,semester,status']);

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        } else {
            $activeYear = AcademicYear::getActive();
            if ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            }
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->boolean('all')) {
            $assignments = $query->orderBy('class_id')->orderBy('subject_id')->get();
            return response()->json([
                'success' => true,
                'message' => 'Seluruh penugasan guru berhasil diambil.',
                'data' => $assignments,
            ]);
        }

        $assignments = $query->orderBy('class_id')->orderBy('subject_id')->paginate(min((int) $request->query('per_page', 50), 200));

        return response()->json([
            'success' => true,
            'message' => 'Daftar penugasan guru berhasil diambil.',
            'data' => $assignments->items(),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'jam_per_minggu' => ['nullable', 'integer', 'min:1', 'max:60'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'jam_per_minggu.max' => 'Beban jam pelajaran per minggu maksimal 60 JP.',
            'jam_per_minggu.min' => 'Beban jam pelajaran per minggu minimal 1 JP.',
        ]);

        if (empty($validated['academic_year_id'])) {
            $activeYear = AcademicYear::getActive() ?? AcademicYear::latest()->first();
            $validated['academic_year_id'] = $activeYear?->id;
        }

        $exists = TeacherAssignment::where('teacher_id', $validated['teacher_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('class_id', $validated['class_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Penugasan guru untuk mata pelajaran dan kelas ini sudah terdaftar pada tahun ajaran tersebut.',
                'data' => null,
            ], 422);
        }

        $validated['created_by'] = auth()->id();
        $assignment = TeacherAssignment::create($validated);

        // Otomatis sinkronkan jadwal mingguan yang cocok
        $this->syncAssignmentToSchedule($assignment);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'teacher_assignments',
            targetType: TeacherAssignment::class,
            targetId: $assignment->id,
            dataSesudah: $assignment->toArray(),
            keterangan: "Menetapkan penugasan mengajar untuk guru ID {$assignment->teacher_id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Penugasan guru berhasil dibuat dan otomatis terhubung ke jadwal mingguan.',
            'data' => $assignment->load(['teacher', 'subject', 'schoolClass', 'academicYear']),
        ], 201);
    }

    public function show(TeacherAssignment $teacherAssignment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail penugasan guru berhasil diambil.',
            'data' => $teacherAssignment->load(['teacher', 'subject', 'schoolClass', 'academicYear']),
        ]);
    }

    public function update(Request $request, TeacherAssignment $teacherAssignment): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['sometimes', 'required', 'exists:teachers,id'],
            'subject_id' => ['sometimes', 'required', 'exists:subjects,id'],
            'class_id' => ['sometimes', 'required', 'exists:classes,id'],
            'academic_year_id' => ['sometimes', 'required', 'exists:academic_years,id'],
            'jam_per_minggu' => ['nullable', 'integer', 'min:1', 'max:60'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'jam_per_minggu.max' => 'Beban jam pelajaran per minggu maksimal 60 JP.',
            'jam_per_minggu.min' => 'Beban jam pelajaran per minggu minimal 1 JP.',
        ]);

        $oldData = $teacherAssignment->toArray();
        $validated['updated_by'] = auth()->id();
        $teacherAssignment->update($validated);

        // Otomatis perbarui guru di jadwal mingguan yang cocok
        $this->syncAssignmentToSchedule($teacherAssignment->fresh());

        ActivityLogService::log(
            aksi: 'update',
            modul: 'teacher_assignments',
            targetType: TeacherAssignment::class,
            targetId: $teacherAssignment->id,
            dataSebelum: $oldData,
            dataSesudah: $teacherAssignment->fresh()->toArray(),
            keterangan: "Memperbarui penugasan mengajar guru ID {$teacherAssignment->teacher_id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Penugasan guru berhasil diperbarui dan jadwal mingguan otomatis disinkronkan.',
            'data' => $teacherAssignment->fresh()->load(['teacher', 'subject', 'schoolClass', 'academicYear']),
        ]);
    }

    public function destroy(TeacherAssignment $teacherAssignment): JsonResponse
    {
        $oldData = $teacherAssignment->toArray();
        $teacherAssignment->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'teacher_assignments',
            targetType: TeacherAssignment::class,
            targetId: $teacherAssignment->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus penugasan guru."
        );

        return response()->json([
            'success' => true,
            'message' => 'Penugasan guru berhasil dihapus.',
            'data' => null,
        ]);
    }

    /**
     * Sinkronkan seluruh penugasan guru ke jadwal mingguan
     */
    public function syncAllToSchedules(Request $request): JsonResponse
    {
        $activeYear = AcademicYear::getActive() ?? AcademicYear::latest()->first();
        if (!$activeYear) {
            return response()->json(['success' => false, 'message' => 'Tahun ajaran tidak aktif.'], 400);
        }

        $assignments = TeacherAssignment::where('academic_year_id', $activeYear->id)->get();
        $updatedCount = 0;

        foreach ($assignments as $a) {
            $updatedCount += $this->syncAssignmentToSchedule($a);
        }

        return response()->json([
            'success' => true,
            'message' => "Jadwal mingguan berhasil disinkronkan ({$updatedCount} slot jadwal terhubung langsung).",
            'data' => ['updated_slots' => $updatedCount],
        ]);
    }

    /**
     * Helper privat untuk sinkronisasi 1 penugasan ke jadwal mingguan
     */
    private function syncAssignmentToSchedule(TeacherAssignment $a): int
    {
        $query = Schedule::where('academic_year_id', $a->academic_year_id)
            ->where('class_id', $a->class_id)
            ->where('subject_id', $a->subject_id);

        if ($a->keterangan && str_contains(strtolower($a->keterangan), 'pagi')) {
            $query->whereHas('timeSlot', fn($q) => $q->where('jam_mulai', '<', '09:20:00'));
        } elseif ($a->keterangan && str_contains(strtolower($a->keterangan), 'siang')) {
            $query->whereHas('timeSlot', fn($q) => $q->where('jam_mulai', '>=', '09:20:00'));
        }

        return $query->update(['teacher_id' => $a->teacher_id]);
    }
}
