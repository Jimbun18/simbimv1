<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Services\PeriodLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Semester::with('academicYear');

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $semesters = $query->orderBy('tgl_mulai', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar semester berhasil diambil.',
            'data' => $semesters,
        ]);
    }

    public function show(Semester $semester): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail semester berhasil diambil.',
            'data' => $semester->load(['academicYear', 'closedBy', 'reopenApprovedBy'])
                ->loadCount(['schedules', 'teacherAssignments', 'reportCards']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester' => ['required', 'in:gasal,genap'],
            'tgl_mulai' => ['required', 'date'],
            'tgl_selesai' => ['required', 'date', 'after:tgl_mulai'],
            'status' => ['sometimes', 'in:rencana,aktif,terkunci'],
        ]);

        PeriodLockService::validateNoOverlap($validated['tgl_mulai'], $validated['tgl_selesai']);

        $validated['created_by'] = auth()->id();
        $semester = Semester::create($validated);

        if (!empty($validated['status']) && $validated['status'] === 'aktif') {
            PeriodLockService::activateSemester($semester);
        }

        return response()->json([
            'success' => true,
            'message' => 'Semester baru berhasil dibuat.',
            'data' => $semester->fresh(['academicYear']),
        ], 201);
    }

    public function update(Request $request, Semester $semester): JsonResponse
    {
        $validated = $request->validate([
            'semester' => ['sometimes', 'required', 'in:gasal,genap'],
            'tgl_mulai' => ['sometimes', 'required', 'date'],
            'tgl_selesai' => ['sometimes', 'required', 'date', 'after:tgl_mulai'],
        ]);

        $tglMulai = $validated['tgl_mulai'] ?? $semester->tgl_mulai->format('Y-m-d');
        $tglSelesai = $validated['tgl_selesai'] ?? $semester->tgl_selesai->format('Y-m-d');

        PeriodLockService::validateNoOverlap($tglMulai, $tglSelesai, $semester->id);

        $validated['updated_by'] = auth()->id();
        $semester->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Semester berhasil diperbarui.',
            'data' => $semester->fresh(['academicYear']),
        ]);
    }

    public function activate(Semester $semester): JsonResponse
    {
        $activated = PeriodLockService::activateSemester($semester);

        return response()->json([
            'success' => true,
            'message' => "Semester {$activated->nama_lengkap} berhasil diaktifkan.",
            'data' => $activated,
        ]);
    }

    /**
     * Checklist pra-penutupan semester
     */
    public function checklist(Semester $semester): JsonResponse
    {
        $totalReports = $semester->reportCards()->count();
        $draftReports = $semester->reportCards()->where('status', 'draft')->count();
        $approvedReports = $semester->reportCards()->where('status', 'disetujui')->count();
        $finalReports = $semester->reportCards()->where('status', 'final')->count();

        return response()->json([
            'success' => true,
            'message' => 'Checklist pra-penutupan semester.',
            'data' => [
                'semester' => $semester->nama_lengkap,
                'status' => $semester->status,
                'total_rapor' => $totalReports,
                'rapor_draft' => $draftReports,
                'rapor_disetujui' => $approvedReports,
                'rapor_final' => $finalReports,
                'can_close_cleanly' => $draftReports === 0,
            ],
        ]);
    }

    public function close(Request $request, Semester $semester): JsonResponse
    {
        $validated = $request->validate([
            'force_close' => ['sometimes', 'boolean'],
            'force_reason' => ['required_if:force_close,true', 'nullable', 'string', 'min:5'],
        ]);

        $closed = PeriodLockService::closeSemester(
            $semester,
            $request->boolean('force_close'),
            $request->input('force_reason')
        );

        return response()->json([
            'success' => true,
            'message' => "Semester {$closed->nama_lengkap} berhasil ditutup dan dikunci.",
            'data' => $closed,
        ]);
    }

    public function reopen(Request $request, Semester $semester): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
            'reopen_until' => ['required', 'date', 'after:now'],
            'reopen_approved_by' => ['required', 'exists:users,id'],
        ], [
            'reason.min' => 'Alasan pembukaan kembali wajib diisi minimal 10 karakter.',
            'reopen_until.after' => 'Batas waktu buka kunci harus berupa waktu di masa depan.',
            'reopen_approved_by.exists' => 'Kepala Sekolah yang menyetujui tidak ditemukan.',
        ]);

        $reopened = PeriodLockService::reopenSemester(
            $semester,
            $validated['reason'],
            $validated['reopen_until'],
            (int) $validated['reopen_approved_by']
        );

        return response()->json([
            'success' => true,
            'message' => "Semester {$reopened->nama_lengkap} berhasil dibuka sementara hingga {$reopened->reopen_until->format('d/m/Y H:i')}.",
            'data' => $reopened,
        ]);
    }
}
