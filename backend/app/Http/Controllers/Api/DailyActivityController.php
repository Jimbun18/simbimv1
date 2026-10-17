<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyActivity;
use App\Models\InAppNotification;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\PhotoCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DailyActivityController extends Controller
{
    public function __construct(
        protected PhotoCompressionService $photoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = DailyActivity::with([
            'schoolClass:id,nama,tingkat',
            'teacher:id,nama_lengkap',
        ]);

        if ($user->isGuardian()) {
            $guardian = $user->guardian;
            if (!$guardian) {
                return response()->json(['success' => true, 'data' => []]);
            }
            // Wali hanya dapat melihat kegiatan di kelas anak-anaknya
            $classIds = $guardian->students()->pluck('students.class_id')->unique();
            $query->whereIn('class_id', $classIds);
        } elseif ($user->isTeacher() && !$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $homeroomClassIds = SchoolClass::where('wali_kelas_id', $teacher->id)->pluck('id');
            $activityClassIds = DailyActivity::where('teacher_id', $teacher->id)->pluck('class_id');
            $allowedClassIds = $homeroomClassIds->merge($activityClassIds)->unique();
            $query->whereIn('class_id', $allowedClassIds);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->query('tanggal'));
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'))
                ->whereMonth('tanggal', $request->query('bulan'));
        }

        $perPage = min((int) $request->query('per_page', 15), 50);
        $activities = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kegiatan harian kelas berhasil diambil.',
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'tanggal' => ['sometimes', 'required', 'date'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $teacher = $user->teacher;
        $schoolClass = SchoolClass::findOrFail($validated['class_id']);

        // Jika Guru, pastikan mengajar atau wali kelas rombel tersebut
        if (!$user->isAdmin()) {
            if (!$teacher) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
        }

        $photoPath = null;
        if ($request->hasFile('foto')) {
            $stamp = "SIM-BIM | Kegiatan Kelas {$schoolClass->nama} | " . now()->format('d/m/Y');
            $photoPath = $this->photoService->compressAndStore(
                $request->file('foto'),
                'activities',
                $stamp
            );
        }

        $activity = DailyActivity::create([
            'class_id' => $validated['class_id'],
            'teacher_id' => $teacher?->id ?? ($schoolClass->wali_kelas_id ?? 1),
            'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'kategori' => $validated['kategori'] ?? 'kbm',
            'photo_path' => $photoPath,
            'created_by' => $user->id,
        ]);

        ActivityLogService::log(
            aksi: 'create_daily_activity',
            modul: 'daily_activities',
            targetType: DailyActivity::class,
            targetId: $activity->id,
            dataSesudah: $activity->toArray(),
            keterangan: "Menambahkan kegiatan harian kelas {$schoolClass->nama}: {$activity->judul}."
        );

        // Notifikasi ke wali murid anak di kelas ini
        $students = Student::where('class_id', $validated['class_id'])->with('guardians.user')->get();
        foreach ($students as $student) {
            foreach ($student->guardians as $guardian) {
                if ($guardian->user_id) {
                    InAppNotification::send(
                        userId: $guardian->user_id,
                        judul: "Kegiatan Baru: {$activity->judul}",
                        isi: "Ustadz/Ustadzah telah membagikan kegiatan harian untuk kelas {$schoolClass->nama}.",
                        tipe: 'kegiatan',
                        targetUrl: "/parent/children/{$student->id}/activities"
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Kegiatan harian berhasil ditambahkan.',
            'data' => $activity->load(['schoolClass', 'teacher']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $activity = DailyActivity::with(['schoolClass', 'teacher', 'creator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail kegiatan harian berhasil diambil.',
            'data' => $activity,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $activity = DailyActivity::findOrFail($id);
        $user = $request->user();

        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher || $activity->teacher_id !== $teacher->id) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
        }

        $validated = $request->validate([
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['sometimes', 'required', 'string'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('foto')) {
            if ($activity->photo_path && Storage::disk('public')->exists($activity->photo_path)) {
                Storage::disk('public')->delete($activity->photo_path);
            }
            $stamp = "SIM-BIM | Kegiatan Kelas | " . now()->format('d/m/Y');
            $validated['photo_path'] = $this->photoService->compressAndStore(
                $request->file('foto'),
                'activities',
                $stamp
            );
        }

        $oldData = $activity->toArray();
        $validated['updated_by'] = $user->id;
        $activity->update($validated);

        ActivityLogService::log(
            aksi: 'update_daily_activity',
            modul: 'daily_activities',
            targetType: DailyActivity::class,
            targetId: $activity->id,
            dataSebelum: $oldData,
            dataSesudah: $activity->fresh()->toArray(),
            keterangan: "Memperbarui kegiatan harian ID #{$id}: {$activity->judul}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Kegiatan harian berhasil diperbarui.',
            'data' => $activity->fresh()->load(['schoolClass', 'teacher']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $activity = DailyActivity::findOrFail($id);
        $user = $request->user();

        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher || $activity->teacher_id !== $teacher->id) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
        }

        $oldData = $activity->toArray();
        $activity->delete();

        ActivityLogService::log(
            aksi: 'delete_daily_activity',
            modul: 'daily_activities',
            targetType: DailyActivity::class,
            targetId: $id,
            dataSebelum: $oldData,
            keterangan: "Menghapus kegiatan harian ID #{$id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Kegiatan harian berhasil dihapus.',
            'data' => null,
        ]);
    }
}
