<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TimeSlot::query();

        if ($request->filled('hari')) {
            $query->where('hari', $request->query('hari'));
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }

        $slots = $query->orderBy('hari')->orderBy('urutan')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar jam pelajaran berhasil diambil.',
            'data' => $slots,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:30'],
            'jenis' => ['required', 'in:belajar,istirahat'],
            'jam_mulai' => ['required', 'date_format:H:i:s,H:i'],
            'jam_selesai' => ['required', 'date_format:H:i:s,H:i', 'after:jam_mulai'],
            'urutan' => ['required', 'integer', 'min:1'],
            'hari' => ['required', 'in:senin,selasa,rabu,kamis,jumat,sabtu'],
        ], [
            'jam_selesai.after' => 'Jam selesai harus lebih akhir dari jam mulai.',
        ]);

        $validated['created_by'] = auth()->id();
        $slot = TimeSlot::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'time_slots',
            targetType: TimeSlot::class,
            targetId: $slot->id,
            dataSesudah: $slot->toArray(),
            keterangan: "Membuat jam pelajaran: {$slot->nama} ({$slot->hari})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jam pelajaran berhasil dibuat.',
            'data' => $slot,
        ], 201);
    }

    public function show(TimeSlot $timeSlot): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail jam pelajaran berhasil diambil.',
            'data' => $timeSlot->load('schedules'),
        ]);
    }

    public function update(Request $request, TimeSlot $timeSlot): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:30'],
            'jenis' => ['sometimes', 'required', 'in:belajar,istirahat'],
            'jam_mulai' => ['sometimes', 'required', 'date_format:H:i:s,H:i'],
            'jam_selesai' => ['sometimes', 'required', 'date_format:H:i:s,H:i', 'after:jam_mulai'],
            'urutan' => ['sometimes', 'required', 'integer', 'min:1'],
            'hari' => ['sometimes', 'required', 'in:senin,selasa,rabu,kamis,jumat,sabtu'],
        ]);

        $oldData = $timeSlot->toArray();
        $validated['updated_by'] = auth()->id();
        $timeSlot->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'time_slots',
            targetType: TimeSlot::class,
            targetId: $timeSlot->id,
            dataSebelum: $oldData,
            dataSesudah: $timeSlot->fresh()->toArray(),
            keterangan: "Memperbarui jam pelajaran: {$timeSlot->nama} ({$timeSlot->hari})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jam pelajaran berhasil diperbarui.',
            'data' => $timeSlot->fresh(),
        ]);
    }

    public function destroy(TimeSlot $timeSlot): JsonResponse
    {
        if ($timeSlot->schedules()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Jam pelajaran tidak dapat dihapus karena masih digunakan dalam jadwal aktif.',
                'data' => null,
            ], 422);
        }

        $oldData = $timeSlot->toArray();
        $timeSlot->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'time_slots',
            targetType: TimeSlot::class,
            targetId: $timeSlot->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus jam pelajaran: {$timeSlot->nama} ({$timeSlot->hari})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Jam pelajaran berhasil dihapus.',
            'data' => null,
        ]);
    }
}
