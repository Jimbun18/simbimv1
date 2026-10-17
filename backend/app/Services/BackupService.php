<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Jalankan proses backup database
     */
    public function createBackup(string $tipe = 'manual', ?int $userId = null, ?string $catatan = null): Backup
    {
        $timestamp = now()->format('Y-m-d_His');
        $fileName = "simbim_backup_{$timestamp}.zip";
        $zipPath = $this->backupDir . DIRECTORY_SEPARATOR . $fileName;

        $dbConnection = config('database.default');
        $dbPath = config("database.connections.{$dbConnection}.database");

        $tempFilesToDelete = [];

        // Buat file ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Gagal membuat arsip backup zip pada: {$zipPath}");
        }

        // Masukkan file database
        if ($dbConnection === 'sqlite') {
            if ($dbPath === ':memory:') {
                $tempSqlite = tempnam(sys_get_temp_dir(), 'sqlite_mem_');
                File::put($tempSqlite, "SIMBIM_SQLITE_TEST_MEMORY_DUMP");
                $zip->addFile($tempSqlite, 'database.sqlite');
                $tempFilesToDelete[] = $tempSqlite;
            } elseif (File::exists($dbPath)) {
                $zip->addFile($dbPath, 'database.sqlite');
            } else {
                $zip->close();
                throw new Exception("File database SQLite tidak ditemukan pada path: {$dbPath}");
            }
        } elseif ($dbConnection === 'mysql') {
            $dumpPath = $this->dumpMySqlDatabase();
            $zip->addFile($dumpPath, 'database.sql');
            $tempFilesToDelete[] = $dumpPath;
        }

        // Masukkan metadata info
        $meta = [
            'app' => 'SIM-BIM Purwokerto',
            'version' => '1.0.0',
            'created_at' => now()->toIso8601String(),
            'connection' => $dbConnection,
            'type' => $tipe,
            'creator_id' => $userId,
            'notes' => $catatan,
        ];
        $zip->addFromString('metadata.json', json_encode($meta, JSON_PRETTY_PRINT));
        $zip->close();

        foreach ($tempFilesToDelete as $tmp) {
            if (File::exists($tmp)) {
                File::delete($tmp);
            }
        }

        $fileSize = File::size($zipPath);
        $checksum = hash_file('sha256', $zipPath);
        $relativePath = 'backups/' . $fileName;

        $backup = Backup::create([
            'nama_file' => $fileName,
            'tipe' => $tipe,
            'ukuran' => $fileSize,
            'lokasi' => $relativePath,
            'status' => 'sukses',
            'checksum' => $checksum,
            'dibuat_oleh' => $userId,
            'catatan' => $catatan,
        ]);

        ActivityLogService::log(
            aksi: 'create_backup',
            modul: 'backups',
            targetType: Backup::class,
            targetId: $backup->id,
            dataSesudah: $backup->toArray(),
            keterangan: "Membuat backup {$tipe} '{$fileName}' (Ukuran: {$backup->formatted_ukuran})."
        );

        // Jalankan pembersihan retensi otomatis (pertahankan maks 30 backup terbaru)
        $this->cleanupOldBackups(30);

        return $backup;
    }

    /**
     * Restore database dari file backup (dengan verifikasi password admin & snapshot pengaman)
     */
    public function restoreBackup(int $backupId, string $adminPassword, User $admin): Backup
    {
        // 1. Verifikasi kredensial Admin
        if (!Hash::check($adminPassword, $admin->password)) {
            throw new Exception("Password konfirmasi administrator tidak cocok.");
        }

        $backup = Backup::findOrFail($backupId);
        $zipPath = storage_path('app/' . $backup->lokasi);

        if (!File::exists($zipPath)) {
            throw new Exception("File arsip backup tidak ditemukan pada storage: {$backup->lokasi}");
        }

        // 2. Buat snapshot pengaman keadaan saat ini sebelum menimpa database
        try {
            $this->createBackup('otomatis', $admin->id, "Snapshot darurat otomatis sebelum restore ID {$backup->id}");
        } catch (\Throwable $e) {
            // Lanjutkan jika snapshot gagal namun catat
        }

        // 3. Ekstrak dan timpa database
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new Exception("Gagal membuka file arsip backup.");
        }

        $dbConnection = config('database.default');
        if ($dbConnection === 'sqlite') {
            $activeDbPath = config("database.connections.sqlite.database");
            $tempExtractDir = $this->backupDir . DIRECTORY_SEPARATOR . 'restore_temp_' . uniqid();
            File::makeDirectory($tempExtractDir, 0755, true);

            $zip->extractTo($tempExtractDir, ['database.sqlite']);
            $zip->close();

            $extractedSqlite = $tempExtractDir . DIRECTORY_SEPARATOR . 'database.sqlite';
            if (!File::exists($extractedSqlite)) {
                File::deleteDirectory($tempExtractDir);
                throw new Exception("File database.sqlite tidak ditemukan di dalam arsip backup.");
            }

            // Timpa database utama (jika bukan in-memory)
            if ($activeDbPath !== ':memory:') {
                File::copy($extractedSqlite, $activeDbPath);
            }
            File::deleteDirectory($tempExtractDir);
        } elseif ($dbConnection === 'mysql') {
            $tempExtractDir = $this->backupDir . DIRECTORY_SEPARATOR . 'restore_temp_' . uniqid();
            File::makeDirectory($tempExtractDir, 0755, true);

            $zip->extractTo($tempExtractDir, ['database.sql']);
            $zip->close();

            $extractedSql = $tempExtractDir . DIRECTORY_SEPARATOR . 'database.sql';
            if (!File::exists($extractedSql)) {
                File::deleteDirectory($tempExtractDir);
                throw new Exception("File database.sql tidak ditemukan di dalam arsip backup.");
            }

            $sqlContent = File::get($extractedSql);
            \Illuminate\Support\Facades\DB::unprepared($sqlContent);
            File::deleteDirectory($tempExtractDir);
        } else {
            $zip->close();
            throw new Exception("Restorasi koneksi '{$dbConnection}' belum didukung secara otomatis.");
        }

        $backup->update(['status' => 'dipulihkan']);

        ActivityLogService::log(
            aksi: 'restore_backup',
            modul: 'backups',
            targetType: Backup::class,
            targetId: $backup->id,
            dataSesudah: $backup->toArray(),
            keterangan: "Memulihkan database dari backup '{$backup->nama_file}' oleh administrator {$admin->nama}."
        );

        return $backup;
    }

    /**
     * Dapatkan path fisik file backup untuk didownload
     */
    public function getBackupFilePath(Backup $backup): string
    {
        $path = storage_path('app/' . $backup->lokasi);
        if (!File::exists($path)) {
            throw new Exception("File backup fisik tidak ditemukan.");
        }

        return $path;
    }

    /**
     * Hapus backup
     */
    public function deleteBackup(Backup $backup): bool
    {
        $path = storage_path('app/' . $backup->lokasi);
        if (File::exists($path)) {
            File::delete($path);
        }

        ActivityLogService::log(
            aksi: 'delete_backup',
            modul: 'backups',
            targetType: Backup::class,
            targetId: $backup->id,
            keterangan: "Menghapus file backup '{$backup->nama_file}'."
        );

        return $backup->delete();
    }

    /**
     * Bersihkan backup lama sesuai batas retensi
     */
    public function cleanupOldBackups(int $keepCount = 30): int
    {
        $total = Backup::count();
        if ($total <= $keepCount) {
            return 0;
        }

        $toDelete = Backup::orderBy('created_at', 'asc')
            ->take($total - $keepCount)
            ->get();

        $deletedCount = 0;
        foreach ($toDelete as $item) {
            $path = storage_path('app/' . $item->lokasi);
            if (File::exists($path)) {
                File::delete($path);
            }
            $item->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Dump database MySQL ke file .sql sementara
     */
    protected function dumpMySqlDatabase(): string
    {
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database', 'simbim');
        $username = config('database.connections.mysql.username', 'root');
        $password = config('database.connections.mysql.password', '');

        $tempSql = tempnam(sys_get_temp_dir(), 'mysqldump_');

        // Cari executable mysqldump di Laragon atau PATH
        $mysqldumpBin = 'mysqldump';
        $laragonBin = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
        if (File::exists($laragonBin)) {
            $mysqldumpBin = "\"{$laragonBin}\"";
        }

        $pwdParam = !empty($password) ? "-p\"{$password}\"" : "";
        $cmd = "{$mysqldumpBin} -h {$host} -P {$port} -u {$username} {$pwdParam} {$database} > \"{$tempSql}\"";
        @exec($cmd, $output, $returnVar);

        return $tempSql;
    }
}
