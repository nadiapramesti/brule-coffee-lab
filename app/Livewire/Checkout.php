<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Cart;
use App\Models\Voucher;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.brule')]
#[Title('Checkout - BRÛLÉ')]
class Checkout extends Component
{
    /** Baris yang dibeli: dari keranjang, atau satu baris langsung bila datang lewat ?p=&g=&grind=. */
    #[Locked] public array $raw = [];
    #[Locked] public bool $direct = false;

    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $payment = 'online';
    public bool $giftWrap = false;
    public string $cardMessage = '';
    public ?int $savedId = null;
    public bool $saveAddress = false;
    public ?string $error = null;
    public string $voucherInput = '';
    #[Locked] public ?string $voucher = null;
    public ?string $voucherError = null;

    public function mount()
    {
        if (auth()->user()?->isStaff()) {
            return $this->redirectRoute(auth()->user()->homeRoute());
        }

        if (request()->query('p')) {
            $g = (int) request()->query('g');
            $grind = (string) request()->query('grind');
            $this->direct = true;
            $this->raw = [[
                'id' => 'direct',
                'profile_id' => (int) request()->query('p'),
                'grams' => in_array($g, Cart::GRAMS, true) ? $g : 200,
                'grind' => array_key_exists($grind, OrderItem::GRINDS) ? $grind : 'whole_bean',
            ]];
        } else {
            $this->raw = Cart::raw();
        }

        if ($this->lines->isEmpty()) {
            return $this->redirectRoute('cart', navigate: true);
        }

        $user = auth()->user();
        $this->name = $user->name;
        $this->phone = (string) ($user->phone ?? '');

        $default = $user->addresses()->first();
        if ($default) {
            $this->useAddress($default->id);
        } else {
            $this->saveAddress = true;
        }

        return null;
    }

    /** Isi form dari alamat tersimpan milik user ini. */
    public function useAddress(?int $id): void
    {
        $this->savedId = $id ?: null;
        if (! $id) {
            return;
        }
        $a = auth()->user()->addresses()->find($id);
        if ($a) {
            $this->name = $a->recipient;
            $this->phone = $a->phone;
            $this->address = $a->address;
            $this->saveAddress = false;
        }
    }

    #[Computed]
    public function lines()
    {
        return Cart::lines($this->raw);
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
    public function discount(): int
    {
        $v = $this->voucher ? Voucher::findByCode($this->voucher) : null;

        return $v && ! $v->problemFor($this->subtotal, auth()->user()) ? $v->discountFor($this->subtotal) : 0;
    }

    /** Biaya add-on (kemasan hadiah + kartu ucapan) dari pilihan saat ini. */
    #[Computed]
    public function addon(): int
    {
        return Order::addonCostFor($this->giftWrap, $this->cardMessage);
    }

    #[Computed]
    public function total(): int
    {
        return $this->subtotal + $this->shipping + $this->addon - $this->discount;
    }

    /** Alasan COD tidak tersedia untuk total saat ini (dimatikan admin / melebihi batas), atau null. */
    #[Computed]
    public function codProblem(): ?string
    {
        return Order::codProblem($this->total);
    }

    /** Pilihan add-on berubah: COD bisa jadi tidak boleh lagi karena total naik melewati batas. */
    public function updatedGiftWrap(): void
    {
        unset($this->addon, $this->total, $this->codProblem);
    }

    public function updatedCardMessage(): void
    {
        $this->cardMessage = mb_substr($this->cardMessage, 0, Order::CARD_MAX);
        unset($this->addon, $this->total, $this->codProblem);
    }

    public function applyVoucher(): void
    {
        $this->voucherError = null;
        $code = Voucher::normalize($this->voucherInput);
        if ($code === '') {
            $this->voucherError = __('Ketik kode voucher dulu.');

            return;
        }
        $v = Voucher::findByCode($code);
        $problem = $v ? $v->problemFor($this->subtotal, auth()->user()) : __('Kode voucher tidak ditemukan.');
        if ($problem) {
            $this->voucher = null;
            $this->voucherError = $problem;

            return;
        }
        $this->voucher = $v->code;
        $this->voucherInput = '';
        unset($this->discount, $this->codProblem);
    }

    public function removeVoucher(): void
    {
        $this->voucher = null;
        $this->voucherError = null;
        unset($this->discount, $this->codProblem);
    }

    public function submit()
    {
        $data = $this->validate([
            'name'    => ['required', 'string', 'max:80'],
            'phone'   => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'address' => ['required', 'string', 'min:15', 'max:400'],
            'payment' => ['required', 'in:online,cod'],
            'giftWrap' => ['boolean'],
            'cardMessage' => ['nullable', 'string', 'max:' . Order::CARD_MAX],
        ], [
            'phone.regex'  => __('Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx'),
            'address.min'  => __('Alamat terlalu pendek. Tulis jalan, nomor, kota, dan kode pos.'),
        ]);

        // Satu bungkus = satu item pesanan, supaya tiap bungkus punya jurnal sangrai sendiri.
        $lines = $this->lines->flatMap(fn ($l) => array_fill(0, $l->qty, $l->kind === 'product'
            ? ['product' => $l->product, 'option' => $l->option]
            : ['profile' => $l->profile, 'grams' => $l->grams, 'grind' => $l->grind, 'blend' => $l->blend, 'ratio' => $l->ratio]))->all();

        try {
            $order = Order::placeMany(
                auth()->user(), $lines, collect($data)->only(['name', 'phone', 'address'])->all(), $this->voucher, $this->payment,
                ['gift_wrap' => $this->giftWrap, 'card_message' => $this->cardMessage],
            );
        } catch (\RuntimeException $e) {
            $this->error = $e->getMessage();
            $this->voucher = null;
            unset($this->discount, $this->codProblem);

            return null;
        }

        if (! $this->direct) {
            Cart::clear();
        }

        $this->rememberAddress($data);

        return $this->redirectRoute('track', ['code' => $order->order_code], navigate: true);
    }

    private function rememberAddress(array $d): void
    {
        $user = auth()->user();
        if (! $this->saveAddress || $user->addresses()->count() >= \App\Models\Address::MAX_PER_USER) {
            return;
        }
        if ($user->addresses()->where('address', $d['address'])->exists()) {
            return;
        }
        $a = $user->addresses()->create([
            'label' => $user->addresses()->count() === 0 ? __('Rumah') : __('Alamat :n', ['n' => $user->addresses()->count() + 1]),
            'recipient' => $d['name'], 'phone' => $d['phone'], 'address' => $d['address'], 'is_default' => false,
        ]);
        if ($user->addresses()->count() === 1) {
            $a->makeDefault();
        }
    }

    public function render()
    {
        if ($this->payment === 'cod' && $this->codProblem) {
            $this->payment = 'online';
        }

        return view('livewire.checkout');
    }
}
