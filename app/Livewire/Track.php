<?php

namespace App\Livewire;

use App\Models\Order;
use App\Support\Midtrans;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kromatik')]
#[Title('Lacak pesanan - BRÛLÉ')]
class Track extends Component
{
    public ?string $code = null;
    public string $query = '';
    public ?string $payNotice = null;

    public function mount(?string $code = null): void
    {
        $this->code = $code ? strtoupper(trim($code)) : null;
        $this->query = $this->code ?? '';
    }

    #[Computed]
    public function order(): ?Order
    {
        return $this->code
            ? Order::with('items')->where('order_code', $this->code)->first()
            : null;
    }

    /** Hanya pemilik (atau tim roastery) yang boleh membayar atau membatalkan. */
    #[Computed]
    public function canManage(): bool
    {
        $u = auth()->user();

        return $u && $this->order && ($u->id === $this->order->user_id || $u->isRoaster());
    }

    public function search()
    {
        $q = strtoupper(trim($this->query));

        return $q === '' ? null : $this->redirectRoute('track', ['code' => $q], navigate: true);
    }

    /** Simulasi tanpa gateway. Mati begitu Midtrans dikonfigurasi, supaya pembayaran tidak bisa dilewati. */
    public function pay(): void
    {
        abort_unless($this->canManage, 403);
        abort_if(Midtrans::enabled(), 403);
        $this->order->markPaid();
        unset($this->order);
    }

    /** Buka popup Midtrans Snap di browser. */
    public function startPayment(): void
    {
        abort_unless($this->canManage, 403);
        abort_unless(Midtrans::enabled() && $this->order->status === 'pending_payment', 403);

        try {
            $token = Midtrans::token($this->order);
        } catch (\Throwable $e) {
            report($e);
            $this->payNotice = __('Gateway pembayaran sedang tidak bisa dihubungi. Coba lagi sebentar lagi.')
                . (config('app.debug') ? ' [' . \Illuminate\Support\Str::limit($e->getMessage(), 220) . ']' : '');

            return;
        }

        $this->payNotice = null;
        unset($this->order);
        $this->dispatch('snap-pay', token: $token);
    }

    /** Tanya Midtrans status terbaru (dipanggil setelah popup ditutup atau lewat tombol cek). */
    public function syncPayment(): void
    {
        abort_unless($this->canManage, 403);
        $o = $this->order;
        if (! Midtrans::enabled() || ! $o || ! $o->midtrans_order_id || $o->status !== 'pending_payment') {
            return;
        }

        try {
            $p = Midtrans::status($o->midtrans_order_id);
        } catch (\Throwable $e) {
            report($e);
            $this->payNotice = __('Gateway pembayaran sedang tidak bisa dihubungi. Coba lagi sebentar lagi.')
                . (config('app.debug') ? ' [' . \Illuminate\Support\Str::limit($e->getMessage(), 220) . ']' : '');

            return;
        }

        $this->payNotice = match ($p ? Midtrans::apply($o, $p) : 'pending') {
            'paid' => null,
            'failed' => __('Pembayaran gagal atau kedaluwarsa. Kamu bisa mencoba bayar lagi.'),
            default => __('Pembayaran belum kami terima. Kalau sudah membayar, tunggu beberapa saat lalu cek lagi.'),
        };
        unset($this->order);
    }

    public function cancel(): void
    {
        abort_unless($this->canManage, 403);
        $this->order->cancelAndRestock();
        unset($this->order);
    }

    public function render()
    {
        return view('livewire.track');
    }
}
