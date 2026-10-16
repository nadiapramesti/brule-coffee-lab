<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RoastProfile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kromatik')]
#[Title('Checkout - KROMATIK')]
class Checkout extends Component
{
    #[Locked] public int $profileId = 0;
    #[Locked] public int $grams = 200;
    #[Locked] public string $grind = 'whole_bean';

    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public ?string $error = null;

    public function mount(): void
    {
        $this->profileId = (int) request()->query('p');
        $this->grams = in_array((int) request()->query('g'), [200, 500, 1000], true) ? (int) request()->query('g') : 200;
        $g = (string) request()->query('grind');
        $this->grind = array_key_exists($g, OrderItem::GRINDS) ? $g : 'whole_bean';

        abort_unless($this->profile, 404);

        $user = auth()->user();
        $this->name = $user->name;
        $this->phone = (string) ($user->phone ?? '');
    }

    #[Computed]
    public function profile(): ?RoastProfile
    {
        return RoastProfile::with('coffee')->find($this->profileId);
    }

    #[Computed]
    public function subtotal(): int
    {
        return $this->profile->priceFor($this->grams);
    }

    #[Computed]
    public function shipping(): int
    {
        return Order::shippingFor($this->subtotal);
    }

    public function submit()
    {
        $data = $this->validate([
            'name'    => ['required', 'string', 'max:80'],
            'phone'   => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'address' => ['required', 'string', 'min:15', 'max:400'],
        ], [
            'phone.regex'  => __('Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx'),
            'address.min'  => __('Alamat terlalu pendek. Tulis jalan, nomor, kota, dan kode pos.'),
        ]);

        try {
            $order = Order::place(auth()->user(), $this->profile, $this->grams, $this->grind, $data);
        } catch (\RuntimeException $e) {
            $this->error = $e->getMessage();

            return null;
        }

        return $this->redirectRoute('track', ['code' => $order->order_code], navigate: true);
    }

    public function render()
    {
        return view('livewire.checkout');
    }
}
