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
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'aksi' => $aksi,
            'modul' => $modul,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'data_sebelum' => $dataSebelum ? static::cleanData($dataSebelum) : null,
            'data_sesudah' => $dataSesudah ? static::cleanData($dataSesudah) : null,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'keterangan' => $keterangan,
            'created_at' => now(),
        ]);
    }

    private static function cleanData(array $data): array
    {
        foreach (static::$hiddenAttributes as $key) {
            unset($data[$key]);
        }
        return $data;
    }
}
