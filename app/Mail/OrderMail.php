<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email transaksi pesanan. Satu kelas untuk empat kejadian supaya tampilannya seragam:
 * placed (pesanan dibuat), paid (lunas), shipped (dikirim + resi), cancelled.
 */
class OrderMail extends Mailable
{
    public const KINDS = ['placed', 'paid', 'shipped', 'cancelled'];

    public function __construct(public Order $order, public string $kind)
    {
        $this->locale(config('app.locale'));
    }

    public function subjectLine(): string
    {
        $code = $this->order->order_code;

        return match ($this->kind) {
            'placed' => $this->order->isCod()
                ? __('Pesanan COD :code diterima', ['code' => $code])
                : __('Pesanan :code diterima, selesaikan pembayaran', ['code' => $code]),
            'paid' => __('Pembayaran pesanan :code diterima', ['code' => $code]),
            'shipped' => __('Pesanan :code sedang dikirim', ['code' => $code]),
            'cancelled' => __('Pesanan :code dibatalkan', ['code' => $code]),
        };
    }

    public function envelope(): Envelope
    {
        $reply = Setting::get('contact_email');

        return new Envelope(
            from: new Address(config('mail.from.address'), Setting::get('store_name') ?: 'BRÛLÉ'),
            replyTo: $reply ? [new Address($reply)] : [],
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order',
            text: 'emails.order-text',
            with: [
                'o' => $this->order,
                'kind' => $this->kind,
                'items' => $this->order->groupedItems(),
                'url' => route('track', $this->order->order_code),
                'store' => Setting::get('store_name') ?: 'BRÛLÉ',
                'contact' => Setting::get('contact_email'),
                'headline' => $this->headline(),
                'lead' => $this->lead(),
            ],
        );
    }

    private function headline(): string
    {
        return match ($this->kind) {
            'placed' => $this->order->isCod() ? __('Pesananmu kami terima') : __('Satu langkah lagi: bayar pesananmu'),
            'paid' => __('Pembayaran diterima'),
            'shipped' => __('Pesananmu sudah berangkat'),
            'cancelled' => __('Pesananmu dibatalkan'),
        };
    }

    private function lead(): string
    {
        $name = \Illuminate\Support\Str::before($this->order->recipient_name, ' ');

        return match ($this->kind) {
            'placed' => $this->order->isCod()
                ? __('Halo :name, pesanan COD-mu sudah masuk antrian sangrai. Siapkan uang tunai Rp:n saat paket tiba.', ['name' => $name, 'n' => number_format($this->order->total, 0, ',', '.')])
                : __('Halo :name, biji untukmu sudah kami tahan di gudang. Selesaikan pembayaran supaya pesananmu masuk antrian sangrai.', ['name' => $name]),
            'paid' => __('Halo :name, terima kasih. Pesananmu masuk antrian sangrai dan baru disangrai setelah ini, jadi bijinya segar sampai ke tanganmu.', ['name' => $name]),
            'shipped' => __('Halo :name, kopimu baru selesai disangrai dan dikemas, lalu dikirim hari ini. Pantau perjalanannya dengan nomor resi di bawah.', ['name' => $name]),
            'cancelled' => __('Halo :name, pesananmu dibatalkan dan stok biji sudah dikembalikan. Kalau ini bukan kamu yang minta, balas email ini.', ['name' => $name]),
        };
    }
}
