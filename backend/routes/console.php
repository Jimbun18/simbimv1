<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:backup-run --type=otomatis')->dailyAt('01:00');

Artisan::command('app:import-dapodik-students {--file=}', function () {
    return $this->call(\App\Console\Commands\ImportDapodikStudentsCommand::class, [
        '--file' => $this->option('file'),
    ]);
});
