<?php

namespace App\Services;

use App\Models\SchoolCalendar;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;

class TeacherAttendanceService
{
    /**
     * Sinkronisasi otomatis status Alpa bagi guru & tendik aktif yang tidak absen dan tidak mengajukan izin.
     * Mengikuti kalender akademik: jika hari libur (Minggu atau terdaftar di kalender sebagai libur), otomatis diabaikan.
     */
    public static function syncAutoAlpha(string|\DateTimeInterface|null $date = null): int
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();
        $isToday = $targetDate === now()->toDateString();

        // 1. Cek kalender akademik: hari libur tidak pernah di-alpa-kan
        $holiday = SchoolCalendar::checkHoliday($targetDate);
        if ($holiday['is_holiday']) {
            return 0;
        }

        // 2. Jika hari ini, periksa apakah sudah melewati batas akhir jam kerja/sekolah (cutoff sore)
        // Guru yang datang terlambat TETAP DIANGGAP HADIR (terlambat), BUKAN alpa.
        // Alpa HANYA untuk PTK yang sama sekali tidak absen hingga jam kerja sekolah berakhir.
        if ($isToday) {
            $cutoff = Setting::get('auto_alpha_cutoff_time', '16:00');
            $currentTime = now()->format('H:i');
            if ($currentTime < $cutoff) {
                // Selama jam sekolah masih aktif hari ini, jangan tandai alpa otomatis
                return 0;
            }
        }

        // 3. Ambil seluruh PTK aktif
        $activeTeachers = Teacher::where('status', 'aktif')->get();
        $activeStaffs = Staff::where('status', 'aktif')->get();

        // 4. Ambil ID yang sudah tercatat hadir/izin/sakit/alpa pada tanggal ini
        $existing = TeacherAttendance::whereDate('tanggal', $targetDate)->get();
        $existingTeacherIds = $existing->whereNotNull('teacher_id')->pluck('teacher_id')->toArray();
        $existingStaffIds = $existing->whereNotNull('staff_id')->pluck('staff_id')->toArray();

        $createdCount = 0;

        foreach ($activeTeachers as $teacher) {
            if (!in_array($teacher->id, $existingTeacherIds, true)) {
                TeacherAttendance::create([
                    'teacher_id' => $teacher->id,
                    'staff_id' => null,
                    'tanggal' => $targetDate,
                    'jam_masuk' => null,
                    'jam_keluar' => null,
                    'status' => 'alpa',
                    'status_persetujuan' => 'disetujui',
                    'keterangan' => 'Alpa (Otomatis Kalender Akademik)',
                    'is_terlambat' => false,
                    'menit_keterlambatan' => 0,
                    'created_by' => 1,
                ]);
                $createdCount++;
            }
        }

        foreach ($activeStaffs as $staff) {
            if (!in_array($staff->id, $existingStaffIds, true)) {
                TeacherAttendance::create([
                    'teacher_id' => null,
                    'staff_id' => $staff->id,
                    'tanggal' => $targetDate,
                    'jam_masuk' => null,
                    'jam_keluar' => null,
                    'status' => 'alpa',
                    'status_persetujuan' => 'disetujui',
                    'keterangan' => 'Alpa (Otomatis Kalender Akademik)',
                    'is_terlambat' => false,
                    'menit_keterlambatan' => 0,
                    'created_by' => 1,
                ]);
                $createdCount++;
            }
        }

        return $createdCount;
    }

    /**
     * Sinkronisasi otomatis status Alpa dalam rentang tanggal tertentu (untuk rekapitulasi).
     */
    public static function syncAutoAlphaRange(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $today = now()->toDateString();

        $totalCreated = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($d->toDateString() > $today) {
                break;
            }
            $totalCreated += self::syncAutoAlpha($d->toDateString());
        }

        return $totalCreated;
    }
}
