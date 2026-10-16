<?php

namespace App\Livewire;

use App\Models\Order;
use App\Support\Cart as CartStore;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kromatik')]
#[Title('Keranjang - KROMATIK')]
class Cart extends Component
{
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
        unset($this->lines, $this->subtotal, $this->shipping, $this->problem);
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.cart');
    }
}
