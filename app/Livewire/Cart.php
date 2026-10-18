<?php

namespace App\Livewire;

use App\Models\Order;
use App\Support\Cart as CartStore;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.brule')]
#[Title('Keranjang - BRÛLÉ')]
class Cart extends Component
{
    public function mount()
    {
        if (auth()->user()?->isStaff()) {
            return $this->redirectRoute(auth()->user()->homeRoute());
        }
    }

    #[Computed]
    public function lines()
    {
        return CartStore::lines();
    }

    #[Computed]
    public function subtotal(): int
    {
        return (int) $this->lines->sum('price');
    }

    #[Computed]
    public function shipping(): int
    {
        return Order::shippingFor($this->subtotal);
    }

    #[Computed]
    public function problem(): ?string
    {
        return CartStore::stockProblem($this->lines);
    }

    public function remove(string $id): void
    {
        CartStore::remove($id);
        $this->refreshed();
    }

    public function step(string $id, int $delta): void
    {
        $line = collect(CartStore::raw())->firstWhere('id', $id);
        if ($line) {
            CartStore::setQty($id, ((int) ($line['qty'] ?? 1)) + ($delta <=> 0));
            $this->refreshed();
        }
    }

    public function setGrams(string $id, int $grams): void
    {
        CartStore::change($id, $grams, null);
        $this->refreshed();
    }

    public function setGrind(string $id, string $grind): void
    {
        CartStore::change($id, null, $grind);
        $this->refreshed();
    }

    private function refreshed(): void
    {
        unset($this->lines, $this->subtotal, $this->shipping, $this->problem);
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.cart');
    }
}
