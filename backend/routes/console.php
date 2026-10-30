<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:backup-run --type=otomatis')->dailyAt('01:00');
Schedule::command('attendance:auto-alpha-teachers')->dailyAt('16:30');

Artisan::command('app:import-dapodik-students {--file=}', function () {
    return $this->call(\App\Console\Commands\ImportDapodikStudentsCommand::class, [
        '--file' => $this->option('file'),
    ]);
});
