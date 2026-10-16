<?php

namespace App\Livewire;

use App\Models\Address;
use App\Models\Order;
use App\Support\Cart;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.kromatik')]
#[Title('Akun saya - KROMATIK')]
class Account extends Component
{
    use WithPagination;

    public const FILTERS = ['semua', 'aktif', 'selesai', 'batal'];

    #[Url(as: 'tab')] public string $tab = 'pesanan';
    #[Url(as: 'status')] public string $filter = 'semua';

    public ?int $editing = null;
    public bool $formOpen = false;
    public string $label = '';
    public string $recipient = '';
    public string $phone = '';
    public string $address = '';
    public ?string $notice = null;

    public function mount(): void
    {
        $this->tab = in_array($this->tab, ['pesanan', 'alamat'], true) ? $this->tab : 'pesanan';
        $this->filter = in_array($this->filter, self::FILTERS, true) ? $this->filter : 'semua';
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders()
    {
        return auth()->user()->orders()->with('items')
            ->when($this->filter === 'aktif', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->when($this->filter === 'selesai', fn ($q) => $q->where('status', 'completed'))
            ->when($this->filter === 'batal', fn ($q) => $q->where('status', 'cancelled'))
            ->latest('id')->paginate(8);
    }

    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses()->get();
    }

    /** Masukkan isi pesanan lama ke keranjang; baris yang racikannya sudah tidak ada dilewati. */
    public function reorder(string $code)
    {
        $order = auth()->user()->orders()->with('items')->where('order_code', $code)->firstOrFail();

        $added = 0;
        $skipped = 0;
        foreach ($order->items as $i) {
            Cart::add((int) $i->roast_profile_id, (int) $i->grams, (string) $i->grind) ? $added++ : $skipped++;
        }

        if ($added === 0) {
            $this->notice = __('Racikan di pesanan ini sudah tidak tersedia atau keranjangmu penuh.');

            return null;
        }

        return $this->redirectRoute('cart', navigate: true);
    }

    // ===== Buku alamat =====

    public function newAddress(): void
    {
        $this->resetForm();
        $this->recipient = auth()->user()->name;
        $this->phone = (string) (auth()->user()->phone ?? '');
        $this->formOpen = true;
    }

    public function editAddress(int $id): void
    {
        $a = auth()->user()->addresses()->findOrFail($id);
        $this->editing = $a->id;
        $this->label = $a->label;
        $this->recipient = $a->recipient;
        $this->phone = $a->phone;
        $this->address = $a->address;
        $this->formOpen = true;
        $this->resetErrorBag();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function saveAddress(): void
    {
        $d = $this->validate([
            'label' => ['required', 'string', 'max:40'],
            'recipient' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'address' => ['required', 'string', 'min:15', 'max:400'],
        ], [
            'phone.regex' => __('Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx'),
            'address.min' => __('Alamat terlalu pendek. Tulis jalan, nomor, kota, dan kode pos.'),
        ]);

        $user = auth()->user();

        if ($this->editing) {
            $user->addresses()->findOrFail($this->editing)->update($d);
        } else {
            if ($user->addresses()->count() >= Address::MAX_PER_USER) {
                $this->addError('label', __('Maksimal :n alamat tersimpan.', ['n' => Address::MAX_PER_USER]));

                return;
            }
            $a = $user->addresses()->create($d + ['is_default' => false]);
            if ($user->addresses()->count() === 1) {
                $a->makeDefault();
            }
        }

        unset($this->addresses);
        $this->resetForm();
    }

    public function setDefault(int $id): void
    {
        auth()->user()->addresses()->findOrFail($id)->makeDefault();
        unset($this->addresses);
    }

    public function deleteAddress(int $id): void
    {
        $user = auth()->user();
        $a = $user->addresses()->findOrFail($id);
        $wasDefault = $a->is_default;
        $a->delete();

        if ($wasDefault) {
            $user->addresses()->first()?->makeDefault();
        }
        unset($this->addresses);
    }

    private function resetForm(): void
    {
        $this->reset(['editing', 'formOpen', 'label', 'recipient', 'phone', 'address']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.account');
    }
}
