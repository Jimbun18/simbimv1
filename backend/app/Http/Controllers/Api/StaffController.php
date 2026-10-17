<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Staff::with(['religion', 'education', 'user:id,username,email,status']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jabatan')) {
            $query->where('jabatan', $request->query('jabatan'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $staff = $query->orderBy('nama_lengkap')->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json([
            'success' => true,
            'message' => 'Daftar tenaga kependidikan berhasil diambil.',
            'data' => $staff->items(),
            'meta' => [
                'current_page' => $staff->currentPage(),
                'last_page' => $staff->lastPage(),
                'per_page' => $staff->perPage(),
                'total' => $staff->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'size:16', 'regex:/^\d{16}$/', 'unique:staff,nik'],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tgl_lahir' => ['required', 'date', 'before:today'],
            'agama' => ['nullable', 'exists:ref_religions,id'],
            'alamat_jalan' => ['required', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
            'no_hp' => ['required', 'string', 'max:16'],
            'email' => ['nullable', 'email', 'max:150'],
            'jabatan' => ['required', 'string', 'max:100'],
            'status_kepegawaian' => ['nullable', 'in:pns,pppk,honorer,kontrak'],
            'tgl_mulai_tugas' => ['required', 'date'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
            'pendidikan_terakhir' => ['nullable', 'exists:ref_education,id'],
        ]);

        $validated['status'] = $validated['status'] ?? 'aktif';
        $validated['created_by'] = auth()->id();
        $staff = Staff::create($validated);

        ActivityLogService::log(
            aksi: 'create',
            modul: 'staff',
            targetType: Staff::class,
            targetId: $staff->id,
            dataSesudah: $staff->toArray(),
            keterangan: "Menambahkan tenaga kependidikan: {$staff->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Tenaga kependidikan berhasil ditambahkan.',
            'data' => $staff->load(['religion', 'education']),
        ], 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail tenaga kependidikan berhasil diambil.',
            'data' => $staff->load(['religion', 'education', 'user']),
        ]);
    }

    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'nik' => ['sometimes', 'required', 'string', 'size:16', 'regex:/^\d{16}$/', Rule::unique('staff', 'nik')->ignore($staff->id)],
            'nama_lengkap' => ['sometimes', 'required', 'string', 'max:150'],
            'jenis_kelamin' => ['sometimes', 'required', 'in:L,P'],
            'tempat_lahir' => ['sometimes', 'required', 'string', 'max:100'],
            'tgl_lahir' => ['sometimes', 'required', 'date', 'before:today'],
            'agama' => ['nullable', 'exists:ref_religions,id'],
            'alamat_jalan' => ['sometimes', 'required', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
            'no_hp' => ['sometimes', 'required', 'string', 'max:16'],
            'email' => ['nullable', 'email', 'max:150'],
            'jabatan' => ['sometimes', 'required', 'string', 'max:100'],
            'status_kepegawaian' => ['nullable', 'in:pns,pppk,honorer,kontrak'],
            'tgl_mulai_tugas' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'in:aktif,nonaktif'],
            'pendidikan_terakhir' => ['nullable', 'exists:ref_education,id'],
        ]);

        $oldData = $staff->toArray();
        $validated['updated_by'] = auth()->id();
        $staff->update($validated);

        ActivityLogService::log(
            aksi: 'update',
            modul: 'staff',
            targetType: Staff::class,
            targetId: $staff->id,
            dataSebelum: $oldData,
            dataSesudah: $staff->fresh()->toArray(),
            keterangan: "Memperbarui data tenaga kependidikan: {$staff->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data tenaga kependidikan berhasil diperbarui.',
            'data' => $staff->fresh()->load(['religion', 'education']),
        ]);
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $oldData = $staff->toArray();
        $staff->update(['status' => 'nonaktif']);
        $staff->delete();

        ActivityLogService::log(
            aksi: 'delete',
            modul: 'staff',
            targetType: Staff::class,
            targetId: $staff->id,
            dataSebelum: $oldData,
            keterangan: "Menonaktifkan dan menghapus data tenaga kependidikan: {$staff->nama_lengkap}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Data tenaga kependidikan berhasil dihapus.',
            'data' => null,
        ]);
    }
}
