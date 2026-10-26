<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\TeachingEvidence;
use App\Services\ActivityLogService;
use App\Services\PhotoCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeachingEvidenceController extends Controller
{
    public function __construct(
        protected PhotoCompressionService $photoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $query = TeachingEvidence::with([
            'schedule.subject:id,nama,kode,is_tematik,tema_nomor,muatan_pelajaran',
            'schedule.schoolClass:id,nama,tingkat',
            'schedule.room:id,nama',
            'schedule.timeSlot:id,nama,jam_mulai,jam_selesai,urutan,hari',
            'schedule.teacher:id,nama_lengkap,nip',
            'teacher:id,nama_lengkap,nip',
        ]);

        if (!$isAdmin) {
            $teacher = $user->teacher;
            if (!$teacher) {
                return response()->json(['success' => true, 'data' => []]);
            }
            $query->where('teacher_id', $teacher->id);
        } else {
            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->query('teacher_id'));
            }
        }

        if ($request->filled('schedule_id')) {
            $query->where('schedule_id', $request->query('schedule_id'));
        }

        if ($request->filled('class_id')) {
            $query->whereHas('schedule', fn($q) => $q->where('class_id', $request->query('class_id')));
        }

        if ($request->filled('tanggal')) {
            $query->where('tanggal', $request->query('tanggal'));
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'))
                ->whereMonth('tanggal', $request->query('bulan'));
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $evidence = $query->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar bukti mengajar berhasil diambil.',
            'data' => $evidence->items(),
            'meta' => [
                'current_page' => $evidence->currentPage(),
                'last_page' => $evidence->lastPage(),
                'per_page' => $evidence->perPage(),
                'total' => $evidence->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;

        $validated = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'tanggal' => ['sometimes', 'required', 'date'],
            'foto' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'materi_ajar' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $schedule = Schedule::with(['subject', 'schoolClass', 'teacher'])->findOrFail($validated['schedule_id']);

        // Jika Guru mengunggah, pastikan jadwal miliknya
        if (!$user->isAdmin()) {
            if (!$teacher || $schedule->teacher_id !== $teacher->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak untuk mengunggah bukti mengajar pada jadwal pelajaran ini.',
                    'data' => null,
                ], 403);
            }
        }

        $tanggal = $validated['tanggal'] ?? now()->toDateString();

        // Validasi: Bukti KBM & jurnal hanya boleh diunggah pada hari yang sesuai dengan jadwal
        if (!$user->isAdmin()) {
            $scheduleDay = strtolower(trim($schedule->hari));
            $targetDate = \Carbon\Carbon::parse($tanggal);
            $dayMap = [
                0 => 'minggu',
                1 => 'senin',
                2 => 'selasa',
                3 => 'rabu',
                4 => 'kamis',
                5 => 'jumat',
                6 => 'sabtu',
            ];
            $actualDay = $dayMap[$targetDate->dayOfWeek] ?? '';

            if ($actualDay !== $scheduleDay) {
                return response()->json([
                    'success' => false,
                    'message' => "Bukti KBM dan jurnal hanya dapat diunggah pada hari yang sesuai dengan jadwal (Hari " . strtoupper($scheduleDay) . "). Tanggal {$tanggal} merupakan hari " . strtoupper($actualDay) . ".",
                    'data' => null,
                ], 422);
            }

            // Validasi: Apakah hari ini adalah hari libur kalender atau hari Minggu?
            $holiday = \App\Models\SchoolCalendar::checkHoliday($tanggal);
            if ($holiday['is_holiday']) {
                return response()->json([
                    'success' => false,
                    'message' => "Pengunggahan bukti KBM ditutup. Hari ini adalah {$holiday['name']}.",
                    'data' => [
                        'is_holiday' => true,
                        'holiday_name' => $holiday['name'],
                    ],
                ], 422);
            }

            // Pastikan tanggal pengunggahan adalah hari ini
            if ($targetDate->toDateString() !== now()->toDateString()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bukti KBM dan jurnal hanya dapat diunggah pada hari pelaksanaan KBM (Hari Ini).',
                    'data' => null,
                ], 422);
            }

            // Validasi: Pembatasan waktu sesuai jam pelajaran di jadwal
            $relatedSchedules = Schedule::where('teacher_id', $schedule->teacher_id)
                ->where('class_id', $schedule->class_id)
                ->where('subject_id', $schedule->subject_id)
                ->where('hari', $schedule->hari)
                ->with('timeSlot')
                ->get();

            $minStart = null;
            $maxEnd = null;

            foreach ($relatedSchedules as $rel) {
                if ($rel->timeSlot) {
                    $start = substr($rel->timeSlot->jam_mulai, 0, 5);
                    $end = substr($rel->timeSlot->jam_selesai, 0, 5);
                    if (!$minStart || $start < $minStart) $minStart = $start;
                    if (!$maxEnd || $end > $maxEnd) $maxEnd = $end;
                }
            }

            if ($minStart && $maxEnd) {
                $nowTime = now()->format('H:i');
                $endCarbon = \Carbon\Carbon::createFromFormat('H:i', $maxEnd)->addMinutes(15)->format('H:i');

                if ($nowTime < $minStart) {
                    return response()->json([
                        'success' => false,
                        'message' => "Jam pelajaran belum dimulai. Pengunggahan bukti KBM dan jurnal hanya dapat dilakukan saat jam pelajaran berlangsung ({$minStart} - {$maxEnd} WIB).",
                        'data' => null,
                    ], 422);
                }

                if ($nowTime > $endCarbon) {
                    return response()->json([
                        'success' => false,
                        'message' => "Waktu pengunggahan telah berakhir. Pengunggahan bukti KBM dan jurnal dibatasi hanya saat jam pelajaran sedang berlangsung ({$minStart} - {$maxEnd} WIB).",
                        'data' => null,
                    ], 422);
                }
            }
        }

        $targetTeacherId = $teacher ? $teacher->id : $schedule->teacher_id;

        // Watermark info
        $subjectName = $schedule->subject?->nama ?? 'Mapel';
        $className = $schedule->schoolClass?->nama ?? 'Kelas';
        $teacherName = $schedule->teacher?->nama_lengkap ?? 'Guru';
        $watermark = "SIM-BIM | {$subjectName} ({$className}) | {$teacherName} | " . now()->format('d/m/Y H:i') . " WIB";

        $photoPath = $this->photoService->compressAndStore(
            $request->file('foto'),
            'attendance/evidence',
            $watermark
        );

        $evidence = TeachingEvidence::updateOrCreate(
            [
                'schedule_id' => $schedule->id,
                'teacher_id' => $targetTeacherId,
                'tanggal' => $tanggal,
            ],
            [
                'photo_path' => $photoPath,
                'materi_ajar' => $validated['materi_ajar'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
                'captured_at' => now(),
                'created_by' => $user->id,
            ]
        );

        ActivityLogService::log(
            aksi: 'upload_evidence',
            modul: 'teaching_evidence',
            targetType: TeachingEvidence::class,
            targetId: $evidence->id,
            dataSesudah: $evidence->toArray(),
            keterangan: "Mengunggah bukti mengajar untuk {$subjectName} kelas {$className} pada tanggal {$tanggal}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Foto bukti mengajar berhasil diunggah.',
            'data' => $evidence->load(['schedule.subject', 'schedule.schoolClass', 'teacher']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $evidence = TeachingEvidence::with([
            'schedule.subject',
            'schedule.schoolClass',
            'schedule.room',
            'schedule.timeSlot',
            'teacher',
            'creator',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail bukti mengajar berhasil diambil.',
            'data' => $evidence,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $evidence = TeachingEvidence::findOrFail($id);
        $user = $request->user();

        if (!$user->isAdmin()) {
            $teacher = $user->teacher;
            if (!$teacher || $evidence->teacher_id !== $teacher->id) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
            // Batas waktu hapus: 24 jam setelah upload
            if ($evidence->created_at->diffInHours(now()) > 24) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bukti mengajar telah melewati batas 24 jam dan hanya dapat dihapus oleh Admin.',
                ], 422);
            }
        }

        if (Storage::disk('public')->exists($evidence->photo_path)) {
            Storage::disk('public')->delete($evidence->photo_path);
        }

        $oldData = $evidence->toArray();
        $evidence->delete();

        ActivityLogService::log(
            aksi: 'delete_evidence',
            modul: 'teaching_evidence',
            targetType: TeachingEvidence::class,
            targetId: $id,
            dataSebelum: $oldData,
            keterangan: "Menghapus bukti mengajar ID #{$id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Bukti mengajar berhasil dihapus.',
            'data' => null,
        ]);
    }
}
