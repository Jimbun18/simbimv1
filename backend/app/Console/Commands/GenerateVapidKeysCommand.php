<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid {--show : Tampilkan key tanpa menulis ke .env}';
    protected $description = 'Generate VAPID public & private keys untuk Web Push Notification';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        if ($this->option('show')) {
            $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
            return self::SUCCESS;
        }

        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            $this->error('File .env tidak ditemukan.');
            return self::FAILURE;
        }

        $env = file_get_contents($envPath);
        $updates = [
            'VAPID_SUBJECT' => 'mailto:admin@simbim.local',
            'VAPID_PUBLIC_KEY' => $keys['publicKey'],
            'VAPID_PRIVATE_KEY' => $keys['privateKey'],
        ];

        foreach ($updates as $key => $val) {
            if (preg_match("/^{$key}=.*/m", $env)) {
                if ($key !== 'VAPID_SUBJECT') {
                    $env = preg_replace("/^{$key}=.*/m", "{$key}={$val}", $env);
                }
            } else {
                $env .= PHP_EOL . "{$key}={$val}";
            }
        }

        file_put_contents($envPath, $env);
        $this->info('VAPID keys berhasil dibuat dan disimpan ke backend/.env.');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);

        return self::SUCCESS;
    }
}
