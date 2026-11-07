<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pemantauan galat ringan: kirim ringkasan exception ke webhook (Slack/Discord/dll) bila
 * ERROR_WEBHOOK_URL diisi. Error yang sama tidak dikirim berulang dalam beberapa menit.
 * Kegagalan pengiriman tidak pernah boleh menambah error baru.
 */
class ErrorNotifier
{
    public static function notify(Throwable $e): void
    {
        $url = config('services.error_webhook.url');
        if (! $url) {
            return;
        }

        try {
            $key = 'error-notified:'.md5(get_class($e).$e->getFile().$e->getLine());
            $ttl = now()->addMinutes(max(1, (int) config('services.error_webhook.dedupe_minutes', 10)));
            if (! Cache::add($key, 1, $ttl)) {
                return;
            }

            $where = str_replace(base_path().DIRECTORY_SEPARATOR, '', $e->getFile()).':'.$e->getLine();
            $request = app()->bound('request') ? request() : null;
            $text = sprintf(
                "[%s] %s: %s\n%s%s",
                config('app.name'),
                class_basename($e),
                mb_substr($e->getMessage(), 0, 300),
                $where,
                $request ? "\n".$request->method().' '.$request->path() : '',
            );

            // 'text' untuk Slack, 'content' untuk Discord.
            Http::timeout(3)->post($url, ['text' => $text, 'content' => $text]);
        } catch (Throwable) {
            // sengaja diabaikan
        }
    }
}
