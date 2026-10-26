<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SchoolProfile;
use App\Models\Setting;
use App\Models\TeacherAttendance;
use App\Services\ActivityLogService;
use App\Services\Export\AttendanceRecapExportService;
use App\Services\GeofenceService;
use App\Services\PhotoCompressionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherAttendanceController extends Controller
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected PhotoCompressionService $photoService
    ) {}

    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;
        $staff = $user->staff;

        if (!$teacher && !$staff && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data Guru atau Tendik.',
                'data' => null,
            ], 403);
        }

        $today = now()->toDateString();
        $holiday = \App\Models\SchoolCalendar::checkHoliday($today);
        $attendance = null;

        if ($teacher || $staff) {
            $attendance = TeacherAttendance::whereDate('tanggal', $today)
                ->when($teacher, fn($q) => $q->where('teacher_id', $teacher->id))
                ->when(!$teacher && $staff, fn($q) => $q->where('staff_id', $staff->id))
                ->first();
        }

        $profile = SchoolProfile::first();
        $geofenceEnabled = (bool) Setting::get('geofence_enabled', true);
        $requirePhoto = (bool) Setting::get('require_selfie_teacher', true);

        return response()->json([
            'success' => true,
            'message' => 'Status presensi hari ini berhasil diambil.',
            'data' => [
                'tanggal' => $today,
                'hari' => now()->locale('id')->isoFormat('dddd'),
                'attendance' => $attendance,
                'has_checked_in' => $attendance !== null && $attendance->jam_masuk !== null,
                'has_checked_out' => $attendance !== null && $attendance->jam_keluar !== null,
                'holiday' => [
                    'is_holiday' => $holiday['is_holiday'],
                    'is_sunday' => $holiday['is_sunday'],
                    'name' => $holiday['name'],
                    'type' => $holiday['type'],
                ],
                'school_config' => [
                    'latitude' => $profile?->latitude,
                    'longitude' => $profile?->longitude,
                    'radius_geofence_m' => $profile?->radius_geofence_m ?? 100,
                    'jam_masuk_default' => $profile?->jam_masuk_default ?? '07:00:00',
                    'jam_pulang_default' => $profile?->jam_pulang_default ?? '15:00:00',
                    'toleransi_terlambat_menit' => $profile?->toleransi_terlambat_menit ?? 15,
                    'geofence_enabled' => $geofenceEnabled,
                    'require_selfie' => $requirePhoto,
                ],
            ],
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;
        $staff = $user->staff;

        if (!$teacher && !$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terdaftar sebagai Guru maupun Tenaga Kependidikan.',
                'data' => null,
            ], 403);
        }

        $today = now()->toDateString();

        // Validasi: Apakah hari ini adalah hari libur kalender atau hari Minggu?
        $holiday = \App\Models\SchoolCalendar::checkHoliday($today);
        if ($holiday['is_holiday']) {
            return response()->json([
                'success' => false,
                'message' => "Presensi mandiri guru ditutup. Hari ini adalah {$holiday['name']}.",
                'data' => [
                    'is_holiday' => true,
                    'holiday_name' => $holiday['name'],
                ],
            ], 422);
        }

        $requirePhoto = (bool) Setting::get('require_selfie_teacher', true);

        $rules = [
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];

        if ($requirePhoto) {
            $rules['foto'] = ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'];
        } else {
            $rules['foto'] = ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'];
        }

        $validated = $request->validate($rules);

        $today = now()->toDateString();
        $attendanceQuery = TeacherAttendance::whereDate('tanggal', $today);
        if ($teacher) {
            $attendanceQuery->where('teacher_id', $teacher->id);
        } else {
            $attendanceQuery->where('staff_id', $staff->id);
        }

        $attendance = $attendanceQuery->first();
        if ($attendance && $attendance->jam_masuk) {
            return response()->json([
                'success' => false,
                'message' => "Anda sudah melakukan absen masuk hari ini pukul " . substr($attendance->jam_masuk, 0, 5) . " WIB.",
                'data' => $attendance,
            ], 422);
        }

        // Validasi Geofence
        $geofence = $this->geofenceService->validateLocation(
            $validated['lat'] ?? null,
            $validated['lng'] ?? null
        );

        if (!$geofence['is_valid']) {
            return response()->json([
                'success' => false,
                'message' => $geofence['message'],
                'data' => [
                    'distance_m' => $geofence['distance_m'],
                    'radius_m' => $geofence['radius_m'],
                ],
            ], 422);
        }

        // Upload & kompres foto selfie jika ada
        $photoPath = null;
        if ($request->hasFile('foto')) {
            $stamp = 'SIM-BIM | ' . ($teacher?->nama_lengkap ?? $staff?->nama_lengkap) . ' | ' . now()->format('d/m/Y H:i:s') . ' WIB';
            $photoPath = $this->photoService->compressAndStore(
                $request->file('foto'),
                'attendance/teachers',
                $stamp
            );
        }

        // Hitung Keterlambatan
        $profile = SchoolProfile::first();
        $jamMasukDefault = $profile?->jam_masuk_default ?? '07:00:00';
        $toleransi = (int) ($profile?->toleransi_terlambat_menit ?? 15);
        $waktuMasuk = now();
        $targetJam = Carbon::parse($waktuMasuk->toDateString() . ' ' . $jamMasukDefault, $waktuMasuk->timezone);
        $batasToleransi = $targetJam->copy()->addMinutes($toleransi);

        $isTerlambat = $waktuMasuk->greaterThan($batasToleransi);
        $menitTerlambat = 0;
        if ($isTerlambat) {
            $menitTerlambat = (int) max(1, $targetJam->diffInMinutes($waktuMasuk));
        }

        $serverTime = $waktuMasuk->toTimeString();

        $data = [
            'teacher_id' => $teacher?->id,
            'staff_id' => $staff?->id,
            'tanggal' => $today,
            'jam_masuk' => $serverTime,
            'status' => $isTerlambat ? 'terlambat' : 'hadir',
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'jarak_masuk_m' => $geofence['distance_m'] ?? null,
            'foto_masuk' => $photoPath,
            'is_terlambat' => $isTerlambat,
            'menit_keterlambatan' => $menitTerlambat,
            'keterangan' => $validated['keterangan'] ?? ($isTerlambat ? "Terlambat {$menitTerlambat} menit" : null),
            'status_persetujuan' => 'disetujui',
            'created_by' => $user->id,
        ];

        if ($attendance) {
            $attendance->update($data);
            $record = $attendance;
        } else {
            $record = TeacherAttendance::create($data);
        }

        ActivityLogService::log(
            aksi: 'check_in',
            modul: 'teacher_attendance',
            targetType: TeacherAttendance::class,
            targetId: $record->id,
            dataSesudah: $record->toArray(),
            keterangan: "Absen masuk berhasil: " . ($teacher?->nama_lengkap ?? $staff?->nama_lengkap) . " pukul {$serverTime} WIB." . ($isTerlambat ? " (Terlambat {$menitTerlambat} menit)" : "")
        );

        return response()->json([
            'success' => true,
            'message' => $isTerlambat
                ? "Absen masuk berhasil tercatat. Anda terlambat {$menitTerlambat} menit."
                : 'Absen masuk berhasil. Selamat mengajar dan beraktivitas!',
            'data' => $record,
        ], 200);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;
        $staff = $user->staff;

        if (!$teacher && !$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terdaftar sebagai Guru maupun Tenaga Kependidikan.',
                'data' => null,
            ], 403);
        }

        $requirePhoto = (bool) Setting::get('require_selfie_teacher', true);

        $rules = [
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];

        $validated = $request->validate($rules);

        $today = now()->toDateString();
        $attendance = TeacherAttendance::whereDate('tanggal', $today)
            ->when($teacher, fn($q) => $q->where('teacher_id', $teacher->id))
            ->when(!$teacher && $staff, fn($q) => $q->where('staff_id', $staff->id))
            ->first();

        if (!$attendance || !$attendance->jam_masuk) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum melakukan absen masuk hari ini. Silakan absen masuk terlebih dahulu.',
                'data' => null,
            ], 422);
        }

        if ($attendance->jam_keluar) {
            return response()->json([
                'success' => false,
                'message' => "Anda sudah melakukan absen pulang hari ini pukul " . substr($attendance->jam_keluar, 0, 5) . " WIB.",
                'data' => $attendance,
            ], 422);
        }

        // Validasi Geofence
        $geofence = $this->geofenceService->validateLocation(
            $validated['lat'] ?? null,
            $validated['lng'] ?? null
        );

        if (!$geofence['is_valid']) {
            return response()->json([
                'success' => false,
                'message' => $geofence['message'],
                'data' => [
                    'distance_m' => $geofence['distance_m'],
                    'radius_m' => $geofence['radius_m'],
                ],
            ], 422);
        }

        $photoPath = null;
        if ($request->hasFile('foto')) {
            $stamp = 'SIM-BIM | ' . ($teacher?->nama_lengkap ?? $staff?->nama_lengkap) . ' | PULANG | ' . now()->format('d/m/Y H:i:s') . ' WIB';
            $photoPath = $this->photoService->compressAndStore(
                $request->file('foto'),
                'attendance/teachers',
                $stamp
            );
        }

        $profile = SchoolProfile::first();
        $jamPulangDefault = $profile?->jam_pulang_default ?? '14:30:00';
        $waktuKeluar = now();
        $targetJamPulang = Carbon::parse($waktuKeluar->toDateString() . ' ' . $jamPulangDefault, $waktuKeluar->timezone);
        $isPulangAwal = $waktuKeluar->lessThan($targetJamPulang);
        $menitPulangAwal = 0;
        if ($isPulangAwal) {
            $menitPulangAwal = (int) max(1, $waktuKeluar->diffInMinutes($targetJamPulang));
        }

        $serverTime = $waktuKeluar->toTimeString();

        // Keterangan catatan pulang awal jika ada
        $keteranganPulang = $attendance->keterangan;
        if ($isPulangAwal) {
            $catatanPulangAwal = "Pulang awal {$menitPulangAwal} menit";
            $keteranganPulang = $keteranganPulang ? "{$keteranganPulang} | {$catatanPulangAwal}" : $catatanPulangAwal;
        }

        $attendance->update([
            'jam_keluar' => $serverTime,
            'lat_keluar' => $validated['lat'] ?? null,
            'lng_keluar' => $validated['lng'] ?? null,
            'jarak_keluar_m' => $geofence['distance_m'] ?? null,
            'foto_pulang' => $photoPath,
            'keterangan' => $keteranganPulang,
            'updated_by' => $user->id,
        ]);

        ActivityLogService::log(
            aksi: 'check_out',
            modul: 'teacher_attendance',
            targetType: TeacherAttendance::class,
            targetId: $attendance->id,
            dataSesudah: $attendance->toArray(),
            keterangan: "Absen pulang berhasil: " . ($teacher?->nama_lengkap ?? $staff?->nama_lengkap) . " pukul {$serverTime} WIB." . ($isPulangAwal ? " (Pulang awal {$menitPulangAwal} menit)" : "")
        );

        $msg = $isPulangAwal
            ? "Absen pulang berhasil tercatat. Anda pulang {$menitPulangAwal} menit lebih awal dari jam kerja standar ({$jamPulangDefault})."
            : 'Absen pulang berhasil tercatat. Terima kasih atas dedikasi Anda hari ini!';

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => $attendance,
        ]);
    }

    public function leave(Request $request): JsonResponse
    {
        $user = $request->user();
        $teacher = $user->teacher;
        $staff = $user->staff;

        if (!$teacher && !$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terdaftar sebagai Guru maupun Tenaga Kependidikan.',
                'data' => null,
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:izin,sakit,dinas'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['required', 'string', 'max:500'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('attendance/leaves', 'public');
        }

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end = Carbon::parse($validated['tanggal_selesai']);

        $createdRecords = [];

        DB::transaction(function () use ($start, $end, $teacher, $staff, $validated, $lampiranPath, $user, &$createdRecords) {
            $current = $start->copy();
            while ($current->lte($end)) {
                // Jangan buat pada hari Minggu
                if ($current->dayOfWeek !== Carbon::SUNDAY) {
                    $dateStr = $current->toDateString();
                    $record = TeacherAttendance::updateOrCreate(
                        [
                            'teacher_id' => $teacher?->id,
                            'staff_id' => $staff?->id,
                            'tanggal' => $dateStr,
                        ],
                        [
                            'status' => $validated['status'],
                            'keterangan' => $validated['keterangan'],
                            'surat_izin_path' => $lampiranPath,
                            'status_persetujuan' => 'diajukan',
                            'created_by' => $user->id,
                        ]
                    );
                    $createdRecords[] = $record;
                }
                $current->addDay();
            }
        });

        ActivityLogService::log(
            aksi: 'request_leave',
            modul: 'teacher_attendance',
            targetType: TeacherAttendance::class,
            targetId: $createdRecords[0]->id ?? null,
            keterangan: "Pengajuan {$validated['status']} oleh " . ($teacher?->nama_lengkap ?? $staff?->nama_lengkap) . " dari {$validated['tanggal_mulai']} s.d {$validated['tanggal_selesai']}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin/sakit berhasil dikirim dan menunggu persetujuan Kepala Sekolah.',
            'data' => $createdRecords,
        ], 201);
    }

    public function approveLeave(Request $request, int $id): JsonResponse
    {
        $attendance = TeacherAttendance::findOrFail($id);

        $validated = $request->validate([
            'status_persetujuan' => ['required', 'in:disetujui,ditolak'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ]);

        $oldData = $attendance->toArray();

        $attendance->update([
            'status_persetujuan' => $validated['status_persetujuan'],
            'disetujui_oleh' => $request->user()->id,
            'keterangan' => $validated['alasan'] ? ($attendance->keterangan . " | Catatan persetujuan: " . $validated['alasan']) : $attendance->keterangan,
            'updated_by' => $request->user()->id,
        ]);

        ActivityLogService::log(
            aksi: 'approve_leave',
            modul: 'teacher_attendance',
            targetType: TeacherAttendance::class,
            targetId: $attendance->id,
            dataSebelum: $oldData,
            dataSesudah: $attendance->fresh()->toArray(),
            keterangan: "Kepala Sekolah {$validated['status_persetujuan']} permohonan {$attendance->status} ID #{$attendance->id}."
        );

        return response()->json([
            'success' => true,
            'message' => "Permohonan berhasil di-{$validated['status_persetujuan']}.",
            'data' => $attendance,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $query = TeacherAttendance::with(['teacher:id,nip,nik,nama_lengkap,jenis_ptk,foto_path', 'staff:id,nik,nama_lengkap,jabatan,foto_path']);

        // Jika bukan admin, hanya bisa melihat absensinya sendiri
        if (!$isAdmin) {
            $teacher = $user->teacher;
            $staff = $user->staff;
            if ($teacher) {
                $query->where('teacher_id', $teacher->id);
            } elseif ($staff) {
                $query->where('staff_id', $staff->id);
            } else {
                return response()->json(['success' => true, 'data' => [], 'summary' => []]);
            }
        } else {
            if ($request->filled('teacher_id')) {
                $query->where('teacher_id', $request->query('teacher_id'));
            }
            if ($request->filled('staff_id')) {
                $query->where('staff_id', $request->query('staff_id'));
            }
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->query('tanggal'));
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'))
                ->whereMonth('tanggal', $request->query('bulan'));
        } elseif ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->query('bulan'));
        } elseif ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->query('tahun'));
        }

        if ($request->filled('status') && $request->query('status') !== 'belum_absen') {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('is_terlambat')) {
            $query->where('is_terlambat', filter_var($request->query('is_terlambat'), FILTER_VALIDATE_BOOLEAN));
        }

        // Summary stats
        $summaryQuery = clone $query;
        $allRecords = $summaryQuery->get();

        $totalTeachers = \App\Models\Teacher::count();
        $totalStaff = \App\Models\Staff::count();
        $totalPtk = $totalTeachers + $totalStaff;

        $totalRecord = $allRecords->count();
        $hadir = $allRecords->where('status', 'hadir')->count();
        $terlambat = $allRecords->filter(function ($rec) {
            return $rec->status === 'terlambat' || $rec->is_terlambat;
        })->count();
        $hadirTepatWaktu = $allRecords->where('status', 'hadir')->where('is_terlambat', false)->count();
        $izin = $allRecords->where('status', 'izin')->count();
        $sakit = $allRecords->where('status', 'sakit')->count();
        $dinas = $allRecords->where('status', 'dinas')->count();
        $alpa = $allRecords->where('status', 'alpa')->count();

        $totalHadirFisik = $hadir + $terlambat + $dinas;

        $persentaseKehadiran = $totalPtk > 0 
            ? round(($totalHadirFisik / $totalPtk) * 100, 1) 
            : ($totalRecord > 0 ? round(($totalHadirFisik / $totalRecord) * 100, 1) : 0);

        $kepatuhanRate = $totalPtk > 0 
            ? round(($totalRecord / $totalPtk) * 100, 1) 
            : ($totalRecord > 0 ? 100 : 0);

        $belumAbsen = max(0, $totalPtk - $totalRecord);

        $summary = [
            'total_record' => $totalRecord,
            'total_ptk' => $totalPtk,
            'total_teachers' => $totalTeachers,
            'total_staff' => $totalStaff,
            'hadir' => $hadir,
            'hadir_tepat_waktu' => $hadirTepatWaktu,
            'terlambat' => $terlambat,
            'izin' => $izin,
            'sakit' => $sakit,
            'dinas' => $dinas,
            'alpa' => $alpa,
            'belum_absen' => $belumAbsen,
            'total_hadir_fisik' => $totalHadirFisik,
            'persentase_kehadiran' => $persentaseKehadiran,
            'kepatuhan_rate' => $kepatuhanRate,
        ];

        // Mode Monitoring Seluruh PTK (Pendidik & Tendik yang sudah maupun belum check-in)
        if ($request->boolean('with_all_ptk') && $isAdmin && $request->filled('tanggal')) {
            $tanggal = $request->query('tanggal');
            $teachers = \App\Models\Teacher::select('id', 'nip', 'nik', 'nama_lengkap', 'jenis_ptk', 'foto_path')->get();
            $staffs = \App\Models\Staff::select('id', 'nik', 'nama_lengkap', 'jabatan', 'foto_path')->get();

            $existingAttendances = TeacherAttendance::with(['teacher:id,nip,nik,nama_lengkap,jenis_ptk,foto_path', 'staff:id,nik,nama_lengkap,jabatan,foto_path'])
                ->whereDate('tanggal', $tanggal)
                ->get();

            $teacherAttendanceMap = $existingAttendances->whereNotNull('teacher_id')->keyBy('teacher_id');
            $staffAttendanceMap = $existingAttendances->whereNotNull('staff_id')->keyBy('staff_id');

            $allPtkList = collect();

            foreach ($teachers as $t) {
                if ($teacherAttendanceMap->has($t->id)) {
                    $att = $teacherAttendanceMap->get($t->id);
                    $att->ptk_type = 'guru';
                    $allPtkList->push($att);
                } else {
                    $allPtkList->push([
                        'id' => null,
                        'teacher_id' => $t->id,
                        'staff_id' => null,
                        'tanggal' => $tanggal,
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'status' => 'belum_absen',
                        'lat' => null,
                        'lng' => null,
                        'jarak_masuk_m' => null,
                        'foto_masuk' => null,
                        'is_terlambat' => false,
                        'menit_keterlambatan' => 0,
                        'keterangan' => 'Belum melakukan check-in GPS',
                        'status_persetujuan' => 'disetujui',
                        'teacher' => $t,
                        'staff' => null,
                        'ptk_type' => 'guru',
                    ]);
                }
            }

            foreach ($staffs as $s) {
                if ($staffAttendanceMap->has($s->id)) {
                    $att = $staffAttendanceMap->get($s->id);
                    $att->ptk_type = 'tendik';
                    $allPtkList->push($att);
                } else {
                    $allPtkList->push([
                        'id' => null,
                        'teacher_id' => null,
                        'staff_id' => $s->id,
                        'tanggal' => $tanggal,
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'status' => 'belum_absen',
                        'lat' => null,
                        'lng' => null,
                        'jarak_masuk_m' => null,
                        'foto_masuk' => null,
                        'is_terlambat' => false,
                        'menit_keterlambatan' => 0,
                        'keterangan' => 'Belum melakukan check-in GPS',
                        'status_persetujuan' => 'disetujui',
                        'teacher' => null,
                        'staff' => $s,
                        'ptk_type' => 'tendik',
                    ]);
                }
            }

            // Filter status jika ada
            if ($request->filled('status')) {
                $statusFilter = $request->query('status');
                $allPtkList = $allPtkList->filter(function ($item) use ($statusFilter) {
                    $st = is_array($item) ? ($item['status'] ?? '') : ($item->status ?? '');
                    if ($statusFilter === 'terlambat') {
                        $isTerlambat = is_array($item) ? ($item['is_terlambat'] ?? false) : ($item->is_terlambat ?? false);
                        return $st === 'terlambat' || $isTerlambat;
                    }
                    return $st === $statusFilter;
                });
            }

            // Filter kategori guru/tendik jika ada
            if ($request->filled('kategori')) {
                $kat = $request->query('kategori');
                $allPtkList = $allPtkList->filter(function ($item) use ($kat) {
                    $ptkType = is_array($item) ? ($item['ptk_type'] ?? '') : ($item->ptk_type ?? '');
                    return $ptkType === $kat;
                });
            }

            // Filter pencarian nama/NIP jika ada
            if ($request->filled('search')) {
                $q = strtolower($request->query('search'));
                $allPtkList = $allPtkList->filter(function ($item) use ($q) {
                    $teacher = is_array($item) ? ($item['teacher'] ?? null) : ($item->teacher ?? null);
                    $staff = is_array($item) ? ($item['staff'] ?? null) : ($item->staff ?? null);
                    $nama = strtolower($teacher?->nama_lengkap ?? $staff?->nama_lengkap ?? '');
                    $nip = strtolower($teacher?->nip ?? $staff?->nik ?? '');
                    return str_contains($nama, $q) || str_contains($nip, $q);
                });
            }

            $allPtkList = $allPtkList->values();

            return response()->json([
                'success' => true,
                'message' => 'Data monitoring presensi guru & tendik berhasil diambil.',
                'data' => $allPtkList,
                'summary' => $summary,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $allPtkList->count(),
                    'total' => $allPtkList->count(),
                ],
            ]);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $attendances = $query->orderBy('tanggal', 'desc')->orderBy('jam_masuk', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Data rekap absensi guru & tendik berhasil diambil.',
            'data' => $attendances->items(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    public function correct(Request $request, int $id): JsonResponse
    {
        $attendance = TeacherAttendance::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:hadir,izin,sakit,alpa,dinas'],
            'jam_masuk' => ['nullable', 'date_format:H:i:s,H:i'],
            'jam_keluar' => ['nullable', 'date_format:H:i:s,H:i'],
            'is_terlambat' => ['sometimes', 'boolean'],
            'menit_keterlambatan' => ['nullable', 'integer', 'min:0'],
            'alasan_koreksi' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $oldData = $attendance->toArray();

        $validated['updated_by'] = $request->user()->id;
        $attendance->update($validated);

        ActivityLogService::log(
            aksi: 'correct_attendance',
            modul: 'teacher_attendance',
            targetType: TeacherAttendance::class,
            targetId: $attendance->id,
            dataSebelum: $oldData,
            dataSesudah: $attendance->fresh()->toArray(),
            keterangan: "Admin mengoreksi absensi ID #{$attendance->id}. Alasan: {$validated['alasan_koreksi']}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Absensi berhasil dikoreksi oleh Admin.',
            'data' => $attendance->fresh(),
        ]);
    }

    public function recap(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Hanya Admin dan Kepala Sekolah yang dapat mengakses rekapitulasi presensi guru & tendik.'], 403);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $tahun = (int) $request->query('tahun');
            $bulan = (int) $request->query('bulan');
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();
        } elseif ($request->filled('bulan')) {
            $bulan = (int) $request->query('bulan');
            $tahun = (int) now()->year;
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();
        }

        if (empty($startDate)) {
            $startDate = now()->startOfMonth()->toDateString();
        }
        if (empty($endDate)) {
            $endDate = now()->toDateString();
        }

        $kategori = $request->query('kategori'); // 'guru' | 'tendik' | null
        $search = $request->query('search');

        $recap = $this->buildTeacherRecapData($startDate, $endDate, $kategori, $search);

        return response()->json([
            'success' => true,
            'message' => 'Data rekapitulasi presensi guru & tendik berhasil dimuat.',
            'data' => $recap,
        ]);
    }

    public function exportRecap(Request $request, AttendanceRecapExportService $exportService): StreamedResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $tahun = (int) $request->query('tahun');
            $bulan = (int) $request->query('bulan');
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();
        } elseif ($request->filled('bulan')) {
            $bulan = (int) $request->query('bulan');
            $tahun = (int) now()->year;
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->toDateString();
        }

        if (empty($startDate)) {
            $startDate = now()->startOfMonth()->toDateString();
        }
        if (empty($endDate)) {
            $endDate = now()->toDateString();
        }

        $kategori = $request->query('kategori');
        $search = $request->query('search');

        $recap = $this->buildTeacherRecapData($startDate, $endDate, $kategori, $search);

        return $exportService->exportTeacherRecap($recap, $startDate, $endDate);
    }

    protected function buildTeacherRecapData(string $startDate, string $endDate, ?string $kategori = null, ?string $search = null): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        // Hitung Hari Efektif Belajar/Kerja (mengabaikan hari Minggu & hari libur kalender akademik)
        $workDays = \App\Models\SchoolCalendar::countEffectiveWorkDays($startDate, $endDate);
        $workDays = max(1, $workDays);

        $teachersQuery = \App\Models\Teacher::select('id', 'nip', 'nik', 'nama_lengkap', 'jenis_ptk', 'foto_path');
        $staffsQuery = \App\Models\Staff::select('id', 'nik', 'nama_lengkap', 'jabatan', 'foto_path');

        if ($search) {
            $teachersQuery->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
            $staffsQuery->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $teachers = ($kategori === 'tendik') ? collect() : $teachersQuery->get();
        $staffs = ($kategori === 'guru') ? collect() : $staffsQuery->get();

        $attendances = TeacherAttendance::whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam_masuk', 'asc')
            ->get();

        $teacherGroup = $attendances->whereNotNull('teacher_id')->groupBy('teacher_id');
        $staffGroup = $attendances->whereNotNull('staff_id')->groupBy('staff_id');

        $ptkList = collect();

        foreach ($teachers as $t) {
            $recs = $teacherGroup->get($t->id, collect());
            $hadirTepatWaktu = $recs->where('status', 'hadir')->where('is_terlambat', false)->count();
            $terlambatRecs = $recs->filter(fn($r) => $r->status === 'terlambat' || $r->is_terlambat);
            $terlambatCount = $terlambatRecs->count();
            $menitTerlambat = (int) $terlambatRecs->sum('menit_keterlambatan');
            $izin = $recs->where('status', 'izin')->count();
            $sakit = $recs->where('status', 'sakit')->count();
            $dinas = $recs->where('status', 'dinas')->count();
            $alpa = $recs->where('status', 'alpa')->count();

            $totalHadirFisik = $hadirTepatWaktu + $terlambatCount + $dinas;
            $totalRecorded = $recs->count();

            $persentase = round(($totalHadirFisik / $workDays) * 100, 1);
            if ($persentase > 100) $persentase = 100.0;

            if ($persentase >= 95) {
                $predikat = 'Sangat Disiplin';
            } elseif ($persentase >= 85) {
                $predikat = 'Disiplin Baik';
            } elseif ($persentase >= 75) {
                $predikat = 'Cukup';
            } else {
                $predikat = 'Perlu Pembinaan';
            }

            $ptkList->push([
                'id' => $t->id,
                'ptk_id' => $t->id,
                'ptk_type' => 'guru',
                'nama_lengkap' => $t->nama_lengkap,
                'nip' => $t->nip ?: '-',
                'nik' => $t->nik ?: '-',
                'peran' => $t->jenis_ptk ?: 'Guru',
                'foto_path' => $t->foto_path,
                'hadir_tepat_waktu' => $hadirTepatWaktu,
                'terlambat' => $terlambatCount,
                'menit_terlambat' => $menitTerlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'dinas' => $dinas,
                'alpa' => $alpa,
                'total_hadir_fisik' => $totalHadirFisik,
                'total_recorded' => $totalRecorded,
                'persentase_kehadiran' => $persentase,
                'predikat' => $predikat,
                'logs' => $recs->map(fn($r) => [
                    'id' => $r->id,
                    'tanggal' => $r->tanggal->toDateString(),
                    'jam_masuk' => $r->jam_masuk,
                    'jam_keluar' => $r->jam_keluar,
                    'status' => $r->status,
                    'is_terlambat' => (bool) $r->is_terlambat,
                    'menit_keterlambatan' => (int) $r->menit_keterlambatan,
                    'jarak_masuk_m' => $r->jarak_masuk_m,
                    'foto_masuk' => $r->foto_masuk,
                    'keterangan' => $r->keterangan,
                ])->values(),
            ]);
        }

        foreach ($staffs as $s) {
            $recs = $staffGroup->get($s->id, collect());
            $hadirTepatWaktu = $recs->where('status', 'hadir')->where('is_terlambat', false)->count();
            $terlambatRecs = $recs->filter(fn($r) => $r->status === 'terlambat' || $r->is_terlambat);
            $terlambatCount = $terlambatRecs->count();
            $menitTerlambat = (int) $terlambatRecs->sum('menit_keterlambatan');
            $izin = $recs->where('status', 'izin')->count();
            $sakit = $recs->where('status', 'sakit')->count();
            $dinas = $recs->where('status', 'dinas')->count();
            $alpa = $recs->where('status', 'alpa')->count();

            $totalHadirFisik = $hadirTepatWaktu + $terlambatCount + $dinas;
            $totalRecorded = $recs->count();

            $persentase = round(($totalHadirFisik / $workDays) * 100, 1);
            if ($persentase > 100) $persentase = 100.0;

            if ($persentase >= 95) {
                $predikat = 'Sangat Disiplin';
            } elseif ($persentase >= 85) {
                $predikat = 'Disiplin Baik';
            } elseif ($persentase >= 75) {
                $predikat = 'Cukup';
            } else {
                $predikat = 'Perlu Pembinaan';
            }

            $ptkList->push([
                'id' => $s->id,
                'ptk_id' => $s->id,
                'ptk_type' => 'tendik',
                'nama_lengkap' => $s->nama_lengkap,
                'nip' => $s->nik ?: '-',
                'nik' => $s->nik ?: '-',
                'peran' => $s->jabatan ?: 'Tendik',
                'foto_path' => $s->foto_path,
                'hadir_tepat_waktu' => $hadirTepatWaktu,
                'terlambat' => $terlambatCount,
                'menit_terlambat' => $menitTerlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'dinas' => $dinas,
                'alpa' => $alpa,
                'total_hadir_fisik' => $totalHadirFisik,
                'total_recorded' => $totalRecorded,
                'persentase_kehadiran' => $persentase,
                'predikat' => $predikat,
                'logs' => $recs->map(fn($r) => [
                    'id' => $r->id,
                    'tanggal' => $r->tanggal->toDateString(),
                    'jam_masuk' => $r->jam_masuk,
                    'jam_keluar' => $r->jam_keluar,
                    'status' => $r->status,
                    'is_terlambat' => (bool) $r->is_terlambat,
                    'menit_keterlambatan' => (int) $r->menit_keterlambatan,
                    'jarak_masuk_m' => $r->jarak_masuk_m,
                    'foto_masuk' => $r->foto_masuk,
                    'keterangan' => $r->keterangan,
                ])->values(),
            ]);
        }

        $avgPersen = $ptkList->count() > 0 ? round($ptkList->avg('persentase_kehadiran'), 1) : 0;
        $summary = [
            'total_ptk' => $ptkList->count(),
            'total_hari_kerja' => $workDays,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rata_rata_kehadiran' => $avgPersen,
            'total_hadir_tepat_waktu' => (int) $ptkList->sum('hadir_tepat_waktu'),
            'total_terlambat' => (int) $ptkList->sum('terlambat'),
            'total_menit_terlambat' => (int) $ptkList->sum('menit_terlambat'),
            'total_izin' => (int) $ptkList->sum('izin'),
            'total_sakit' => (int) $ptkList->sum('sakit'),
            'total_dinas' => (int) $ptkList->sum('dinas'),
            'total_alpa' => (int) $ptkList->sum('alpa'),
            'total_hadir_fisik' => (int) $ptkList->sum('total_hadir_fisik'),
        ];

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_hari_kerja' => $workDays,
            ],
            'summary' => $summary,
            'ptk_list' => $ptkList->values(),
        ];
    }
}
