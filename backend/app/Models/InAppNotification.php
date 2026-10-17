<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InAppNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'judul',
        'isi',
        'tipe',
        'target_url',
        'data',
        'dibaca',
        'dibaca_pada',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'dibaca' => 'boolean',
            'dibaca_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function send(
        int $userId,
        string $judul,
        string $isi,
        string $tipe = 'info',
        ?string $targetUrl = null,
        ?array $data = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'judul' => $judul,
            'isi' => $isi,
            'tipe' => $tipe,
            'target_url' => $targetUrl,
            'data' => $data,
            'dibaca' => false,
        ]);
    }
}
