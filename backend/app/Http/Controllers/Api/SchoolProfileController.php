<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolProfile;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $profile = SchoolProfile::with('headmaster')->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil sekolah belum diset.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil sekolah berhasil diambil.',
            'data' => $profile,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $profile = SchoolProfile::firstOrCreate(['id' => 1]);

        $validated = $request->validate([
            'nama_sekolah' => ['sometimes', 'required', 'string', 'max:150'],
            'npsn' => ['nullable', 'string', 'size:8'],
            'nss' => ['nullable', 'string', 'max:20'],
            'jenjang' => ['sometimes', 'string', 'max:10'],
            'status_sekolah' => ['nullable', 'string', 'max:10'],
            'yayasan' => ['nullable', 'string', 'max:150'],
            'alamat_jalan' => ['sometimes', 'required', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'desa_kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kab_kota' => ['sometimes', 'required', 'string', 'max:100'],
            'provinsi' => ['sometimes', 'required', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'size:5'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'url', 'max:255'],
            'kepala_sekolah_id' => ['nullable', 'exists:teachers,id'],
            'akreditasi' => ['nullable', 'string', 'max:5'],
            'tahun_akreditasi' => ['nullable', 'integer', 'digits:4'],
            'tahun_berdiri' => ['nullable', 'integer', 'digits:4'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_geofence_m' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'jam_masuk_default' => ['nullable', 'date_format:H:i:s,H:i'],
            'jam_pulang_default' => ['nullable', 'date_format:H:i:s,H:i'],
            'toleransi_terlambat_menit' => ['nullable', 'integer', 'min:0', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $oldData = $profile->toArray();

        if ($request->hasFile('logo')) {
            if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('school', 'public');
        }

        $validated['updated_by'] = auth()->id();
        $profile->update($validated);

        ActivityLogService::log(
            aksi: 'update_school_profile',
            modul: 'school',
            targetType: SchoolProfile::class,
            targetId: $profile->id,
            dataSebelum: $oldData,
            dataSesudah: $profile->fresh()->toArray(),
            keterangan: 'Admin memperbarui profil dan pengaturan sekolah.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil sekolah berhasil diperbarui.',
            'data' => $profile->fresh()->load('headmaster'),
        ]);
    }
}
