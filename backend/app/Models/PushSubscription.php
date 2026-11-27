<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function upsertForUser(
        int $userId,
        string $endpoint,
        string $publicKey,
        string $authToken,
        string $contentEncoding = 'aesgcm',
        ?string $userAgent = null
    ): self {
        $hash = hash('sha256', $endpoint);

        return static::updateOrCreate(
            ['endpoint_hash' => $hash],
            [
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'public_key' => $publicKey,
                'auth_token' => $authToken,
                'content_encoding' => $contentEncoding ?: 'aesgcm',
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            ]
        );
    }
}
