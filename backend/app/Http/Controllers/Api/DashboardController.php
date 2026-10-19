<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\FinanceTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeachingEvidence;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $currentMonth = now()->format('Y-m');
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();
        $dayOfWeekMap = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];
        $todayDayName = $dayOfWeekMap[now()->dayOfWeek] ?? 'Senin';

        // 1. DATA MASTER & KESISWAAN
        $totalSiswa = Student::where('status', 'aktif')->count();
        $siswaLaki = Student::where('status', 'aktif')->where('jenis_kelamin', 'L')->count();
        $siswaPerempuan = Student::where('status', 'aktif')->where('jenis_kelamin', 'P')->count();
        $totalGuru = Teacher::where('status', 'aktif')->count();
        $totalTendik = Staff::where('status', 'aktif')->count();
        $totalKelas = SchoolClass::where('status', 'aktif')->count();

        // 2. KEHADIRAN HARI INI
        // a. Kehadiran Guru
        $teacherAttendancesToday = TeacherAttendance::whereDate('tanggal', $today)->get();
        $guruHadir = $teacherAttendancesToday->whereIn('status', ['hadir', 'terlambat'])->count();
        $guruTepatWaktu = $teacherAttendancesToday->where('status', 'hadir')->count();
        $guruTerlambat = $teacherAttendancesToday->where('status', 'terlambat')->count();
        $guruIzinSakit = $teacherAttendancesToday->whereIn('status', ['izin', 'sakit'])->count();
        $guruBelumHadir = max(0, $totalGuru - $guruHadir - $guruIzinSakit);
        $guruPersenKehadiran = $totalGuru > 0 ? round(($guruHadir / $totalGuru) * 100, 1) : 0;

        // b. Kehadiran Siswa
        $studentAttendancesToday = StudentAttendance::whereDate('tanggal', $today)->get();
        $totalAbsenSiswaInput = $studentAttendancesToday->count();
        $siswaHadir = $studentAttendancesToday->where('status', 'hadir')->count();
        $siswaSakit = $studentAttendancesToday->where('status', 'sakit')->count();
        $siswaIzin = $studentAttendancesToday->where('status', 'izin')->count();
        $siswaAlpa = $studentAttendancesToday->where('status', 'alpa')->count();
        $siswaPersenHadir = $totalAbsenSiswaInput > 0 ? round(($siswaHadir / $totalAbsenSiswaInput) * 100, 1) : 0;

        // c. Kepatuhan Bukti Mengajar (KBM)
        $jadwalHariIni = Schedule::where('status', 'aktif')
            ->where(function ($q) use ($todayDayName) {
                $q->where('hari', $todayDayName)
                  ->orWhere('hari', strtolower($todayDayName));
            })
            ->pluck('id');
        $totalSesiHariIni = $jadwalHariIni->count();
        $buktiMengajarHariIni = TeachingEvidence::whereDate('tanggal', $today)
            ->whereIn('schedule_id', $jadwalHariIni)
            ->distinct('schedule_id')
            ->count('schedule_id');
        $persenKepatuhanMengajar = $totalSesiHariIni > 0 ? round(($buktiMengajarHariIni / $totalSesiHariIni) * 100, 1) : 0;

        // 3. KEUANGAN BULAN INI (Agregasi langsung di DB)
        $invoiceSummary = Invoice::where('periode', $currentMonth)
            ->selectRaw('COALESCE(SUM(jumlah), 0) as total_tagihan, COALESCE(SUM(jumlah_dibayar), 0) as total_terbayar, COALESCE(SUM(sisa), 0) as sisa_tagihan')
            ->first();
        $totalTagihanBulanIni = (float) ($invoiceSummary->total_tagihan ?? 0);
        $totalTerbayarBulanIni = (float) ($invoiceSummary->total_terbayar ?? 0);
        $sisaTagihanBulanIni = (float) ($invoiceSummary->sisa_tagihan ?? 0);
        $persenPembayaranBulanIni = $totalTagihanBulanIni > 0 ? round(($totalTerbayarBulanIni / $totalTagihanBulanIni) * 100, 1) : 0;

        // Arus Kas Bulan Ini (Agregasi 1 query)
        $cashFlow = FinanceTransaction::where('is_pembalik', false)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE 0 END), 0) as kas_masuk, COALESCE(SUM(CASE WHEN tipe = 'keluar' THEN jumlah ELSE 0 END), 0) as kas_keluar")
            ->first();

        $kasMasukBulanIni = (float) ($cashFlow->kas_masuk ?? 0);
        $kasKeluarBulanIni = (float) ($cashFlow->kas_keluar ?? 0);
        $saldoKasBulanIni = $kasMasukBulanIni - $kasKeluarBulanIni;

        // 4. SARPRAS & INVENTARIS (Agregasi 1 query)
        $assetSummary = Asset::selectRaw("
            COUNT(*) as total_aset,
            COALESCE(SUM(nilai), 0) as total_nilai,
            SUM(CASE WHEN kondisi = 'rusak_ringan' THEN 1 ELSE 0 END) as rusak_ringan,
            SUM(CASE WHEN kondisi = 'rusak_berat' THEN 1 ELSE 0 END) as rusak_berat
        ")->first();

        $totalAset = (int) ($assetSummary->total_aset ?? 0);
        $totalNilaiAset = (float) ($assetSummary->total_nilai ?? 0);
        $asetRusakRingan = (int) ($assetSummary->rusak_ringan ?? 0);
        $asetRusakBerat = (int) ($assetSummary->rusak_berat ?? 0);
        $asetDipinjam = AssetLoan::where('status', 'dipinjam')->count();

        // 5. AUDIT LOG TERKINI (10 aktivitas terakhir)
        $recentLogs = ActivityLog::with('user:id,nama,username')
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'waktu' => $log->created_at?->diffForHumans() ?? '-',
                    'waktu_lengkap' => $log->created_at?->format('d/m/Y H:i:s') ?? '-',
                    'user' => $log->user?->nama ?? 'Sistem',
                    'modul' => $log->modul,
                    'aksi' => $log->aksi,
                    'keterangan' => $log->keterangan,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan eksekutif dashboard berhasil diambil.',
            'data' => [
                'tanggal' => $today,
                'hari' => $todayDayName,
                'akademik' => [
                    'total_siswa' => $totalSiswa,
                    'siswa_laki' => $siswaLaki,
                    'siswa_perempuan' => $siswaPerempuan,
                    'total_guru' => $totalGuru,
                    'total_tendik' => $totalTendik,
                    'total_kelas' => $totalKelas,
                ],
                'kehadiran_hari_ini' => [
                    'guru' => [
                        'total' => $totalGuru,
                        'hadir' => $guruHadir,
                        'tepat_waktu' => $guruTepatWaktu,
                        'terlambat' => $guruTerlambat,
                        'izin_sakit' => $guruIzinSakit,
                        'belum_hadir' => $guruBelumHadir,
                        'persentase_kehadiran' => $guruPersenKehadiran,
                    ],
                    'siswa' => [
                        'total_tercatat' => $totalAbsenSiswaInput,
                        'hadir' => $siswaHadir,
                        'sakit' => $siswaSakit,
                        'izin' => $siswaIzin,
                        'alpa' => $siswaAlpa,
                        'persentase_kehadiran' => $siswaPersenHadir,
                    ],
                    'bukti_mengajar' => [
                        'total_sesi' => $totalSesiHariIni,
                        'sudah_upload' => $buktiMengajarHariIni,
                        'belum_upload' => max(0, $totalSesiHariIni - $buktiMengajarHariIni),
                        'persentase_kepatuhan' => $persenKepatuhanMengajar,
                    ],
                ],
                'keuangan_bulan_ini' => [
                    'periode' => $currentMonth,
                    'total_tagihan' => $totalTagihanBulanIni,
                    'total_terbayar' => $totalTerbayarBulanIni,
                    'sisa_tagihan' => $sisaTagihanBulanIni,
                    'persentase_tercapai' => $persenPembayaranBulanIni,
                    'kas_masuk' => $kasMasukBulanIni,
                    'kas_keluar' => $kasKeluarBulanIni,
                    'saldo_kas' => $saldoKasBulanIni,
                ],
                'sarpras' => [
                    'total_aset' => $totalAset,
                    'total_nilai' => $totalNilaiAset,
                    'rusak_ringan' => $asetRusakRingan,
                    'rusak_berat' => $asetRusakBerat,
                    'sedang_dipinjam' => $asetDipinjam,
                ],
                'aktivitas_terbaru' => $recentLogs,
            ],
        ]);
    }
}
