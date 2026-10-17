<?php

namespace App\Support;

use App\Mail\OrderMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

/**
 * Pintu tunggal pengiriman email pesanan. Kegagalan kirim (SMTP mati, alamat
 * salah) hanya dilaporkan ke log; tidak boleh menggagalkan pesanan atau
 * pembayaran yang sudah berhasil.
 */
class OrderMailer
{
    public static function send(Order $order, string $kind): void
    {
        $email = $order->user?->email;

        if (! $email || ! in_array($kind, OrderMail::KINDS, true)) {
            return;
        }

        try {
            Mail::to($email)->send(new OrderMail($order->loadMissing('items', 'user'), $kind));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
