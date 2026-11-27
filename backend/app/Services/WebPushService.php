<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public static function getPublicKey(): ?string
    {
        $key = config('services.webpush.vapid.public_key');
        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }

    public static function isConfigured(): bool
    {
        $pub = config('services.webpush.vapid.public_key');
        $priv = config('services.webpush.vapid.private_key');
        return !empty($pub) && !empty($priv) && class_exists(WebPush::class);
    }

    /**
     * Kirim Web Push ke seluruh perangkat/browser milik user tertentu.
     * Mengembalikan jumlah pengiriman yang berhasil.
     */
    public static function sendToUser(
        int $userId,
        string $title,
        string $body,
        ?string $url = '/presensi',
        array $extra = []
    ): int {
        if (!self::isConfigured()) {
            return 0;
        }

        $subscriptions = PushSubscription::where('user_id', $userId)->get();
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('services.webpush.vapid.subject', 'mailto:admin@simbim.local'),
                'publicKey' => config('services.webpush.vapid.public_key'),
                'privateKey' => config('services.webpush.vapid.private_key'),
            ],
        ];

        $payload = json_encode(array_merge([
            'title' => $title,
            'body' => $body,
            'url' => $url ?: '/presensi',
            'icon' => '/logo.png',
            'badge' => '/logo.png',
            'timestamp' => now()->timestamp * 1000,
        ], $extra));

        $sentCount = 0;

        try {
            $webPush = new WebPush($auth);
            $webPush->setReuseVAPIDHeaders(true);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'publicKey' => $sub->public_key,
                        'authToken' => $sub->auth_token,
                        'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                if ($report->isSuccess()) {
                    $sentCount++;
                } elseif ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint_hash', hash('sha256', $endpoint))->delete();
                } else {
                    Log::warning("WebPush gagal ke {$endpoint}: " . $report->getReason());
                }
            }
        } catch (\Throwable $e) {
            Log::warning("WebPushService exception untuk user ID {$userId}: " . $e->getMessage());
        }

        return $sentCount;
    }
}
