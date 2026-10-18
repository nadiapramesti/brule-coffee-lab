<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Kelola perlengkapan - BRÛLÉ')]
class ProductIndex extends Component
{
    /** @var array<int,string> tambahan stok (unit) per produk */
    public array $restock = [];

    public ?string $notice = null;

    #[Url(as: 'q')]
    public string $search = '';

    /** '', active, hidden, low */
    #[Url(as: 'tampil')]
    public string $show = '';

    #[Url(as: 'form')]
    public ?string $form = null;

    #[Computed]
    public function products()
    {
        $q = trim($this->search);

        return Product::query()
            ->when($this->show === 'active', fn ($x) => $x->where('is_active', true))
            ->when($this->show === 'hidden', fn ($x) => $x->where('is_active', false))
            ->when($this->show === 'low', fn ($x) => $x->where('stock', '<=', Product::LOW_STOCK))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")))
            ->orderByDesc('is_active')->orderBy('category')->orderBy('name')->get();
    }

    #[Computed]
    public function counts(): array
    {
        return [
            '' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'hidden' => Product::where('is_active', false)->count(),
            'low' => Product::where('stock', '<=', Product::LOW_STOCK)->count(),
        ];
    }

    #[Computed]
    public function editing(): ?Product
    {
        return ($this->form && $this->form !== 'new') ? Product::where('slug', $this->form)->first() : null;
    }

    #[Computed]
    public function formOpen(): bool
    {
        return $this->form === 'new' || $this->editing !== null;
    }

    public function openForm(?string $slug = null): void
    {
        $this->form = $slug ?: 'new';
        unset($this->editing, $this->formOpen);
    }

    #[On('close-product-form')]
    public function closeForm(): void
    {
        $this->form = null;
        unset($this->editing, $this->formOpen);
    }

    #[On('product-saved')]
    public function saved(string $name, bool $created): void
    {
        $this->closeForm();
        unset($this->products, $this->counts);
        $this->notice = $created ? __(':n ditambahkan.', ['n' => $name]) : __(':n disimpan.', ['n' => $name]);
    }

    public function addStock(int $id): void
    {
        $this->validate(["restock.$id" => ['required', 'integer', 'min:1', 'max:100000']], [
            "restock.$id.required" => __('Isi jumlah unit dulu.'),
            "restock.$id.integer" => __('Jumlah harus angka bulat.'),
            "restock.$id.min" => __('Minimal 1 unit.'),
            "restock.$id.max" => __('Terlalu besar, maksimal 100.000 unit.'),
        ]);

        $product = Product::findOrFail($id);
        $product->increment('stock', (int) $this->restock[$id]);
        ActivityLog::record('product.restocked', $product, 'Stok :n ditambah :q unit', ['n' => $product->name, 'q' => (int) $this->restock[$id]]);
        unset($this->restock[$id], $this->products, $this->counts);
        $this->notice = __('Stok ditambahkan.');
    }

    public function toggle(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
        ActivityLog::record('product.toggled', $product, $product->is_active ? 'Produk :n ditampilkan di toko' : 'Produk :n disembunyikan dari toko', ['n' => $product->name]);
        unset($this->products, $this->counts);
        $this->notice = $product->is_active ? __('Produk ditampilkan di toko.') : __('Produk disembunyikan dari toko.');
    }

    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);

        if ($product->orderItems()->exists()) {
            $this->notice = __('Produk ini sudah pernah dipesan, jadi tidak bisa dihapus. Sembunyikan saja.');

            return;
        }

        ActivityLog::record('product.deleted', null, 'Produk :n dihapus', ['n' => $product->name]);
        $product->delete();
        unset($this->products, $this->counts);
        $this->notice = __('Produk dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.product-index');
    }
}
