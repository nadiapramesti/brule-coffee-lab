<?php

namespace App\Livewire;

use App\Models\Order;
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

    public function pay(): void
    {
        abort_unless($this->canManage, 403);
        $this->order->markPaid();
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
