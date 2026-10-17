<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'tipe',
        'grup',
        'deskripsi',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
            $setting = static::find($key);
            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->tipe);
        });
    }

    public static function set(string $key, mixed $value, ?string $tipe = null, ?string $grup = null, ?string $deskripsi = null): self
    {
        $setting = static::find($key);
        $tipe = $tipe ?? ($setting ? $setting->tipe : static::inferType($value));

        $rawValue = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
        if (is_bool($value)) {
            $rawValue = $value ? '1' : '0';
        }

        $record = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $rawValue,
                'tipe' => $tipe,
                'grup' => $grup ?? ($setting?->grup ?? 'umum'),
                'deskripsi' => $deskripsi ?? ($setting?->deskripsi ?? null),
            ]
        );

        Cache::forget("setting:{$key}");
        Cache::forget("settings:all");

        return $record;
    }

    public static function castValue(?string $value, string $tipe): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($tipe) {
            'boolean', 'bool' => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    protected static function inferType(mixed $value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'float';
        if (is_array($value)) return 'json';
        return 'string';
    }
}
