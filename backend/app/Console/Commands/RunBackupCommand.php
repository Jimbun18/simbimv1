<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    protected $signature = 'app:backup-run {--type=otomatis : Tipe backup (otomatis/manual)} {--note= : Catatan backup}';

    protected $description = 'Jalankan backup database dan arsipkan ke storage lokal';

    public function handle(BackupService $backupService): int
    {
        $type = $this->option('type') ?: 'otomatis';
        $note = $this->option('note') ?: 'Scheduled daily system backup';

        $this->info("Memulai proses backup ({$type})...");

        try {
            $backup = $backupService->createBackup($type, null, $note);
            $this->info("Backup berhasil dibuat: {$backup->nama_file} ({$backup->formatted_ukuran})");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal membuat backup: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
