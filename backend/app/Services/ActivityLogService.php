<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    private static array $hiddenAttributes = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public static function log(
        string $aksi,
        string $modul,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $dataSebelum = null,
        ?array $dataSesudah = null,
        ?string $keterangan = null,
        ?int $userId = null
    ): ?ActivityLog {
        try {
            return ActivityLog::create([
                'user_id' => $userId ?? Auth::id(),
                'aksi' => mb_substr($aksi, 0, 50),
                'modul' => mb_substr($modul, 0, 50),
                'target_type' => $targetType ? mb_substr($targetType, 0, 100) : null,
                'target_id' => $targetId,
                'data_sebelum' => $dataSebelum ? static::cleanData($dataSebelum) : null,
                'data_sesudah' => $dataSesudah ? static::cleanData($dataSesudah) : null,
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'keterangan' => $keterangan ? mb_substr($keterangan, 0, 255) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ActivityLogService::log gagal dicatat: ' . $e->getMessage());
            return null;
        }
    }

    private static function cleanData(array $data): array
    {
        foreach (static::$hiddenAttributes as $key) {
            unset($data[$key]);
        }
        return $data;
    }
}
