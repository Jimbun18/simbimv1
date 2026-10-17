<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Services\ActivityLogService;
use App\Services\Export\MasterDataExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    public function exportExcel(Request $request, MasterDataExportService $exportService): StreamedResponse
    {
        return $exportService->exportClasses($request);
    }
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = SchoolClass::with(['academicYear', 'homeroomTeacher', 'room'])
            ->withCount(['enrollments', 'activeStudents']);

        // RBAC: Jika pengguna adalah Guru (bukan admin), batasi hanya rombel yang diampu (wali kelas / jadwal mengajar)
        if ($user && $user->isTeacher() && !$user->isAdmin()) {
            $teacher = $user->teacher;
            if ($teacher) {
                $homeroomIds = SchoolClass::where('wali_kelas_id', $teacher->id)->pluck('id');
                $assignedIds = DB::table('teacher_assignments')->where('teacher_id', $teacher->id)->pluck('class_id');
                $scheduledIds = DB::table('schedules')->where('teacher_id', $teacher->id)->pluck('class_id');
                $allowedIds = $homeroomIds->merge($assignedIds)->merge($scheduledIds)->unique()->filter()->values()->toArray();

                $query->whereIn('id', $allowedIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        } else {
            // Default to active year if available
            $activeYear = AcademicYear::getActive();
            if ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            }
        }

        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->query('tingkat'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('all') || $request->query('per_page') === 'all') {
            $allClasses = $query->orderBy('tingkat')->orderBy('kode')->get();
            return response()->json([
                'success' => true,
                'message' => 'Daftar semua rombel / kelas berhasil diambil.',
                'data' => $allClasses,
            ]);
        }

        $classes = $query->orderBy('tingkat')->orderBy('kode')->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json([
            'success' => true,
            'message' => 'Daftar rombel / kelas berhasil diambil.',
            'data' => $classes->items(),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'total' => $classes->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'kode' => ['required', 'string', 'max:10'],
            'nama' => ['required', 'string', 'max:50'],
            'tingkat' => ['required', 'integer', 'between:1,6'],
            'paralel' => ['nullable', 'string', 'max:5'],
            'wali_kelas_id' => ['nullable', 'exists:teachers,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
        ]);

        // Unique kode per academic year check
        $exists = SchoolClass::where('academic_year_id', $validated['academic_year_id'])
            ->where('kode', $validated['kode'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "Kelas dengan kode {$validated['kode']} sudah ada pada tahun ajaran ini.",
                'data' => null,
            ], 422);
        }

        // One homeroom teacher per class per academic year
        if (!empty($validated['wali_kelas_id'])) {
            $teacherAssigned = SchoolClass::where('academic_year_id', $validated['academic_year_id'])
                ->where('wali_kelas_id', $validated['wali_kelas_id'])
                ->exists();

            if ($teacherAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guru ini sudah menjadi wali kelas pada kelas lain di tahun ajaran yang sama.',
                    'data' => null,
                ], 422);
            }
        }

        $validated['status'] = $validated['status'] ?? 'aktif';
        $validated['created_by'] = auth()->id();
        $class = SchoolClass::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'classes',
            targetType: SchoolClass::class,
            targetId: $class->id,
            dataSesudah: $class->toArray(),
            keterangan: "Membuat rombel baru: {$class->nama} ({$class->kode})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Rombel berhasil dibuat.',
            'data' => $class->load(['academicYear', 'homeroomTeacher', 'room']),
        ], 201);
    }

    public function show(SchoolClass $class): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail rombel berhasil diambil.',
            'data' => $class->load(['academicYear', 'homeroomTeacher', 'room', 'activeStudents']),
        ]);
    }

    public function update(Request $request, SchoolClass $class): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['sometimes', 'required', 'exists:academic_years,id'],
            'kode' => ['sometimes', 'required', 'string', 'max:10'],
            'nama' => ['sometimes', 'required', 'string', 'max:50'],
            'tingkat' => ['sometimes', 'required', 'integer', 'between:1,6'],
            'paralel' => ['nullable', 'string', 'max:5'],
            'wali_kelas_id' => ['nullable', 'exists:teachers,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
        ]);

        $targetYear = $validated['academic_year_id'] ?? $class->academic_year_id;
        $targetKode = $validated['kode'] ?? $class->kode;

        if (isset($validated['kode']) || isset($validated['academic_year_id'])) {
            $duplicate = SchoolClass::where('academic_year_id', $targetYear)
                ->where('kode', $targetKode)
                ->where('id', '!=', $class->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => "Kelas dengan kode {$targetKode} sudah terdaftar pada tahun ajaran ini.",
                    'data' => null,
                ], 422);
            }
        }

        if (!empty($validated['wali_kelas_id'])) {
            $teacherAssigned = SchoolClass::where('academic_year_id', $targetYear)
                ->where('wali_kelas_id', $validated['wali_kelas_id'])
                ->where('id', '!=', $class->id)
                ->exists();

            if ($teacherAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guru ini sudah menjadi wali kelas pada rombel lain di tahun ajaran ini.',
                    'data' => null,
                ], 422);
            }
        }

        $oldData = $class->toArray();
        $validated['updated_by'] = auth()->id();
        $class->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'classes',
            targetType: SchoolClass::class,
            targetId: $class->id,
            dataSebelum: $oldData,
            dataSesudah: $class->fresh()->toArray(),
            keterangan: "Memperbarui data rombel: {$class->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data rombel berhasil diperbarui.',
            'data' => $class->fresh()->load(['academicYear', 'homeroomTeacher', 'room']),
        ]);
    }

    public function destroy(SchoolClass $class): JsonResponse
    {
        if ($class->activeStudents()->exists() || $class->schedules()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Rombel tidak dapat dihapus karena masih memiliki siswa aktif atau jadwal pelajaran.',
                'data' => null,
            ], 422);
        }

        $oldData = $class->toArray();
        $class->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'classes',
            targetType: SchoolClass::class,
            targetId: $class->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus rombel: {$class->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Rombel berhasil dihapus.',
            'data' => null,
        ]);
    }
}
