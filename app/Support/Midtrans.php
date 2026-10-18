<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Integrasi Midtrans Snap tanpa SDK: buat token, cek status, dan terapkan hasilnya ke pesanan.
 * Server key hanya dipakai di sini (server). Yang sampai ke browser hanya client key.
 */
class Midtrans
{
    public static function enabled(): bool
    {
        return filled(config('services.midtrans.server_key')) && filled(config('services.midtrans.client_key'));
    }

    public static function clientKey(): string
    {
        return (string) config('services.midtrans.client_key');
    }

    private static function prod(): bool
    {
        return (bool) config('services.midtrans.is_production');
    }

    public static function snapJsUrl(): string
    {
        return self::prod() ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    private static function snapUrl(): string
    {
        return self::prod() ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private static function statusUrl(string $id): string
    {
        return (self::prod() ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com') . '/v2/' . rawurlencode($id) . '/status';
    }

    private static function http()
    {
        return Http::withBasicAuth((string) config('services.midtrans.server_key'), '')->acceptJson()->timeout(15);
    }

    /** Token Snap untuk pesanan. Dipakai ulang selama masih berlaku supaya tidak membuat transaksi ganda. */
    public static function token(Order $order): string
    {
        if ($order->snap_token) {
            return $order->snap_token;
        }

        $mid = $order->order_code . '-' . Str::lower(Str::random(5));
        $user = $order->user;

        $res = self::http()->post(self::snapUrl(), [
            'transaction_details' => ['order_id' => $mid, 'gross_amount' => (int) $order->total],
            'customer_details' => array_filter([
                'first_name' => $order->recipient_name ?: $user?->name,
                'email' => $user && ! str_ends_with($user->email, '.invalid') ? $user->email : null,
                'phone' => $order->recipient_phone,
            ]),
        ]);

        if (! $res->successful() || ! $res->json('token')) {
            throw new RuntimeException('Midtrans menolak membuat transaksi: ' . ($res->json('error_messages.0') ?? $res->status()));
        }

        $order->forceFill(['snap_token' => $res->json('token'), 'midtrans_order_id' => $mid])->save();

        return $order->snap_token;
    }

    /** Status terbaru dari Midtrans untuk order_id tertentu, atau null bila belum ada transaksi. */
    public static function status(string $midtransOrderId): ?array
    {
        $res = self::http()->get(self::statusUrl($midtransOrderId));
        $code = (string) $res->json('status_code');

        // 404 = pembeli belum memilih metode bayar di popup.
        return $res->successful() && $code !== '404' ? $res->json() : null;
    }

    public static function validSignature(array $p): bool
    {
        $expected = hash('sha512', ($p['order_id'] ?? '') . ($p['status_code'] ?? '') . ($p['gross_amount'] ?? '') . config('services.midtrans.server_key'));

        return hash_equals($expected, (string) ($p['signature_key'] ?? ''));
    }

    /** Terapkan payload status/notifikasi ke pesanan. Mengembalikan 'paid', 'pending', atau 'failed'. */
    public static function apply(Order $order, array $p): string
    {
        // Nominal harus sama dengan total pesanan, apa pun isi notifikasinya.
        if ((int) round((float) ($p['gross_amount'] ?? 0)) !== (int) $order->total) {
            return 'failed';
        }

        $status = $p['transaction_status'] ?? '';
        $fraud = $p['fraud_status'] ?? 'accept';

        if ($p['payment_type'] ?? null) {
            $order->forceFill(['payment_channel' => substr((string) $p['payment_type'], 0, 40)])->save();
        }

        if ($status === 'settlement' || ($status === 'capture' && $fraud === 'accept')) {
            $order->markPaid();

            return 'paid';
        }

        if (in_array($status, ['deny', 'cancel', 'expire', 'failure'], true) && $order->status === 'pending_payment') {
            // Token ini sudah mati; kosongkan supaya pembeli bisa mencoba lagi dengan transaksi baru.
            $order->forceFill(['snap_token' => null, 'midtrans_order_id' => null])->save();

            return 'failed';
        }

        return 'pending';
    }
}
