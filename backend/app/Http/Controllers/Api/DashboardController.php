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

        // 3. KEUANGAN BULAN INI
        $invoicesMonth = Invoice::where('periode', $currentMonth)->get();
        $totalTagihanBulanIni = (float) $invoicesMonth->sum('jumlah');
        $totalTerbayarBulanIni = (float) $invoicesMonth->sum('jumlah_dibayar');
        $sisaTagihanBulanIni = (float) $invoicesMonth->sum('sisa');
        $persenPembayaranBulanIni = $totalTagihanBulanIni > 0 ? round(($totalTerbayarBulanIni / $totalTagihanBulanIni) * 100, 1) : 0;

        // Arus Kas Bulan Ini
        $kasMasukBulanIni = (float) FinanceTransaction::where('tipe', 'masuk')
            ->where('is_pembalik', false)
            ->whereDate('tanggal', '>=', $startOfMonth)
            ->whereDate('tanggal', '<=', $endOfMonth)
            ->sum('jumlah');

        $kasKeluarBulanIni = (float) FinanceTransaction::where('tipe', 'keluar')
            ->where('is_pembalik', false)
            ->whereDate('tanggal', '>=', $startOfMonth)
            ->whereDate('tanggal', '<=', $endOfMonth)
            ->sum('jumlah');

        $saldoKasBulanIni = $kasMasukBulanIni - $kasKeluarBulanIni;

        // 4. SARPRAS & INVENTARIS
        $totalAset = Asset::count();
        $totalNilaiAset = (float) Asset::sum('nilai');
        $asetRusakRingan = Asset::where('kondisi', 'rusak_ringan')->count();
        $asetRusakBerat = Asset::where('kondisi', 'rusak_berat')->count();
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
