<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function index(): JsonResponse
    {
        $years = AcademicYear::with(['semesters'])
            ->withCount(['classes'])
            ->latest('tgl_mulai')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar tahun ajaran berhasil diambil.',
            'data' => $years,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/', 'unique:academic_years,nama'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['required', 'date', 'after:tgl_mulai'],
            'status' => ['sometimes', 'in:rencana,aktif,selesai'],
        ], [
            'nama.regex' => 'Format tahun ajaran harus YYYY/YYYY (contoh: 2026/2027).',
            'tgl_selesai.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'nama.unique' => 'Tahun ajaran tersebut sudah terdaftar.',
        ]);

        $validated['status'] = $validated['status'] ?? 'rencana';
        $validated['created_by'] = auth()->id();

        $year = DB::transaction(function () use ($validated) {
            if ($validated['status'] === 'aktif') {
                AcademicYear::where('status', 'aktif')->update(['status' => 'selesai']);
                Semester::where('status', 'aktif')->update(['status' => 'terkunci']);
            }

            $ay = AcademicYear::create($validated);

            // Buat otomatis 2 semester standar (Gasal & Genap)
            $start = Carbon::parse($ay->tgl_mulai);
            $end = Carbon::parse($ay->tgl_selesai);
            $mid = $start->copy()->addMonths(5)->endOfMonth();
            $secondStart = $mid->copy()->addDay();

            // Semester Gasal
            Semester::create([
                'academic_year_id' => $ay->id,
                'semester' => 'gasal',
                'tgl_mulai' => $start->toDateString(),
                'tgl_selesai' => $mid->toDateString(),
                'status' => $ay->status === 'aktif' ? 'aktif' : 'rencana',
                'created_by' => auth()->id(),
            ]);

            // Semester Genap
            Semester::create([
                'academic_year_id' => $ay->id,
                'semester' => 'genap',
                'tgl_mulai' => $secondStart->toDateString(),
                'tgl_selesai' => $end->toDateString(),
                'status' => 'rencana',
                'created_by' => auth()->id(),
            ]);

            return $ay->fresh(['semesters']);
        });

        ActivityLogService::log(
            aksi: 'create',
            modul: 'academic_years',
            targetType: AcademicYear::class,
            targetId: $year->id,
            dataSesudah: $year->toArray(),
            keterangan: "Membuat tahun ajaran {$year->nama} beserta semester Gasal & Genap."
        );

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil dibuat.',
            'data' => $year,
        ], 201);
    }

    public function show(AcademicYear $academicYear): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail tahun ajaran berhasil diambil.',
            'data' => $academicYear->load(['semesters'])->loadCount(['classes']),
        ]);
    }

    public function update(Request $request, AcademicYear $academicYear): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years', 'nama')->ignore($academicYear->id)],
            'tgl_mulai' => ['sometimes', 'required', 'date'],
            'tgl_selesai' => ['sometimes', 'required', 'date', 'after:tgl_mulai'],
            'status' => ['sometimes', 'in:rencana,aktif,selesai'],
        ]);

        $oldData = $academicYear->toArray();
        $validated['updated_by'] = auth()->id();

        DB::transaction(function () use ($academicYear, $validated) {
            if (!empty($validated['status']) && $validated['status'] === 'aktif') {
                AcademicYear::where('id', '!=', $academicYear->id)
                    ->where('status', 'aktif')
                    ->update(['status' => 'selesai']);
            }
            $academicYear->update($validated);
        });

        ActivityLogService::log(
            aksi: 'update',
            modul: 'academic_years',
            targetType: AcademicYear::class,
            targetId: $academicYear->id,
            dataSebelum: $oldData,
            dataSesudah: $academicYear->fresh()->toArray(),
            keterangan: "Memperbarui tahun ajaran {$academicYear->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil diperbarui.',
            'data' => $academicYear->fresh(['semesters']),
        ]);
    }

    public function destroy(AcademicYear $academicYear): JsonResponse
    {
        if ($academicYear->classes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun ajaran tidak dapat dihapus karena masih memiliki relasi dengan rombel kelas.',
                'data' => null,
            ], 422);
        }

        $oldData = $academicYear->toArray();
        $academicYear->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'academic_years',
            targetType: AcademicYear::class,
            targetId: $academicYear->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus tahun ajaran {$academicYear->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Tahun ajaran berhasil dihapus.',
            'data' => null,
        ]);
    }

    public function setActive(AcademicYear $academicYear): JsonResponse
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['status' => 'selesai']);
            $academicYear->update(['status' => 'aktif', 'updated_by' => auth()->id()]);
        });

        ActivityLogService::log(
            aksi: 'set_active',
            modul: 'academic_years',
            targetType: AcademicYear::class,
            targetId: $academicYear->id,
            keterangan: "Mengaktifkan tahun ajaran {$academicYear->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => "Tahun ajaran {$academicYear->nama} telah diaktifkan.",
            'data' => $academicYear->fresh(['semesters']),
        ]);
    }
}
