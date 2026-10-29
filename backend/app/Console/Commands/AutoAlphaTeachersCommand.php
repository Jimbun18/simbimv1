<?php

namespace App\Console\Commands;

use App\Services\TeacherAttendanceService;
use Illuminate\Console\Command;

class AutoAlphaTeachersCommand extends Command
{
    protected $signature = 'attendance:auto-alpha-teachers {--date= : Tanggal spesifik YYYY-MM-DD}';
    protected $description = 'Otomatis menandai Alpa bagi guru dan tendik yang tidak presensi dan tidak mengajukan izin pada hari efektif';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $this->info("Menjalankan sinkronisasi alpa otomatis untuk tanggal: {$date}...");

        $created = TeacherAttendanceService::syncAutoAlpha($date);

        $this->info("Selesai. Sebanyak {$created} guru/tendik ditandai Alpa.");
        return Command::SUCCESS;
    }
}
