<?php

namespace App\Livewire\Admin;

use App\Models\Coffee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Kelola kopi - BRÛLÉ')]
class CoffeeIndex extends Component
{
    /** @var array<int,string> tambahan stok (gram) per kopi */
    public array $restock = [];

    public ?string $notice = null;

    #[Url(as: 'q')]
    public string $search = '';

    /** '', active, hidden */
    #[Url(as: 'tampil')]
    public string $show = '';

    /** Modal form: 'new' untuk kopi baru, atau slug kopi yang diubah. */
    #[Url(as: 'form')]
    public ?string $form = null;

    #[Computed]
    public function coffees()
    {
        $q = trim($this->search);

        return Coffee::with(['traceability', 'roastProfiles:id,coffee_id,price_per_100g'])->withCount('roastProfiles')
            ->when($this->show === 'active', fn ($x) => $x->where('is_active', true))
            ->when($this->show === 'hidden', fn ($x) => $x->where('is_active', false))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")
                ->orWhereHas('traceability', fn ($t) => $t->where('region', 'like', "%{$q}%")->orWhere('varietal', 'like', "%{$q}%"))))
            ->orderByDesc('is_active')->orderBy('name')->get();
    }

    #[Computed]
    public function counts(): array
    {
        return ['' => Coffee::count(), 'active' => Coffee::where('is_active', true)->count(), 'hidden' => Coffee::where('is_active', false)->count()];
    }

    #[Computed]
    public function editing(): ?Coffee
    {
        return ($this->form && $this->form !== 'new') ? Coffee::where('slug', $this->form)->first() : null;
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

    #[On('close-coffee-form')]
    public function closeForm(): void
    {
        $this->form = null;
        unset($this->editing, $this->formOpen);
    }

    #[On('coffee-saved')]
    public function saved(string $name, bool $created): void
    {
        $this->closeForm();
        unset($this->coffees, $this->counts);
        $this->notice = $created ? __(':n ditambahkan.', ['n' => $name]) : __(':n disimpan.', ['n' => $name]);
    }

    public function addStock(int $id): void
    {
        $this->validate(["restock.$id" => ['required', 'integer', 'min:1', 'max:1000000']], [
            "restock.$id.required" => __('Isi jumlah gram dulu.'),
            "restock.$id.integer" => __('Jumlah harus angka bulat.'),
            "restock.$id.min" => __('Minimal 1 gram.'),
            "restock.$id.max" => __('Terlalu besar, maksimal 1.000.000 gram.'),
        ]);

        Coffee::findOrFail($id)->moveStock('restock', (int) $this->restock[$id], __('Tambah cepat dari daftar kopi'));
        unset($this->restock[$id], $this->coffees);
        $this->notice = __('Stok ditambahkan.');
    }

    public function toggle(int $id): void
    {
        $coffee = Coffee::findOrFail($id);
        $coffee->update(['is_active' => ! $coffee->is_active]);
        \App\Models\ActivityLog::record('coffee.toggled', $coffee, $coffee->is_active ? 'Kopi :n ditampilkan di toko' : 'Kopi :n disembunyikan dari toko', ['n' => $coffee->name]);
        unset($this->coffees, $this->counts);
        $this->notice = $coffee->is_active ? __('Kopi ditampilkan di toko.') : __('Kopi disembunyikan dari toko.');
    }

    public function delete(int $id): void
    {
        $coffee = Coffee::findOrFail($id);

        // Riwayat pesanan menunjuk ke roast profile; kalau sudah pernah dipesan, cukup disembunyikan.
        if ($coffee->roastProfiles()->whereHas('orderItems')->exists()) {
            $this->notice = __('Kopi ini sudah pernah dipesan, jadi tidak bisa dihapus. Sembunyikan saja.');

            return;
        }

        \App\Models\ActivityLog::record('coffee.deleted', null, 'Kopi :n dihapus', ['n' => $coffee->name]);
        $coffee->delete();
        unset($this->coffees, $this->counts);
        $this->notice = __('Kopi dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.coffee-index');
    }
}
