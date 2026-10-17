<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\DailyActivity;
use App\Models\Invoice;
use App\Models\LearningProgress;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentPortalController extends Controller
{
    public function children(Request $request): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if (!$guardian && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan profil Wali Murid.',
                'data' => [],
            ], 403);
        }

        $children = $guardian
            ? $guardian->students()->with([
                'schoolClass.academicYear',
                'schoolClass.homeroomTeacher:id,nama_lengkap',
                'religion:id,nama',
            ])->get()
            : Student::with(['schoolClass.academicYear', 'schoolClass.homeroomTeacher:id,nama_lengkap'])->take(10)->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar anak berhasil diambil.',
            'data' => $children,
        ]);
    }

    public function childSummary(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if ($guardian) {
            $isChild = $guardian->students()->where('students.id', $id)->exists();
            if (!$isChild) {
                return response()->json([
                    'success' => false,
                    'message' => 'Siswa ini bukan anak atau tanggungan Anda.',
                ], 403);
            }
        }

        $student = Student::with([
            'schoolClass.academicYear',
            'schoolClass.homeroomTeacher:id,nama_lengkap,no_hp',
        ])->findOrFail($id);

        $classId = $student->class_id;

        // 1. Kehadiran Bulan Ini
        $attendanceQuery = StudentAttendance::where('student_id', $student->id)
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year);

        $monthRecords = $attendanceQuery->get();
        $totalMonth = $monthRecords->count();
        $hadirMonth = $monthRecords->where('status', 'hadir')->count();

        $recentAttendances = StudentAttendance::where('student_id', $student->id)
            ->orderBy('tanggal', 'desc')
            ->take(7)
            ->get();

        $attendanceSummary = [
            'total_pertemuan_bulan_ini' => $totalMonth,
            'hadir' => $hadirMonth,
            'izin' => $monthRecords->where('status', 'izin')->count(),
            'sakit' => $monthRecords->where('status', 'sakit')->count(),
            'alpa' => $monthRecords->where('status', 'alpa')->count(),
            'persentase_kehadiran' => $totalMonth > 0 ? round(($hadirMonth / $totalMonth) * 100, 1) : 100,
            'today' => StudentAttendance::where('student_id', $student->id)
                ->whereDate('tanggal', now()->toDateString())
                ->first(),
            'recent' => $recentAttendances,
        ];

        // 2. Tagihan & Keuangan SPP Anak
        $invoices = Invoice::where('student_id', $student->id)
            ->with(['feeType:id,nama,kode', 'academicYear:id,nama'])
            ->orderBy('jatuh_tempo', 'desc')
            ->take(8)
            ->get();

        $totalTagihan = (float) $invoices->sum('jumlah');
        $totalTerbayar = (float) $invoices->sum('jumlah_dibayar');
        $sisaTagihan = (float) $invoices->sum('sisa');

        $financialSummary = [
            'total_tagihan' => $totalTagihan,
            'jumlah_terbayar' => $totalTerbayar,
            'sisa_tagihan' => $sisaTagihan,
            'status_tagihan' => $sisaTagihan <= 0 ? 'lunas' : 'belum_lunas',
            'invoices' => $invoices,
        ];

        // 3. Kegiatan Harian Kelas Terbaru (5 terakhir)
        $latestActivities = $classId
            ? DailyActivity::where('class_id', $classId)
                ->with('teacher:id,nama_lengkap')
                ->orderBy('tanggal', 'desc')
                ->take(5)
                ->get()
            : [];

        // 4. Progres Pembelajaran Terbaru (6 terakhir)
        $latestProgress = LearningProgress::where('student_id', $student->id)
            ->with(['subject:id,nama,kode', 'teacher:id,nama_lengkap'])
            ->orderBy('tanggal', 'desc')
            ->take(6)
            ->get();

        // 5. Pengumuman Aktif untuk Wali Murid (4 terakhir)
        $announcements = Announcement::where('is_aktif', true)
            ->where(function ($q) {
                $q->where('target_role', 'semua')
                  ->orWhere('target_role', 'wali');
            })
            ->whereDate('tgl_terbit', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('tgl_berakhir')
                  ->orWhereDate('tgl_berakhir', '>=', now()->toDateString());
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('tgl_terbit', 'desc')
            ->take(4)
            ->get();

        return response()->json([
            'success' => true,
            'message' => "Ringkasan data {$student->nama_lengkap} berhasil diambil.",
            'data' => [
                'student' => $student,
                'attendance_summary' => $attendanceSummary,
                'presensi_bulan_ini' => $attendanceSummary,
                'latest_activities' => $latestActivities,
                'latest_progress' => $latestProgress,
                'progres_terakhir' => $latestProgress,
                'announcements' => $announcements,
                'keuangan' => $financialSummary,
            ],
        ]);
    }

    public function childReportCards(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $guardian = $user->guardian;

        if ($guardian) {
            $isChild = $guardian->students()->where('students.id', $id)->exists();
            if (!$isChild) {
                return response()->json([
                    'success' => false,
                    'message' => 'Siswa ini bukan anak atau tanggungan Anda.',
                ], 403);
            }
        } elseif (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $student = Student::findOrFail($id);

        // Hanya tampilkan rapor dengan status final untuk wali murid
        $reportCards = \App\Models\ReportCard::where('student_id', $student->id)
            ->where('status', 'final')
            ->with(['academicYear:id,nama', 'semesterModel:id,semester,status', 'schoolClass:id,nama_kelas'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => "Rapor resmi {$student->nama_lengkap} berhasil diambil.",
            'data' => $reportCards,
        ]);
    }
}
