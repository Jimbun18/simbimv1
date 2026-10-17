<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use App\Models\LearningProgress;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\PhotoCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LearningProgressController extends Controller
{
    public function __construct(
        protected PhotoCompressionService $photoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = LearningProgress::with([
            'student:id,nis,nisn,nama_lengkap,class_id,foto_path',
            'teacher:id,nama_lengkap',
            'subject:id,nama,kode',
            'schoolClass:id,nama,tingkat',
        ]);

        // FR-PRG-05: Wali HANYA dapat melihat data anaknya sendiri
        if ($user->isGuardian()) {
            $guardian = $user->guardian;
            if (!$guardian) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $childIds = $guardian->students()->pluck('students.id');
            $query->whereIn('student_id', $childIds);
        } elseif ($user->isTeacher() && !$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $homeroomClassIds = SchoolClass::where('wali_kelas_id', $teacher->id)->pluck('id');
            $progressClassIds = LearningProgress::where('teacher_id', $teacher->id)->pluck('class_id');
            $allowedClassIds = $homeroomClassIds->merge($progressClassIds)->unique();
            $query->whereIn('class_id', $allowedClassIds);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->query('student_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
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

        $perPage = min((int) $request->query('per_page', 20), 100);
        $progress = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data progres pembelajaran berhasil diambil.',
            'data' => $progress->items(),
            'meta' => [
                'current_page' => $progress->currentPage(),
                'last_page' => $progress->lastPage(),
                'per_page' => $progress->perPage(),
                'total' => $progress->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'class_id' => ['sometimes', 'required', 'exists:classes,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'tanggal' => ['sometimes', 'required', 'date'],
            'kategori' => ['required', 'in:akademik,tahfidz,sholat,karakter,ekskul,lainnya'],
            'capaian' => ['required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'nilai' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $classId = $validated['class_id'] ?? $student->class_id;
        $teacher = $user->teacher;

        $photoPath = null;
        if ($request->hasFile('foto')) {
            $stamp = "SIM-BIM | Progres {$student->nama_lengkap} | " . now()->format('d/m/Y');
            $photoPath = $this->photoService->compressAndStore(
                $request->file('foto'),
                'progress',
                $stamp
            );
        }

        $progress = LearningProgress::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher?->id ?? 1,
            'class_id' => $classId,
            'subject_id' => $validated['subject_id'] ?? null,
            'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
            'kategori' => $validated['kategori'],
            'capaian' => $validated['capaian'],
            'catatan' => $validated['catatan'] ?? null,
            'nilai' => $validated['nilai'] ?? null,
            'photo_path' => $photoPath,
            'created_by' => $user->id,
        ]);

        ActivityLogService::log(
            aksi: 'record_progress',
            modul: 'learning_progress',
            targetType: LearningProgress::class,
            targetId: $progress->id,
            dataSesudah: $progress->toArray(),
            keterangan: "Mencatat progres [{$progress->kategori}] untuk siswa {$student->nama_lengkap}: {$progress->capaian}."
        );

        // Notifikasi ke wali murid anak ini
        $guardians = $student->guardians;
        foreach ($guardians as $guardian) {
            if ($guardian->user_id) {
                InAppNotification::send(
                    userId: $guardian->user_id,
                    judul: "Progres Belajar Baru: {$student->nama_lengkap}",
                    isi: "Catatan capaian [{$progress->kategori}]: {$progress->capaian}.",
                    tipe: 'progres',
                    targetUrl: "/parent/children/{$student->id}/progress"
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Progres pembelajaran berhasil dicatat.',
            'data' => $progress->load(['student', 'teacher', 'subject', 'schoolClass']),
        ], 201);
    }

    public function batchStore(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'tanggal' => ['sometimes', 'required', 'date'],
            'kategori' => ['required', 'in:akademik,tahfidz,sholat,karakter,ekskul,lainnya'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.student_id' => ['required', 'exists:students,id'],
            'items.*.capaian' => ['required', 'string', 'max:255'],
            'items.*.catatan' => ['nullable', 'string'],
            'items.*.nilai' => ['nullable', 'string', 'max:20'],
        ]);

        $tanggal = $validated['tanggal'] ?? now()->toDateString();
        $classId = $validated['class_id'];
        $subjectId = $validated['subject_id'] ?? null;
        $kategori = $validated['kategori'];
        $teacher = $user->teacher;

        $created = [];

        DB::transaction(function () use ($validated, $tanggal, $classId, $subjectId, $kategori, $teacher, $user, &$created) {
            foreach ($validated['items'] as $item) {
                $record = LearningProgress::create([
                    'student_id' => $item['student_id'],
                    'teacher_id' => $teacher?->id ?? 1,
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'tanggal' => $tanggal,
                    'kategori' => $kategori,
                    'capaian' => $item['capaian'],
                    'catatan' => $item['catatan'] ?? null,
                    'nilai' => $item['nilai'] ?? null,
                    'created_by' => $user->id,
                ]);
                $created[] = $record;
            }
        });

        ActivityLogService::log(
            aksi: 'batch_record_progress',
            modul: 'learning_progress',
            targetType: LearningProgress::class,
            targetId: $created[0]->id ?? null,
            keterangan: "Mencatat progres [{$kategori}] massal untuk " . count($created) . " siswa kelas ID #{$classId}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Progres pembelajaran massal berhasil disimpan.',
            'data' => [
                'total_tersimpan' => count($created),
            ],
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $progress = LearningProgress::with([
            'student',
            'teacher',
            'subject',
            'schoolClass',
            'creator',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail progres pembelajaran berhasil diambil.',
            'data' => $progress,
        ]);
    }

    public function summary(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();

        // Validasi hak akses wali
        if ($user->isGuardian()) {
            $guardian = $user->guardian;
            $isChild = $guardian ? $guardian->students()->where('students.id', $studentId)->exists() : false;
            if (!$isChild) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
        }

        $student = Student::with('schoolClass')->findOrFail($studentId);

        $query = LearningProgress::where('student_id', $studentId);

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'))
                ->whereMonth('tanggal', $request->query('bulan'));
        }

        $records = $query->with(['subject', 'teacher:id,nama_lengkap'])->orderBy('tanggal', 'desc')->get();

        $byCategory = $records->groupBy('kategori')->map(function ($items, $cat) {
            return [
                'kategori' => $cat,
                'total' => $items->count(),
                'latest' => $items->first(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan progres anak berhasil diambil.',
            'data' => [
                'student' => $student,
                'total_capaian' => $records->count(),
                'categories' => $byCategory,
                'recent_records' => $records->take(10),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $progress = LearningProgress::findOrFail($id);
        $user = $request->user();

        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher || $progress->teacher_id !== $teacher->id) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
        }

        $validated = $request->validate([
            'capaian' => ['sometimes', 'required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'nilai' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('foto')) {
            if ($progress->photo_path && Storage::disk('public')->exists($progress->photo_path)) {
                Storage::disk('public')->delete($progress->photo_path);
            }
            $stamp = "SIM-BIM | Progres | " . now()->format('d/m/Y');
            $validated['photo_path'] = $this->photoService->compressAndStore(
                $request->file('foto'),
                'progress',
                $stamp
            );
        }

        $oldData = $progress->toArray();
        $validated['updated_by'] = $user->id;
        $progress->update($validated);

        ActivityLogService::log(
            aksi: 'update_progress',
            modul: 'learning_progress',
            targetType: LearningProgress::class,
            targetId: $progress->id,
            dataSebelum: $oldData,
            dataSesudah: $progress->fresh()->toArray(),
            keterangan: "Memperbarui data progres ID #{$id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Progres pembelajaran berhasil diperbarui.',
            'data' => $progress->fresh()->load(['student', 'teacher', 'subject']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $progress = LearningProgress::findOrFail($id);
        $user = $request->user();

        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher || $progress->teacher_id !== $teacher->id) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
        }

        $oldData = $progress->toArray();
        $progress->delete();

        ActivityLogService::log(
            aksi: 'delete_progress',
            modul: 'learning_progress',
            targetType: LearningProgress::class,
            targetId: $id,
            dataSebelum: $oldData,
            keterangan: "Menghapus catatan progres ID #{$id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Catatan progres berhasil dihapus.',
            'data' => null,
        ]);
    }
}
