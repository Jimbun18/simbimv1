<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subject::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_tematik') && $request->query('is_tematik') !== '') {
            $query->where('is_tematik', $request->boolean('is_tematik'));
        }

        if ($request->filled('kelompok')) {
            $query->where('kelompok', $request->query('kelompok'));
        }

        if ($request->filled('tingkat_berlaku')) {
            $query->where('tingkat_berlaku', $request->query('tingkat_berlaku'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Jika diminta seluruh data (untuk dropdown form jadwal/presensi)
        if ($request->boolean('all') || $request->query('per_page') === 'all') {
            $allSubjects = $query->orderBy('is_tematik', 'desc')->orderBy('kode')->get();
            return response()->json([
                'success' => true,
                'message' => 'Daftar semua mata pelajaran berhasil diambil.',
                'data' => $allSubjects,
            ]);
        }

        $subjects = $query->orderBy('is_tematik', 'desc')
            ->orderBy('tema_nomor')
            ->orderBy('kode')
            ->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json([
            'success' => true,
            'message' => 'Daftar mata pelajaran berhasil diambil.',
            'data' => $subjects->items(),
            'meta' => [
                'current_page' => $subjects->currentPage(),
                'last_page' => $subjects->lastPage(),
                'per_page' => $subjects->perPage(),
                'total' => $subjects->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->has('status') && is_string($request->status)) {
            $request->merge(['status' => strtolower(trim($request->status))]);
        }
        if ($request->boolean('is_tematik')) {
            $request->merge(['kelompok' => 'tematik']);
        } elseif ($request->has('kelompok') && is_string($request->kelompok)) {
            $request->merge(['kelompok' => strtolower(trim(str_replace(' ', '_', $request->kelompok)))]);
        }

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:25', 'unique:subjects,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'is_tematik' => ['nullable', 'boolean'],
            'tema_nomor' => ['nullable', 'integer', 'min:1', 'max:50'],
            'muatan_pelajaran' => ['nullable', 'array'],
            'muatan_pelajaran.*' => ['string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'kelompok' => ['required', 'in:umum,agama,muatan_lokal,ekstrakurikuler,tematik'],
            'tingkat_berlaku' => ['nullable', 'string', 'max:50'],
            'jam_per_minggu' => ['nullable', 'integer', 'min:1', 'max:60'],
            'kkm' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
        ], [
            'jam_per_minggu.max' => 'Beban jam pelajaran per minggu maksimal 60 JP.',
            'jam_per_minggu.min' => 'Beban jam pelajaran per minggu minimal 1 JP.',
            'tema_nomor.max' => 'Nomor tema maksimal 50.',
            'tema_nomor.min' => 'Nomor tema minimal 1.',
            'kode.unique' => 'Kode mata pelajaran sudah digunakan.',
            'nama.required' => 'Nama mata pelajaran / tema wajib diisi.',
            'kelompok.required' => 'Kelompok mata pelajaran wajib dipilih.',
            'kelompok.in' => 'Kelompok mata pelajaran tidak valid.',
            'status.in' => 'Status harus berupa aktif atau nonaktif.',
        ]);

        $validated['is_tematik'] = $validated['is_tematik'] ?? false;
        if ($validated['is_tematik'] && empty($validated['kelompok'])) {
            $validated['kelompok'] = 'tematik';
        }
        $validated['status'] = $validated['status'] ?? 'aktif';
        $validated['created_by'] = auth()->id();
        $subject = Subject::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'subjects',
            targetType: Subject::class,
            targetId: $subject->id,
            dataSesudah: $subject->toArray(),
            keterangan: "Menambahkan mata pelajaran: {$subject->nama} ({$subject->kode})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil ditambahkan.',
            'data' => $subject,
        ], 201);
    }

    public function show(Subject $subject): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail mata pelajaran berhasil diambil.',
            'data' => $subject->loadCount(['schedules', 'assignments']),
        ]);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        if ($request->has('status') && is_string($request->status)) {
            $request->merge(['status' => strtolower(trim($request->status))]);
        }
        if ($request->boolean('is_tematik')) {
            $request->merge(['kelompok' => 'tematik']);
        } elseif ($request->has('kelompok') && is_string($request->kelompok)) {
            $request->merge(['kelompok' => strtolower(trim(str_replace(' ', '_', $request->kelompok)))]);
        }

        $validated = $request->validate([
            'kode' => ['sometimes', 'required', 'string', 'max:25', Rule::unique('subjects', 'kode')->ignore($subject->id)],
            'nama' => ['sometimes', 'required', 'string', 'max:100'],
            'is_tematik' => ['nullable', 'boolean'],
            'tema_nomor' => ['nullable', 'integer', 'min:1', 'max:50'],
            'muatan_pelajaran' => ['nullable', 'array'],
            'muatan_pelajaran.*' => ['string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'kelompok' => ['sometimes', 'required', 'in:umum,agama,muatan_lokal,ekstrakurikuler,tematik'],
            'tingkat_berlaku' => ['nullable', 'string', 'max:50'],
            'jam_per_minggu' => ['nullable', 'integer', 'min:1', 'max:60'],
            'kkm' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
        ], [
            'jam_per_minggu.max' => 'Beban jam pelajaran per minggu maksimal 60 JP.',
            'jam_per_minggu.min' => 'Beban jam pelajaran per minggu minimal 1 JP.',
            'tema_nomor.max' => 'Nomor tema maksimal 50.',
            'tema_nomor.min' => 'Nomor tema minimal 1.',
            'kode.unique' => 'Kode mata pelajaran sudah digunakan.',
            'nama.required' => 'Nama mata pelajaran / tema wajib diisi.',
            'kelompok.required' => 'Kelompok mata pelajaran wajib dipilih.',
            'kelompok.in' => 'Kelompok mata pelajaran tidak valid.',
            'status.in' => 'Status harus berupa aktif atau nonaktif.',
        ]);

        $oldData = $subject->toArray();
        if (!empty($validated['is_tematik']) && empty($validated['kelompok'])) {
            $validated['kelompok'] = 'tematik';
        }
        $validated['updated_by'] = auth()->id();
        $subject->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'subjects',
            targetType: Subject::class,
            targetId: $subject->id,
            dataSebelum: $oldData,
            dataSesudah: $subject->fresh()->toArray(),
            keterangan: "Memperbarui mata pelajaran: {$subject->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil diperbarui.',
            'data' => $subject->fresh(),
        ]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        if ($subject->schedules()->exists() || $subject->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Mata pelajaran tidak dapat dihapus karena masih digunakan pada penugasan atau jadwal.',
                'data' => null,
            ], 422);
        }

        $oldData = $subject->toArray();
        $subject->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'subjects',
            targetType: Subject::class,
            targetId: $subject->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus mata pelajaran: {$subject->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil dihapus.',
            'data' => null,
        ]);
    }
}
