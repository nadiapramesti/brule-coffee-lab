<?php

namespace App\Livewire;

use App\Models\Product;
use App\Support\Cart;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.brule')]
#[Title('Perlengkapan - BRÛLÉ')]
class Gear extends Component
{
    #[Url(as: 'kategori')]
    public string $category = '';

    /** @var array<int,string> varian terpilih per produk */
    public array $choice = [];

    /** Produk yang detailnya sedang dibuka (dialog). */
    #[Url(as: 'produk')]
    public ?string $detail = null;

    public ?int $addedId = null;
    public ?string $error = null;

    #[Computed]
    public function products()
    {
        return Product::active()
            ->when(array_key_exists($this->category, Product::CATEGORIES), fn ($q) => $q->where('category', $this->category))
            ->orderBy('name')->get()
            ->sortBy(fn ($p) => array_search($p->category, array_keys(Product::CATEGORIES), true))->values();
    }

    /** @return array<string,int> jumlah produk aktif per kategori yang ada isinya */
    #[Computed]
    public function categories(): array
    {
        return Product::active()->selectRaw('category, count(*) as n')->groupBy('category')->pluck('n', 'category')->all();
    }

    #[Computed]
    public function detailProduct(): ?Product
    {
        return $this->detail ? Product::active()->where('slug', $this->detail)->first() : null;
    }

    public function openDetail(string $slug): void
    {
        $this->detail = $slug;
        $this->error = null;
        $this->addedId = null;
        unset($this->detailProduct);
    }

    public function closeDetail(): void
    {
        $this->detail = null;
        unset($this->detailProduct);
    }

    public function setCategory(string $key): void
    {
        $this->category = array_key_exists($key, Product::CATEGORIES) ? $key : '';
        unset($this->products);
    }

    public function add(int $id): void
    {
        $this->addedId = null;
        $this->error = null;

        $product = Product::active()->find($id);
        if (! $product) {
            return;
        }
        if ($product->stock < 1) {
            $this->error = __('Stok :n habis.', ['n' => $product->localizedName()]);

            return;
        }
        $option = $this->choice[$id] ?? null;
        if ($product->hasOptions() && ! $product->validOption($option)) {
            $this->error = __('Pilih :l dulu.', ['l' => mb_strtolower($product->option_label ?: __('varian'))]);

            return;
        }
        $inCart = collect(Cart::raw())->where('kind', 'product')->where('product_id', $id)->sum('qty');
        if ($inCart >= $product->stock) {
            $this->error = __('Stok :n tidak cukup untuk jumlah yang kamu pilih.', ['n' => $product->localizedName()]);

            return;
        }
        if (! Cart::addProduct($id, $option)) {
            $this->error = __('Keranjang penuh (maks :n item). Selesaikan checkout dulu.', ['n' => Cart::MAX_LINES]);

            return;
        }

        $this->addedId = $id;
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.gear');
    }
}
