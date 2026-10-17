<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\AssetMaintenance;
use App\Services\ActivityLogService;
use App\Services\PhotoCompressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function __construct(
        protected PhotoCompressionService $photoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Asset::with(['room:id,nama,kode', 'creator:id,nama']);

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->filled('kondisi')) {
            $query->where('kondisi', $request->query('kondisi'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->query('room_id'));
        }

        if ($request->filled('search')) {
            $term = $request->query('search');
            $query->where(function ($q) use ($term) {
                $q->where('nama', 'like', "%{$term}%")
                  ->orWhere('kode', 'like', "%{$term}%")
                  ->orWhere('lokasi', 'like', "%{$term}%");
            });
        }

        $all = (clone $query)->get();
        $summary = [
            'total_aset' => $all->count(),
            'total_unit' => $all->sum('jumlah'),
            'total_nilai' => (float) $all->sum(fn($a) => $a->nilai * $a->jumlah),
            'kondisi' => [
                'baik' => $all->where('kondisi', 'baik')->sum('jumlah'),
                'rusak_ringan' => $all->where('kondisi', 'rusak_ringan')->sum('jumlah'),
                'rusak_berat' => $all->where('kondisi', 'rusak_berat')->sum('jumlah'),
                'hilang' => $all->where('kondisi', 'hilang')->sum('jumlah'),
            ],
        ];

        $perPage = min((int) $request->query('per_page', 25), 100);
        $assets = $query->orderBy('nama', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar inventaris aset berhasil diambil.',
            'data' => $assets->items(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:assets,kode'],
            'nama' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'in:mebel,elektronik,buku,alat_peraga,kendaraan,gedung_ruang,olahraga,lainnya'],
            'lokasi' => ['required', 'string', 'max:100'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'tahun_perolehan' => ['nullable', 'integer', 'digits:4'],
            'nilai' => ['sometimes', 'numeric', 'min:0'],
            'kondisi' => ['required', 'in:baik,rusak_ringan,rusak_berat,hilang'],
            'jumlah' => ['sometimes', 'integer', 'min:1'],
            'satuan' => ['sometimes', 'string', 'max:20'],
            'keterangan' => ['nullable', 'string'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $photoPath = null;
        if ($request->hasFile('foto')) {
            $stamp = "SIM-BIM | Aset {$validated['kode']}";
            $photoPath = $this->photoService->compressAndStore(
                $request->file('foto'),
                'assets',
                $stamp
            );
        }

        $validated['foto_path'] = $photoPath;
        $validated['created_by'] = $request->user()->id;

        $asset = Asset::create($validated);

        ActivityLogService::log(
            aksi: 'create_asset',
            modul: 'assets',
            targetType: Asset::class,
            targetId: $asset->id,
            dataSesudah: $asset->toArray(),
            keterangan: "Menambahkan aset baru: {$asset->nama} ({$asset->kode}) di {$asset->lokasi}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil ditambahkan ke inventaris.',
            'data' => $asset->load('room'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $asset = Asset::with([
            'room',
            'maintenances.creator:id,nama',
            'loans.borrower:id,nama',
            'creator:id,nama',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail aset berhasil diambil.',
            'data' => $asset,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'kode' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('assets', 'kode')->ignore($asset->id)],
            'nama' => ['sometimes', 'required', 'string', 'max:150'],
            'kategori' => ['sometimes', 'required', 'in:mebel,elektronik,buku,alat_peraga,kendaraan,gedung_ruang,olahraga,lainnya'],
            'lokasi' => ['sometimes', 'required', 'string', 'max:100'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'tahun_perolehan' => ['nullable', 'integer', 'digits:4'],
            'nilai' => ['sometimes', 'numeric', 'min:0'],
            'kondisi' => ['sometimes', 'required', 'in:baik,rusak_ringan,rusak_berat,hilang'],
            'jumlah' => ['sometimes', 'integer', 'min:1'],
            'satuan' => ['sometimes', 'string', 'max:20'],
            'keterangan' => ['nullable', 'string'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('foto')) {
            if ($asset->foto_path && Storage::disk('public')->exists($asset->foto_path)) {
                Storage::disk('public')->delete($asset->foto_path);
            }
            $stamp = "SIM-BIM | Aset " . ($validated['kode'] ?? $asset->kode);
            $validated['foto_path'] = $this->photoService->compressAndStore(
                $request->file('foto'),
                'assets',
                $stamp
            );
        }

        $oldData = $asset->toArray();
        $validated['updated_by'] = $request->user()->id;
        $asset->update($validated);

        ActivityLogService::log(
            aksi: 'update_asset',
            modul: 'assets',
            targetType: Asset::class,
            targetId: $asset->id,
            dataSebelum: $oldData,
            dataSesudah: $asset->fresh()->toArray(),
            keterangan: "Memperbarui aset: {$asset->nama} ({$asset->kode})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data aset berhasil diperbarui.',
            'data' => $asset->fresh()->load('room'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $oldData = $asset->toArray();
        $asset->delete();

        ActivityLogService::log(
            aksi: 'delete_asset',
            modul: 'assets',
            targetType: Asset::class,
            targetId: $id,
            dataSebelum: $oldData,
            keterangan: "Menghapus aset: {$asset->nama} ({$asset->kode})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil dihapus dari inventaris.',
            'data' => null,
        ]);
    }

    public function storeMaintenance(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'tanggal' => ['sometimes', 'required', 'date'],
            'deskripsi' => ['required', 'string'],
            'biaya' => ['required', 'numeric', 'min:0'],
            'pelaksana' => ['nullable', 'string', 'max:100'],
            'kondisi_sesudah' => ['required', 'in:baik,rusak_ringan,rusak_berat'],
            'bukti' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $buktiPath = $request->file('bukti')->store('assets/maintenance', 'public');
        }

        $maintenance = AssetMaintenance::create([
            'asset_id' => $asset->id,
            'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
            'deskripsi' => $validated['deskripsi'],
            'biaya' => $validated['biaya'],
            'pelaksana' => $validated['pelaksana'] ?? null,
            'kondisi_sesudah' => $validated['kondisi_sesudah'],
            'bukti_path' => $buktiPath,
            'created_by' => $request->user()->id,
        ]);

        // Perbarui kondisi aset
        $asset->update(['kondisi' => $validated['kondisi_sesudah']]);

        ActivityLogService::log(
            aksi: 'record_asset_maintenance',
            modul: 'assets',
            targetType: AssetMaintenance::class,
            targetId: $maintenance->id,
            keterangan: "Mencatat perawatan aset {$asset->nama} ({$asset->kode}) dengan biaya Rp " . number_format($maintenance->biaya, 0, ',', '.') . "."
        );

        return response()->json([
            'success' => true,
            'message' => 'Catatan perawatan aset berhasil disimpan.',
            'data' => $maintenance,
        ], 201);
    }

    public function borrow(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'peminjam_nama' => ['required', 'string', 'max:100'],
            'peminjam_user_id' => ['nullable', 'exists:users,id'],
            'tgl_pinjam' => ['sometimes', 'required', 'date'],
            'tgl_rencana_kembali' => ['required', 'date', 'after_or_equal:tgl_pinjam'],
            'keperluan' => ['nullable', 'string'],
        ]);

        $loan = AssetLoan::create([
            'asset_id' => $asset->id,
            'peminjam_nama' => $validated['peminjam_nama'],
            'peminjam_user_id' => $validated['peminjam_user_id'] ?? null,
            'tgl_pinjam' => $validated['tgl_pinjam'] ?? now()->toDateString(),
            'tgl_rencana_kembali' => $validated['tgl_rencana_kembali'],
            'status' => 'dipinjam',
            'keperluan' => $validated['keperluan'] ?? null,
            'kondisi_sebelum' => $asset->kondisi,
            'created_by' => $request->user()->id,
        ]);

        ActivityLogService::log(
            aksi: 'borrow_asset',
            modul: 'assets',
            targetType: AssetLoan::class,
            targetId: $loan->id,
            keterangan: "Peminjaman aset {$asset->nama} oleh {$loan->peminjam_nama}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Peminjaman aset berhasil dicatat.',
            'data' => $loan,
        ], 201);
    }

    public function returnLoan(Request $request, int $loanId): JsonResponse
    {
        $loan = AssetLoan::with('asset')->findOrFail($loanId);

        $validated = $request->validate([
            'tgl_kembali_aktual' => ['sometimes', 'required', 'date'],
            'kondisi_sesudah' => ['required', 'in:baik,rusak_ringan,rusak_berat,hilang'],
            'catatan' => ['nullable', 'string'],
        ]);

        $loan->update([
            'tgl_kembali_aktual' => $validated['tgl_kembali_aktual'] ?? now()->toDateString(),
            'status' => 'dikembalikan',
            'kondisi_sesudah' => $validated['kondisi_sesudah'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        // Perbarui kondisi aset jika berubah
        $loan->asset->update(['kondisi' => $validated['kondisi_sesudah']]);

        ActivityLogService::log(
            aksi: 'return_asset_loan',
            modul: 'assets',
            targetType: AssetLoan::class,
            targetId: $loan->id,
            keterangan: "Pengembalian aset {$loan->asset->nama} oleh {$loan->peminjam_nama}. Kondisi: {$validated['kondisi_sesudah']}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengembalian aset berhasil dicatat.',
            'data' => $loan->fresh(),
        ]);
    }
}
