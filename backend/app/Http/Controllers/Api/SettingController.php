<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->groupBy('grup')->map(function ($group) {
            return $group->mapWithKeys(function ($item) {
                return [
                    $item->key => [
                        'value' => Setting::castValue($item->value, $item->tipe),
                        'tipe' => $item->tipe,
                        'deskripsi' => $item->deskripsi,
                    ],
                ];
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil diambil.',
            'data' => $settings,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['present'],
        ]);

        $updated = [];
        $oldValues = [];

        foreach ($validated['settings'] as $item) {
            $key = $item['key'];
            $val = $item['value'];
            $oldValues[$key] = Setting::get($key);
            $record = Setting::set($key, $val);
            $updated[$key] = Setting::castValue($record->value, $record->tipe);
        }

        ActivityLogService::log(
            aksi: 'update_settings',
            modul: 'settings',
            targetType: Setting::class,
            dataSebelum: $oldValues,
            dataSesudah: $updated,
            keterangan: 'Memperbarui pengaturan sistem SIM-BIM.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil diperbarui.',
            'data' => $updated,
        ]);
    }
}
