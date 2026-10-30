<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BkCounselingCase;
use App\Models\Student;
use App\Models\StudentObservation;
use App\Models\Teacher;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentObservationController extends Controller
{
    /**
     * Daftar Catatan Observasi Perilaku Siswa (Bisa diakses Semua Guru).
     */
    public function index(Request $request): JsonResponse
    {
        $query = StudentObservation::with([
            'student:id,nis,nama_lengkap,jenis_kelamin,foto_path,class_id',
            'schoolClass:id,nama,tingkat,kode',
            'observer:id,nama_lengkap,jabatan',
            'bkTeacher:id,nama_lengkap,jabatan',
        ]);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->query('class_id'));
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
     */
    public function addFeedbackBk(Request $request, StudentObservation $observation): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;

        $validated = $request->validate([
            'rekomendasi_bk' => ['required', 'string'],
            'saran_untuk_guru' => ['nullable', 'string'],
            'status_pemantauan' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['rekomendasi_bk_by'] = $teacher ? $teacher->id : 12;
        $validated['rekomendasi_bk_at'] = Carbon::now();
        $validated['updated_by'] = $user->id;

        if (empty($validated['status_pemantauan'])) {
            $validated['status_pemantauan'] = 'Dalam Pemantauan';
        }

        $observation->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'observations',
            targetType: StudentObservation::class,
            targetId: $observation->id,
            dataSesudah: $observation->fresh()->toArray(),
            keterangan: "Memberikan rekomendasi BK untuk observasi siswa ID {$observation->student_id}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Rekomendasi Guru BK berhasil disimpan.',
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
