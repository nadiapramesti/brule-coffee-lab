<?php

namespace App\Livewire\Admin;

use App\Models\Coffee;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Kelola kopi - KROMATIK')]
class CoffeeIndex extends Component
{
    /** @var array<int,string> tambahan stok (gram) per kopi */
    public array $restock = [];

    public ?string $notice = null;

    #[Computed]
    public function coffees()
    {
        return Coffee::withCount('roastProfiles')->orderByDesc('is_active')->orderBy('name')->get();
    }

    public function addStock(int $id): void
    {
        $this->validate(["restock.$id" => ['required', 'integer', 'min:1', 'max:1000000']], [
            "restock.$id.required" => __('Isi jumlah gram dulu.'),
            "restock.$id.integer" => __('Jumlah harus angka bulat.'),
            "restock.$id.min" => __('Minimal 1 gram.'),
            "restock.$id.max" => __('Terlalu besar, maksimal 1.000.000 gram.'),
        ]);

        Coffee::findOrFail($id)->increment('stock_green_bean_gram', (int) $this->restock[$id]);
        unset($this->restock[$id], $this->coffees);
        $this->notice = __('Stok ditambahkan.');
    }

    public function toggle(int $id): void
    {
        $coffee = Coffee::findOrFail($id);
        $coffee->update(['is_active' => ! $coffee->is_active]);
        unset($this->coffees);
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

        $coffee->delete();
        unset($this->coffees);
        $this->notice = __('Kopi dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.coffee-index');
    }
}
