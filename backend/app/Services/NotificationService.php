<?php

namespace App\Services;

use App\Mail\AnnouncementNotificationMail;
use App\Mail\TeacherNotificationMail;
use App\Models\Announcement;
use App\Models\Guardian;
use App\Models\InAppNotification;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Dapatkan email tujuan guru.
     * Prioritas: Mengambil dari tabel data guru (teachers.email).
     * Cadangan (Fallback): Mengambil dari tabel akun pengguna (users.email) jika di data guru kosong.
     */
    public static function getTeacherEmail(Teacher|int $teacher): ?string
    {
        if (is_int($teacher)) {
            $teacher = Teacher::with('user')->find($teacher);
        }

        if (!$teacher) {
            return null;
        }

        // Ambil email dari data guru (teachers.email), jika kosong baru fallback ke users.email
        return !empty($teacher->email) ? trim($teacher->email) : (!empty($teacher->user?->email) ? trim($teacher->user->email) : null);
    }

    /**
     * Kirim notifikasi lengkap ke seorang Guru.
     * 1. Selalu menyimpan notifikasi di Dashboard Guru (In-App Notification).
     * 2. Jika $sendEmail = true, mengirim email via SMTP ke email data guru.
     */
    public static function notifyTeacher(
        Teacher|int $teacher,
        string $title,
        string $message,
        string $type = 'info',
        ?string $targetUrl = null,
        bool $sendEmail = true,
        array $extraDetails = []
    ): void {
        if (is_int($teacher)) {
            $teacher = Teacher::with('user')->find($teacher);
        }

        if (!$teacher) {
            return;
        }

        // 1. Notifikasi di Dashboard (In-App)
        if ($teacher->user_id) {
            try {
                InAppNotification::send(
                    userId: $teacher->user_id,
                    judul: $title,
                    isi: $message,
                    tipe: $type,
                    targetUrl: $targetUrl,
                    data: !empty($extraDetails) ? $extraDetails : null
                );
            } catch (\Throwable $e) {
                Log::warning("Gagal menyimpan in-app notification guru ID {$teacher->id}: " . $e->getMessage());
            }
        }

        // 2. Notifikasi Email (SMTP)
        if ($sendEmail) {
            $targetEmail = self::getTeacherEmail($teacher);

            if ($targetEmail) {
                try {
                    $appUrl = config('app.url', 'http://localhost:5173');
                    $fullActionUrl = $targetUrl ? (str_starts_with($targetUrl, 'http') ? $targetUrl : rtrim($appUrl, '/') . '/' . ltrim($targetUrl, '/')) : null;

                    Mail::to($targetEmail)->send(new TeacherNotificationMail(
                        title: $title,
                        recipientName: $teacher->nama_lengkap,
                        bodyMessage: $message,
                        actionUrl: $fullActionUrl,
                        actionText: 'Buka di SIM-BIM',
                        extraDetails: $extraDetails
                    ));
                } catch (\Throwable $e) {
                    // Log error tanpa menghentikan proses aplikasi utama
                    Log::error("Gagal mengirim email notifikasi SMTP ke guru ({$targetEmail}): " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Kirim notifikasi khusus ke seluruh Guru Bimbingan Konseling (Guru BK).
     * Dipanggil saat ada catatan observasi perilaku siswa baru atau eskalasi kasus.
     */
    public static function notifyGuruBk(
        string $title,
        string $message,
        ?string $targetUrl = '/observations',
        bool $sendEmail = true,
        array $extraDetails = []
    ): void {
        // Ambil semua guru yang memiliki jabatan atau tugas tambahan BK / Konseling
        $guruBkList = Teacher::where(function ($q) {
            $q->where('jabatan', 'like', '%bk%')
              ->orWhere('jabatan', 'like', '%bimbingan%')
              ->orWhere('jabatan', 'like', '%konseling%')
              ->orWhere('tugas_tambahan', 'like', '%bk%')
              ->orWhere('tugas_tambahan', 'like', '%bimbingan%')
              ->orWhere('tugas_tambahan', 'like', '%konseling%');
        })
        ->where('status', 'aktif')
        ->with('user')
        ->get();

        // Jika tidak ditemukan dari kolom jabatan, fallback mencari user dengan method isGuruBk
        if ($guruBkList->isEmpty()) {
            $bkUsers = User::whereHas('role', fn($r) => $r->where('nama', Role::GURU))
                ->with('teacher')
                ->get()
                ->filter(fn($u) => $u->isGuruBk());

            foreach ($bkUsers as $u) {
                if ($u->teacher) {
                    $guruBkList->push($u->teacher);
                }
            }
        }

        foreach ($guruBkList as $guruBk) {
            self::notifyTeacher(
                teacher: $guruBk,
                title: $title,
                message: $message,
                type: 'warning',
                targetUrl: $targetUrl,
                sendEmail: $sendEmail,
                extraDetails: $extraDetails
            );
        }
    }

    /**
     * Kirim notifikasi ke Administrator / Kepala Sekolah.
     */
    public static function notifyAdmins(
        string $title,
        string $message,
        string $type = 'info',
        ?string $targetUrl = null,
        bool $sendEmail = false
    ): void {
        $admins = User::whereHas('role', function ($q) {
            $q->whereIn('nama', [Role::KEPALA_SEKOLAH, Role::STAF_ADMIN]);
        })
        ->where('status', 'aktif')
        ->get();

        foreach ($admins as $admin) {
            try {
                InAppNotification::send(
                    userId: $admin->id,
                    judul: $title,
                    isi: $message,
                    tipe: $type,
                    targetUrl: $targetUrl
                );
            } catch (\Throwable $e) {
                Log::warning("Gagal menyimpan in-app notification admin ID {$admin->id}: " . $e->getMessage());
            }

            if ($sendEmail && !empty($admin->email)) {
                try {
                    $appUrl = config('app.url', 'http://localhost:5173');
                    $fullActionUrl = $targetUrl ? (str_starts_with($targetUrl, 'http') ? $targetUrl : rtrim($appUrl, '/') . '/' . ltrim($targetUrl, '/')) : null;

                    Mail::to($admin->email)->send(new TeacherNotificationMail(
                        title: $title,
                        recipientName: $admin->nama,
                        bodyMessage: $message,
                        actionUrl: $fullActionUrl,
                        actionText: 'Lihat di SIM-BIM'
                    ));
                } catch (\Throwable $e) {
                    Log::error("Gagal mengirim email admin ({$admin->email}): " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Kirim notifikasi umum ke akun User tertentu.
     */
    public static function notifyUser(
        User|int $user,
        string $title,
        string $message,
        string $type = 'info',
        ?string $targetUrl = null
    ): void {
        $userId = is_int($user) ? $user : $user->id;

        try {
            InAppNotification::send(
                userId: $userId,
                judul: $title,
                isi: $message,
                tipe: $type,
                targetUrl: $targetUrl
            );
        } catch (\Throwable $e) {
            Log::warning("Gagal menyimpan notifikasi user ID {$userId}: " . $e->getMessage());
        }
    }

    /**
     * Broadcast Pengumuman Resmi (In-App Dashboard + Email SMTP)
     * Mengikuti target_role: 'semua', 'guru', 'wali', 'staf_admin', 'kepala_sekolah'.
     * Khusus Guru: Mengambil email dari data guru (teachers.email) dengan fallback users.email.
     */
    public static function broadcastAnnouncement(Announcement $announcement): void
    {
        $appUrl = config('app.url', 'http://localhost:5173');
        $publisher = $announcement->creator?->nama ?? 'Kepala Sekolah / Staf Admin';
        $tglFormatted = Carbon::parse($announcement->tgl_terbit)->format('d/m/Y');
        $lampiranUrl = $announcement->lampiran_path ? asset('storage/' . $announcement->lampiran_path) : null;
        $targetRole = $announcement->target_role;
        $excerpt = Str::limit(strip_tags($announcement->isi), 120);

        // 1. Kirim ke GURU (jika target adalah 'semua' atau 'guru')
        if (in_array($targetRole, ['semua', 'guru'])) {
            $teachers = Teacher::where('status', 'aktif')->with('user')->get();
            foreach ($teachers as $teacher) {
                // Notifikasi Dashboard Guru
                if ($teacher->user_id && $teacher->user_id !== $announcement->created_by) {
                    try {
                        InAppNotification::send(
                            userId: $teacher->user_id,
                            judul: "Pengumuman: {$announcement->judul}",
                            isi: $excerpt,
                            tipe: 'info',
                            targetUrl: '/announcements'
                        );
                    } catch (\Throwable $e) {
                        Log::warning("Gagal kirim in-app announcement ke guru ID {$teacher->id}: " . $e->getMessage());
                    }
                }

                // Notifikasi Email Guru (Ambil dari data guru: teachers.email)
                $targetEmail = self::getTeacherEmail($teacher);
                if ($targetEmail) {
                    try {
                        Mail::to($targetEmail)->send(new AnnouncementNotificationMail(
                            judul: $announcement->judul,
                            isi: $announcement->isi,
                            kategori: $announcement->kategori ?? 'umum',
                            recipientName: $teacher->nama_lengkap,
                            tglTerbit: $tglFormatted,
                            publisherName: $publisher,
                            actionUrl: rtrim($appUrl, '/') . '/announcements',
                            lampiranUrl: $lampiranUrl
                        ));
                    } catch (\Throwable $e) {
                        Log::error("Gagal kirim email pengumuman ke guru ({$targetEmail}): " . $e->getMessage());
                    }
                }
            }
        }

        // 2. Kirim ke WALI MURID (jika target adalah 'semua' atau 'wali')
        if (in_array($targetRole, ['semua', 'wali'])) {
            $guardians = Guardian::with('user')->get();
            foreach ($guardians as $guardian) {
                // Notifikasi Dashboard Portal Wali
                if ($guardian->user_id && $guardian->user_id !== $announcement->created_by) {
                    try {
                        InAppNotification::send(
                            userId: $guardian->user_id,
                            judul: "Pengumuman: {$announcement->judul}",
                            isi: $excerpt,
                            tipe: 'info',
                            targetUrl: '/portal-wali?tab=announcements'
                        );
                    } catch (\Throwable $e) {
                        Log::warning("Gagal kirim in-app announcement ke wali ID {$guardian->id}: " . $e->getMessage());
                    }
                }

                // Notifikasi Email Wali (Ambil dari data wali: guardians.email)
                $targetEmail = !empty($guardian->email) ? trim($guardian->email) : $guardian->user?->email;
                if ($targetEmail) {
                    try {
                        Mail::to($targetEmail)->send(new AnnouncementNotificationMail(
                            judul: $announcement->judul,
                            isi: $announcement->isi,
                            kategori: $announcement->kategori ?? 'umum',
                            recipientName: $guardian->nama_lengkap,
                            tglTerbit: $tglFormatted,
                            publisherName: $publisher,
                            actionUrl: rtrim($appUrl, '/') . '/portal-wali',
                            lampiranUrl: $lampiranUrl
                        ));
                    } catch (\Throwable $e) {
                        Log::error("Gagal kirim email pengumuman ke wali ({$targetEmail}): " . $e->getMessage());
                    }
                }
            }
        }

        // 3. Kirim ke STAF ADMIN & KEPALA SEKOLAH (jika target adalah 'semua', 'staf_admin', atau 'kepala_sekolah')
        if (in_array($targetRole, ['semua', 'staf_admin', 'kepala_sekolah'])) {
            $staffUsers = User::whereHas('role', function ($q) use ($targetRole) {
                if ($targetRole === 'semua') {
                    $q->whereIn('nama', [Role::KEPALA_SEKOLAH, Role::STAF_ADMIN]);
                } else {
                    $q->where('nama', $targetRole);
                }
            })
            ->where('status', 'aktif')
            ->where('id', '!=', $announcement->created_by)
            ->get();

            foreach ($staffUsers as $staffUser) {
                try {
                    InAppNotification::send(
                        userId: $staffUser->id,
                        judul: "Pengumuman: {$announcement->judul}",
                        isi: $excerpt,
                        tipe: 'info',
                        targetUrl: '/announcements'
                    );
                } catch (\Throwable $e) {
                    Log::warning("Gagal kirim in-app announcement ke staff ID {$staffUser->id}: " . $e->getMessage());
                }

                if (!empty($staffUser->email)) {
                    try {
                        Mail::to($staffUser->email)->send(new AnnouncementNotificationMail(
                            judul: $announcement->judul,
                            isi: $announcement->isi,
                            kategori: $announcement->kategori ?? 'umum',
                            recipientName: $staffUser->nama,
                            tglTerbit: $tglFormatted,
                            publisherName: $publisher,
                            actionUrl: rtrim($appUrl, '/') . '/announcements',
                            lampiranUrl: $lampiranUrl
                        ));
                    } catch (\Throwable $e) {
                        Log::error("Gagal kirim email pengumuman ke staff ({$staffUser->email}): " . $e->getMessage());
                    }
                }
            }
        }
    }
}

