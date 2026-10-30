<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jembatan ke Tripline (dashboard pemantau login terpisah, lihat tripline-intrusion-alert) lewat
 * API ingest-nya -- Tripline cuma "CCTV": tidak ikut memeriksa kredensial BRÛLÉ, cuma dilapori
 * hasil tiap percobaan login supaya dia bisa mendeteksi brute-force/spraying, dan ditanya balik
 * sebelum login diproses supaya BRÛLÉ sendiri yang memutuskan menolak atau tidak.
 *
 * Sengaja gagal-terbuka (fail-open): kalau Tripline mati/tidak terjangkau, login BRÛLÉ tetap
 * jalan normal -- jangan sampai dashboard pemantau yang down malah mengunci semua pelanggan.
 *
 * PENTING buat jalur mobile: kelas ini (dan INGEST_TOKEN-nya) HANYA boleh dipanggil dari sisi
 * server (controller API ini), TIDAK PERNAH dari aplikasi Flutter-nya. Kalau token ini ikut
 * ditaruh di kode/aset aplikasi mobile, siapa pun yang membongkar APK bisa mengambilnya dan
 * mengirim laporan palsu ke Tripline atas nama BRÛLÉ.
 */
class Tripline
{
    public static function enabled(): bool
    {
        return filled(config('services.tripline.base_url')) && filled(config('services.tripline.ingest_token'));
    }

    private static function http()
    {
        return Http::withToken((string) config('services.tripline.ingest_token'))
            ->acceptJson()
            ->timeout(2)
            ->connectTimeout(1);
    }

    /**
     * Tanya status risiko IP ini menurut Tripline, sebelum BRÛLÉ memproses percobaan login.
     * Selalu balikin bentuk yang sama walau Tripline nonaktif/tidak terjangkau -- "blocked" => false,
     * supaya pemanggil tidak perlu peduli apakah integrasinya sedang hidup atau tidak.
     */
    public static function check(string $ip): array
    {
        $clean = ['blocked' => false, 'quarantined' => false];

        if (! self::enabled()) {
            return $clean;
        }

        try {
            $res = self::http()->get(rtrim((string) config('services.tripline.base_url'), '/').'/api/ingest/check', [
                'ip' => $ip,
            ]);

            if (! $res->successful()) {
                return $clean;
            }

            return [
                'blocked' => (bool) $res->json('blocked'),
                'quarantined' => (bool) $res->json('quarantined'),
            ];
        } catch (\Throwable $e) {
            Log::debug('Tripline check gagal dihubungi, lanjut anggap aman: '.$e->getMessage());

            return $clean;
        }
    }

    /**
     * Lapor satu percobaan login ke Tripline supaya masuk analisisnya. Dipanggil sinkron dengan
     * timeout pendek (2 detik) -- bukan lewat queue, biar tidak butuh queue worker tambahan cuma
     * untuk ini. Kalau gagal/timeout, diam-diam diabaikan (login BRÛLÉ tidak boleh ikut gagal
     * gara-gara Tripline-nya bermasalah).
     */
    public static function report(string $ip, ?string $username, bool $success, ?string $reason = null): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            self::http()->asJson()->post(rtrim((string) config('services.tripline.base_url'), '/').'/api/ingest/attempt', array_filter([
                'ip' => $ip,
                'username' => $username,
                'success' => $success,
                'reason' => $reason,
                'userAgent' => substr((string) request()?->userAgent(), 0, 200),
            ], static fn ($v) => $v !== null));
        } catch (\Throwable $e) {
            Log::debug('Tripline report gagal dikirim, diabaikan: '.$e->getMessage());
        }
    }
}
