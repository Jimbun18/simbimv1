<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolCalendar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_calendars';

    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'judul',
        'deskripsi',
        'tipe',
        'is_libur',
        'warna',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_libur' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Cek apakah suatu tanggal adalah hari libur (Minggu atau terdaftar di kalender sebagai libur).
     */
    public static function checkHoliday(string|\DateTimeInterface $date): array
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dateStr = $carbon->toDateString();

        // 1. Hari Minggu selalu libur rutin
        if ($carbon->dayOfWeek === Carbon::SUNDAY) {
            return [
                'is_holiday' => true,
                'is_sunday' => true,
                'name' => 'Hari Minggu (Libur Rutin)',
                'type' => 'libur_rutin',
                'event' => null,
            ];
        }

        // 2. Cek kalender akademik
        $event = self::where('is_libur', true)
            ->where('tanggal_mulai', '<=', $dateStr)
            ->where('tanggal_selesai', '>=', $dateStr)
            ->first();

        if ($event) {
            return [
                'is_holiday' => true,
                'is_sunday' => false,
                'name' => $event->judul,
                'type' => $event->tipe,
                'event' => $event,
            ];
        }

        return [
            'is_holiday' => false,
            'is_sunday' => false,
            'name' => null,
            'type' => null,
            'event' => null,
        ];
    }

    /**
     * Hitung jumlah Hari Efektif Belajar (HEB) dalam rentang tanggal.
     * Mengabaikan hari Minggu dan hari-hari yang ditandai is_libur = true di kalender.
     */
    public static function countEffectiveWorkDays(string|\DateTimeInterface $startDate, string|\DateTimeInterface $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            return 0;
        }

        // Ambil semua event libur dalam rentang ini
        $holidayEvents = self::where('is_libur', true)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('tanggal_mulai', [$start->toDateString(), $end->toDateString()])
                  ->orWhereBetween('tanggal_selesai', [$start->toDateString(), $end->toDateString()])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('tanggal_mulai', '<=', $start->toDateString())
                          ->where('tanggal_selesai', '>=', $end->toDateString());
                  });
            })
            ->get();

        $effectiveDays = 0;
        $curr = $start->copy();

        while ($curr->lte($end)) {
            // Abaikan hari Minggu
            if ($curr->dayOfWeek === Carbon::SUNDAY) {
                $curr->addDay();
                continue;
            }

            $dateStr = $curr->toDateString();
            $isHoliday = $holidayEvents->contains(function ($event) use ($dateStr) {
                return $event->tanggal_mulai->toDateString() <= $dateStr &&
                       $event->tanggal_selesai->toDateString() >= $dateStr;
            });

            if (!$isHoliday) {
                $effectiveDays++;
            }

            $curr->addDay();
        }

        return $effectiveDays;
    }
}
