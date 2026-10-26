<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeriodLockService
{
    /**
     * Memvalidasi apakah suatu tanggal berada di dalam rentang semester yang valid dan tidak terkunci.
     * @throws ValidationException
     */
    public static function validateDate(string|\DateTimeInterface $date, bool $checkHoliday = false, string $context = 'Operasi'): Semester
    {
        $d = is_string($date) ? substr($date, 0, 10) : $date->format('Y-m-d');

        if ($checkHoliday) {
            self::validateNotHoliday($d, $context);
        }

        $semester = Semester::findByDate($d);

        if (!$semester) {
            throw ValidationException::withMessages([
                'tanggal' => ["Tanggal {$d} berada di luar rentang semester terdaftar (Masa Libur / Jeda Kegiatan Sekolah)."]
            ]);
        }

        if ($semester->is_locked) {
            throw ValidationException::withMessages([
                'tanggal' => ["Semester untuk tanggal {$d} ({$semester->nama_lengkap}) telah ditutup dan dikunci. Data tidak dapat ditambah atau diubah."]
            ]);
        }

        return $semester;
    }

    /**
     * Memvalidasi apakah suatu tanggal bukan hari libur (bukan hari Minggu dan bukan hari libur terdaftar di kalender).
     * @throws ValidationException
     */
    public static function validateNotHoliday(string|\DateTimeInterface $date, string $context = 'Presensi'): array
    {
        $holiday = \App\Models\SchoolCalendar::checkHoliday($date);
        if ($holiday['is_holiday']) {
            throw ValidationException::withMessages([
                'tanggal' => ["{$context} ditutup. Tanggal tersebut adalah {$holiday['name']}."]
            ]);
        }
        return $holiday;
    }

    /**
     * Memvalidasi apakah suatu semester dalam status terkunci.
     * @throws ValidationException
     */
    public static function validateSemesterNotLocked(int|Semester|null $semester): ?Semester
    {
        if ($semester === null) {
            return null;
        }

        $sem = $semester instanceof Semester ? $semester : Semester::find($semester);
        if (!$sem) {
            return null;
        }

        if ($sem->is_locked) {
            throw ValidationException::withMessages([
                'semester' => ["Semester {$sem->nama_lengkap} saat ini berstatus Terkunci. Operasi tidak dapat dilakukan."]
            ]);
        }

        return $sem;
    }

    /**
     * Memastikan tidak ada rentang tanggal semester yang tumpang tindih.
     * @throws ValidationException
     */
    public static function validateNoOverlap(string $tglMulai, string $tglSelesai, ?int $ignoreSemesterId = null): void
    {
        if ($tglMulai > $tglSelesai) {
            throw ValidationException::withMessages([
                'tgl_mulai' => ['Tanggal mulai tidak boleh lebih akhir daripada tanggal selesai.']
            ]);
        }

        $query = Semester::where(function ($q) use ($tglMulai, $tglSelesai) {
            $q->whereBetween('tgl_mulai', [$tglMulai, $tglSelesai])
              ->orWhereBetween('tgl_selesai', [$tglMulai, $tglSelesai])
              ->orWhere(function ($sub) use ($tglMulai, $tglSelesai) {
                  $sub->where('tgl_mulai', '<=', $tglMulai)
                      ->where('tgl_selesai', '>=', $tglSelesai);
              });
        });

        if ($ignoreSemesterId) {
            $query->where('id', '!=', $ignoreSemesterId);
        }

        $overlapping = $query->first();

        if ($overlapping) {
            throw ValidationException::withMessages([
                'tgl_mulai' => ["Rentang tanggal bertabrakan dengan semester lain: {$overlapping->nama_lengkap} ({$overlapping->tgl_mulai->format('d/m/Y')} - {$overlapping->tgl_selesai->format('d/m/Y')})."]
            ]);
        }
    }

    /**
     * Mengaktifkan semester tertentu dan memastikan aturan 'maksimal 1 semester aktif'.
     * @throws ValidationException
     */
    public static function activateSemester(Semester $semester): Semester
    {
        return DB::transaction(function () use ($semester) {
            $ay = $semester->academicYear;
            if (!$ay || $ay->status !== 'aktif') {
                throw ValidationException::withMessages([
                    'academic_year' => ['Semester hanya dapat diaktifkan jika Tahun Ajaran induknya berstatus Aktif.']
                ]);
            }

            // Nonaktifkan semester lain yang sedang aktif
            Semester::where('status', 'aktif')
                ->where('id', '!=', $semester->id)
                ->update(['status' => 'terkunci']);

            $semester->update([
                'status' => 'aktif',
                'reopen_until' => null,
            ]);

            ActivityLogService::log(
                aksi: 'activate',
                modul: 'semesters',
                targetType: Semester::class,
                targetId: $semester->id,
                keterangan: "Mengaktifkan semester: {$semester->nama_lengkap}."
            );

            return $semester->fresh();
        });
    }

    /**
     * Menutup dan mengunci semester dengan validasi draf rapor.
     * @throws ValidationException
     */
    public static function closeSemester(Semester $semester, bool $forceClose = false, ?string $forceReason = null): Semester
    {
        return DB::transaction(function () use ($semester, $forceClose, $forceReason) {
            // Periksa rapor draf
            $draftCount = $semester->reportCards()->where('status', 'draft')->count();

            if ($draftCount > 0 && !$forceClose) {
                throw ValidationException::withMessages([
                    'draft_reports' => [
                        "Terdapat {$draftCount} rapor siswa yang masih berstatus Draf. Selesaikan pengisian rapor terlebih dahulu, atau gunakan opsi konfirmasi eksplisit 'Abaikan Draf Rapor' untuk tetap menutup semester."
                    ]
                ]);
            }

            // Finalisasi rapor yang sudah disetujui
            $semester->reportCards()
                ->where('status', 'disetujui')
                ->update(['status' => 'final']);

            $semester->update([
                'status' => 'terkunci',
                'tutup_at' => now(),
                'tutup_by' => auth()->id(),
                'reopen_until' => null,
            ]);

            ActivityLogService::log(
                aksi: 'close',
                modul: 'semesters',
                targetType: Semester::class,
                targetId: $semester->id,
                keterangan: "Menutup dan mengunci semester: {$semester->nama_lengkap}." . ($forceClose ? " (Paksa tutup draf: {$forceReason})" : '')
            );

            return $semester->fresh();
        });
    }

    /**
     * Membuka sementara semester yang terkunci dengan persetujuan Kepala Sekolah & batas waktu auto-lock.
     * @throws ValidationException
     */
    public static function reopenSemester(
        Semester $semester,
        string $reason,
        \DateTimeInterface|string $reopenUntil,
        int $approvedById
    ): Semester {
        return DB::transaction(function () use ($semester, $reason, $reopenUntil, $approvedById) {
            $until = is_string($reopenUntil) ? new \DateTime($reopenUntil) : $reopenUntil;

            if ($until <= new \DateTime()) {
                throw ValidationException::withMessages([
                    'reopen_until' => ['Batas waktu pembukaan sementara (reopen until) harus berupa waktu di masa depan.']
                ]);
            }

            if (empty(trim($reason)) || strlen(trim($reason)) < 10) {
                throw ValidationException::withMessages([
                    'reopen_reason' => ['Alasan pembukaan kunci semester wajib diisi minimal 10 karakter.']
                ]);
            }

            // Verifikasi bahwa approver adalah Kepala Sekolah
            $approver = User::find($approvedById);
            if (!$approver || !$approver->hasRole('kepala_sekolah')) {
                throw ValidationException::withMessages([
                    'reopen_approved_by' => ['Pembukaan kunci semester wajib disetujui oleh pengguna dengan peran Kepala Sekolah.']
                ]);
            }

            $semester->update([
                'reopen_at' => now(),
                'reopen_until' => $until,
                'reopen_approved_by' => $approvedById,
                'reopen_reason' => trim($reason),
            ]);

            ActivityLogService::log(
                aksi: 'reopen',
                modul: 'semesters',
                targetType: Semester::class,
                targetId: $semester->id,
                keterangan: "Membuka sementara semester {$semester->nama_lengkap} hingga {$until->format('Y-m-d H:i')}. Disetujui Kepala Sekolah: {$approver->nama_lengkap}. Alasan: {$reason}."
            );

            return $semester->fresh();
        });
    }
}
