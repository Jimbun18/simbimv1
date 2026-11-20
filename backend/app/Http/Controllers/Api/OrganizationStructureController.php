<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolOrganizationalAssignment;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationStructureController extends Controller
{
    /**
     * Ambil data bagan dan penugasan struktur organisasi sekolah
     */
    public function index(Request $request): JsonResponse
    {
        $assignments = SchoolOrganizationalAssignment::with(['teacher', 'staff', 'user'])
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();

        // Ambil daftar guru aktif untuk opsi dropdown penugasan
        $teachers = Teacher::where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->select('id', 'user_id', 'nama_lengkap', 'nip', 'nik', 'jenis_ptk', 'foto_path')
            ->get();

        // Ambil daftar staf / tendik aktif
        $staff = Staff::where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->select('id', 'user_id', 'nama_lengkap', 'nik', 'jabatan', 'foto_path')
            ->get();

        // Tahun ajaran aktif
        $activeYear = AcademicYear::where('status', 'aktif')->first()
            ?? AcademicYear::orderBy('id', 'desc')->first();

        return response()->json([
            'success' => true,
            'message' => 'Data struktur organisasi berhasil dimuat.',
            'data' => [
                'assignments' => $assignments,
                'teachers' => $teachers,
                'staff' => $staff,
                'active_academic_year' => $activeYear,
            ],
        ]);
    }

    /**
     * Berikan penugasan struktural kepada guru atau staf
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'posisi_kode' => ['required', 'string', 'max:50'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'staff_id' => ['nullable', 'exists:staff,id'],
            'no_sk' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'menu_permissions' => ['nullable', 'array'],
        ]);

        $assignment = SchoolOrganizationalAssignment::where('posisi_kode', $validated['posisi_kode'])->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Posisi struktural tidak ditemukan.',
            ], 404);
        }

        $userId = null;
        $personelName = '-';

        if (!empty($validated['teacher_id'])) {
            $teacher = Teacher::find($validated['teacher_id']);
            $userId = $teacher?->user_id;
            $personelName = $teacher?->nama_lengkap ?? '-';
            $assignment->teacher_id = $teacher->id;
            $assignment->staff_id = null;
        } elseif (!empty($validated['staff_id'])) {
            $staff = Staff::find($validated['staff_id']);
            $userId = $staff?->user_id;
            $personelName = $staff?->nama_lengkap ?? '-';
            $assignment->staff_id = $staff->id;
            $assignment->teacher_id = null;
        } else {
            // Unassign
            $assignment->teacher_id = null;
            $assignment->staff_id = null;
            $assignment->user_id = null;
        }

        if ($userId) {
            $assignment->user_id = $userId;
        } elseif (empty($validated['teacher_id']) && empty($validated['staff_id'])) {
            $assignment->user_id = null;
        }

        $assignment->no_sk = $validated['no_sk'] ?? $assignment->no_sk;
        if (isset($validated['keterangan'])) {
            $assignment->keterangan = $validated['keterangan'];
        }
        if (isset($validated['menu_permissions'])) {
            $assignment->menu_permissions = $validated['menu_permissions'];
        }

        $assignment->updated_by = auth()->id();
        $assignment->save();

        ActivityLogService::log(
            aksi: 'assign_organization_structure',
            modul: 'users',
            targetType: SchoolOrganizationalAssignment::class,
            targetId: $assignment->id,
            keterangan: "Penugasan struktural '{$assignment->posisi_nama}' diset ke: {$personelName}."
        );

        return response()->json([
            'success' => true,
            'message' => "Posisi {$assignment->posisi_nama} berhasil diperbarui.",
            'data' => $assignment->load(['teacher', 'staff', 'user']),
        ]);
    }

    /**
     * Kosongkan pejabat pada posisi struktural tertentu
     */
    public function unassign(Request $request, int $id): JsonResponse
    {
        $assignment = SchoolOrganizationalAssignment::findOrFail($id);
        $oldName = $assignment->personel_name ?? '-';

        $assignment->update([
            'teacher_id' => null,
            'staff_id' => null,
            'user_id' => null,
            'no_sk' => null,
            'updated_by' => auth()->id(),
        ]);

        ActivityLogService::log(
            aksi: 'unassign_organization_structure',
            modul: 'users',
            targetType: SchoolOrganizationalAssignment::class,
            targetId: $assignment->id,
            keterangan: "Penugasan struktural '{$assignment->posisi_nama}' (sebelumnya {$oldName}) dikosongkan."
        );

        return response()->json([
            'success' => true,
            'message' => "Pejabat pada posisi {$assignment->posisi_nama} berhasil dikosongkan.",
            'data' => $assignment,
        ]);
    }
}
