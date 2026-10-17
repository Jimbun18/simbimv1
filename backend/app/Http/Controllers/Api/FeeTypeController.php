<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeType;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FeeType::query();

        if ($request->has('is_aktif')) {
            $query->where('is_aktif', filter_var($request->query('is_aktif'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('periode')) {
            $query->where('periode', $request->query('periode'));
        }

        $feeTypes = $query->orderBy('nama', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar jenis biaya berhasil diambil.',
            'data' => $feeTypes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:fee_types,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'periode' => ['required', 'in:bulanan,semester,tahunan,sekali'],
            'is_aktif' => ['sometimes', 'boolean'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $feeType = FeeType::create($validated);

        ActivityLogService::log(
            aksi: 'create_fee_type',
            modul: 'fee_types',
            targetType: FeeType::class,
            targetId: $feeType->id,
            dataSesudah: $feeType->toArray(),
            keterangan: "Menambahkan jenis biaya: {$feeType->nama} ({$feeType->kode}) sebesar Rp " . number_format($feeType->nominal, 0, ',', '.') . "."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jenis biaya berhasil ditambahkan.',
            'data' => $feeType,
        ], 201);
    }

    public function show(FeeType $feeType): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail jenis biaya berhasil diambil.',
            'data' => $feeType,
        ]);
    }

    public function update(Request $request, FeeType $feeType): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('fee_types', 'kode')->ignore($feeType->id)],
            'nama' => ['sometimes', 'required', 'string', 'max:100'],
            'nominal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'periode' => ['sometimes', 'required', 'in:bulanan,semester,tahunan,sekali'],
            'is_aktif' => ['sometimes', 'boolean'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $oldData = $feeType->toArray();
        $validated['updated_by'] = $request->user()->id;
        $feeType->update($validated);

        ActivityLogService::log(
            aksi: 'update_fee_type',
            modul: 'fee_types',
            targetType: FeeType::class,
            targetId: $feeType->id,
            dataSebelum: $oldData,
            dataSesudah: $feeType->fresh()->toArray(),
            keterangan: "Memperbarui jenis biaya: {$feeType->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jenis biaya berhasil diperbarui.',
            'data' => $feeType->fresh(),
        ]);
    }

    public function destroy(FeeType $feeType): JsonResponse
    {
        if ($feeType->invoices()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis biaya tidak dapat dihapus karena sudah memiliki data tagihan aktif. Anda dapat menonaktifkannya.',
                'data' => null,
            ], 422);
        }

        $oldData = $feeType->toArray();
        $feeType->delete();

        ActivityLogService::log(
            aksi: 'delete_fee_type',
            modul: 'fee_types',
            targetType: FeeType::class,
            targetId: $feeType->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus jenis biaya: {$feeType->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jenis biaya berhasil dihapus.',
            'data' => null,
        ]);
    }
}
