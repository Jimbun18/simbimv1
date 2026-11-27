<?php

namespace App\Console\Commands;

use App\Mail\TeacherNotificationMail;
use App\Models\InAppNotification;
use App\Models\SchoolCalendar;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Services\NotificationService;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAttendanceRemindersCommand extends Command
{
    protected $signature = 'attendance:send-reminders
                            {--type=auto : Tipe pengingat (checkin, checkout, auto)}
                            {--force : Paksa kirim tanpa mengecek kecocokan jam & duplikat harian}
                            {--date= : Tanggal target YYYY-MM-DD (default hari ini)}';

    protected $description = 'Kirim notifikasi rutin pengingat presensi masuk (pagi) dan pulang (sore) bagi guru & tendik';

    public function handle(): int
    {
        $targetDate = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : now()->toDateString();
        $force = (bool) $this->option('force');
        $requestedType = strtolower((string) $this->option('type'));

        // 1. Abaikan jika hari libur sekolah / Minggu
        $holiday = SchoolCalendar::checkHoliday($targetDate);
        if (!empty($holiday['is_holiday']) && !$force) {
            $reason = $holiday['reason'] ?? $holiday['title'] ?? $holiday['keterangan'] ?? 'Hari Libur';
            $this->info("Hari {$targetDate} adalah hari libur ({$reason}). Pengingat dilewati.");
            return self::SUCCESS;
        }

        $currentTime = now()->format('H:i');

        $typesToRun = [];
        if ($requestedType === 'auto') {
            if ($this->shouldRunNow('checkin', $currentTime, $targetDate, $force)) {
                $typesToRun[] = 'checkin';
            }
            if ($this->shouldRunNow('checkout', $currentTime, $targetDate, $force)) {
                $typesToRun[] = 'checkout';
            }
        } elseif (in_array($requestedType, ['checkin', 'checkout'], true)) {
            if ($this->shouldRunNow($requestedType, $currentTime, $targetDate, $force)) {
                $typesToRun[] = $requestedType;
            }
        } else {
            $this->error("Tipe tidak valid: {$requestedType}. Gunakan checkin, checkout, atau auto.");
            return self::FAILURE;
        }

        if (empty($typesToRun)) {
            return self::SUCCESS;
        }

        $useInApp = Setting::get('notification.channels.in_app', '1') === '1';
        $useWebPush = Setting::get('notification.channels.web_push', '1') === '1';
        $useEmail = Setting::get('notification.channels.email', '0') === '1';

        $totalNotified = 0;

        foreach ($typesToRun as $type) {
            $count = $this->processReminder($type, $targetDate, $useInApp, $useWebPush, $useEmail);
            $totalNotified += $count;
            $this->info("Pengingat {$type} ({$targetDate}) selesai dikirim ke {$count} PTK.");
        }

        return self::SUCCESS;
    }

    protected function shouldRunNow(string $type, string $currentTime, string $targetDate, bool $force): bool
    {
        if ($force) {
            return true;
        }

        $enabledKey = $type === 'checkin'
            ? 'notification.checkin_reminder.enabled'
            : 'notification.checkout_reminder.enabled';

        $timeKey = $type === 'checkin'
            ? 'notification.checkin_reminder.time'
            : 'notification.checkout_reminder.time';

        $defaultTime = $type === 'checkin' ? '07:15' : '14:35';

        if (Setting::get($enabledKey, '1') !== '1') {
            return false;
        }

        $configuredTime = substr((string) Setting::get($timeKey, $defaultTime), 0, 5);
        if ($currentTime < $configuredTime) {
            return false;
        }

        // Pastikan hanya berjalan sekali per hari per tipe
        $cacheKey = "attendance_reminder_sent:{$type}:{$targetDate}";
        return Cache::add($cacheKey, true, now()->endOfDay());
    }

    protected function processReminder(
        string $type,
        string $targetDate,
        bool $useInApp,
        bool $useWebPush,
        bool $useEmail
    ): int {
        $attendances = TeacherAttendance::whereDate('tanggal', $targetDate)->get();

        $recipients = $this->collectRecipients($type, $attendances);
        if (empty($recipients)) {
            return 0;
        }

        $tglFormatted = Carbon::parse($targetDate)->format('d/m/Y');
        $title = $type === 'checkin'
            ? 'Pengingat Presensi Masuk'
            : 'Pengingat Presensi Pulang';

        $body = $type === 'checkin'
            ? "Selamat pagi! Anda belum melakukan presensi masuk hari ini ({$tglFormatted}). Silakan lakukan check-in di SIM-BIM."
            : "Waktunya pulang! Anda belum melakukan presensi keluar hari ini ({$tglFormatted}). Jangan lupa check-out sebelum meninggalkan sekolah.";

        $notifiedUserIds = [];
        $count = 0;

        foreach ($recipients as $recipient) {
            $userId = $recipient['user_id'];
            if (!$userId || in_array($userId, $notifiedUserIds, true)) {
                continue;
            }
            $notifiedUserIds[] = $userId;

            if ($useInApp) {
                try {
                    InAppNotification::send(
                        userId: $userId,
                        judul: $title,
                        isi: $body,
                        tipe: 'warning',
                        targetUrl: '/presensi',
                        data: [
                            'reminder_type' => $type,
                            'date' => $targetDate,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning("Gagal simpan in-app reminder ke user ID {$userId}: " . $e->getMessage());
                }
            }

            if ($useWebPush) {
                WebPushService::sendToUser($userId, $title, $body, '/presensi', [
                    'tag' => "attendance-{$type}-{$targetDate}",
                ]);
            }

            if ($useEmail && !empty($recipient['email'])) {
                try {
                    $appUrl = config('app.url', 'http://localhost:5173');
                    Mail::to($recipient['email'])->send(new TeacherNotificationMail(
                        title: $title,
                        recipientName: $recipient['name'],
                        bodyMessage: $body,
                        actionUrl: rtrim($appUrl, '/') . '/presensi',
                        actionText: 'Buka Halaman Presensi'
                    ));
                } catch (\Throwable $e) {
                    Log::warning("Gagal kirim email reminder ke {$recipient['email']}: " . $e->getMessage());
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * Kumpulkan daftar PTK (Guru & Tendik) yang perlu diingatkan tanpa duplikasi.
     */
    protected function collectRecipients(string $type, $attendances): array
    {
        $teachers = Teacher::where('status', 'aktif')->with('user')->get();
        $teacherUserIds = $teachers->pluck('user_id')->filter()->unique();
        $teacherNiks = $teachers->pluck('nik')->filter()->unique();

        $staffs = Staff::where('status', 'aktif')
            ->with('user')
            ->where(function ($q) use ($teacherUserIds) {
                $q->whereNull('user_id')->orWhereNotIn('user_id', $teacherUserIds);
            })
            ->where(function ($q) use ($teacherNiks) {
                $q->whereNull('nik')->orWhereNotIn('nik', $teacherNiks);
            })
            ->get();

        $recipients = [];

        if ($type === 'checkin') {
            $recordedTeacherIds = $attendances->whereNotNull('teacher_id')->pluck('teacher_id')->toArray();
            $recordedStaffIds = $attendances->whereNotNull('staff_id')->pluck('staff_id')->toArray();

            foreach ($teachers as $teacher) {
                if ($teacher->user_id && !in_array($teacher->id, $recordedTeacherIds, true)) {
                    $recipients[] = [
                        'user_id' => $teacher->user_id,
                        'name' => $teacher->nama_lengkap,
                        'email' => NotificationService::getTeacherEmail($teacher),
                    ];
                }
            }

            foreach ($staffs as $staff) {
                if ($staff->user_id && !in_array($staff->id, $recordedStaffIds, true)) {
                    $recipients[] = [
                        'user_id' => $staff->user_id,
                        'name' => $staff->nama_lengkap,
                        'email' => !empty($staff->email) ? trim($staff->email) : $staff->user?->email,
                    ];
                }
            }
        } else {
            // Check-out: sudah hadir (jam_masuk terisi) dan belum check-out (jam_keluar kosong)
            $needCheckoutTeacherIds = $attendances
                ->whereNotNull('teacher_id')
                ->where('status', 'hadir')
                ->whereNotNull('jam_masuk')
                ->whereNull('jam_keluar')
                ->pluck('teacher_id')
                ->toArray();

            $needCheckoutStaffIds = $attendances
                ->whereNotNull('staff_id')
                ->where('status', 'hadir')
                ->whereNotNull('jam_masuk')
                ->whereNull('jam_keluar')
                ->pluck('staff_id')
                ->toArray();

            foreach ($teachers as $teacher) {
                if ($teacher->user_id && in_array($teacher->id, $needCheckoutTeacherIds, true)) {
                    $recipients[] = [
                        'user_id' => $teacher->user_id,
                        'name' => $teacher->nama_lengkap,
                        'email' => NotificationService::getTeacherEmail($teacher),
                    ];
                }
            }

            foreach ($staffs as $staff) {
                if ($staff->user_id && in_array($staff->id, $needCheckoutStaffIds, true)) {
                    $recipients[] = [
                        'user_id' => $staff->user_id,
                        'name' => $staff->nama_lengkap,
                        'email' => !empty($staff->email) ? trim($staff->email) : $staff->user?->email,
                    ];
                }
            }
        }

        return $recipients;
    }
}
