<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Room::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%")
                  ->orWhere('gedung_lantai', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->query('jenis'));
        }

        if ($request->filled('kondisi')) {
            $query->where('kondisi', $request->query('kondisi'));
        }

        if ($request->boolean('all') || $request->query('per_page') === 'all') {
            return response()->json([
                'success' => true,
                'message' => 'Daftar semua ruangan berhasil diambil.',
                'data' => $query->orderBy('nama')->get(),
            ]);
        }

        $rooms = $query->orderBy('nama')->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json([
            'success' => true,
            'message' => 'Daftar ruangan berhasil diambil.',
            'data' => $rooms->items(),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:15', 'unique:rooms,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'jenis' => ['required', 'in:ruang_kelas,perpustakaan,lab,aula,mushola,lainnya'],
            'gedung_lantai' => ['nullable', 'string', 'max:50'],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
            'kondisi' => ['nullable', 'in:baik,rusak_ringan,rusak_berat'],
        ]);

        $validated['created_by'] = auth()->id();
        $room = Room::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'rooms',
            targetType: Room::class,
            targetId: $room->id,
            dataSesudah: $room->toArray(),
            keterangan: "Menambahkan ruangan baru: {$room->nama} ({$room->kode})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil ditambahkan.',
            'data' => $room,
        ], 201);
    }

    public function show(Room $room): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail ruangan berhasil diambil.',
            'data' => $room->loadCount(['classes', 'schedules']),
        ]);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['sometimes', 'required', 'string', 'max:15', Rule::unique('rooms', 'kode')->ignore($room->id)],
            'nama' => ['sometimes', 'required', 'string', 'max:100'],
            'jenis' => ['sometimes', 'required', 'in:ruang_kelas,perpustakaan,lab,aula,mushola,lainnya'],
            'gedung_lantai' => ['nullable', 'string', 'max:50'],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
            'kondisi' => ['nullable', 'in:baik,rusak_ringan,rusak_berat'],
        ]);

        $oldData = $room->toArray();
        $validated['updated_by'] = auth()->id();
        $room->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'rooms',
            targetType: Room::class,
            targetId: $room->id,
            dataSebelum: $oldData,
            dataSesudah: $room->fresh()->toArray(),
            keterangan: "Memperbarui data ruangan: {$room->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data ruangan berhasil diperbarui.',
            'data' => $room->fresh(),
        ]);
    }

    public function destroy(Room $room): JsonResponse
    {
        if ($room->classes()->exists() || $room->schedules()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak dapat dihapus karena masih digunakan pada kelas atau jadwal.',
                'data' => null,
            ], 422);
        }

        $oldData = $room->toArray();
        $room->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'rooms',
            targetType: Room::class,
            targetId: $room->id,
            dataSebelum: $oldData,
            keterangan: "Menghapus ruangan: {$room->nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil dihapus.',
            'data' => null,
        ]);
    }
}
