<?php

namespace App\Livewire;

use App\Support\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    #[On('cart-updated')]
    public function refresh(): void
    {
        // render ulang saja
    }

    public function render()
    {
        return view('livewire.cart-badge', ['count' => Cart::count()]);
    }
}
