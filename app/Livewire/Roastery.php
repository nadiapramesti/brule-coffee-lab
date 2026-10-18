<?php

namespace App\Livewire;

use App\Models\Coffee;
use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Antrian roastery - BRÛLÉ')]
class Roastery extends Component
{
    /** @var array<int,string> nomor resi per pesanan */
    public array $tracking = [];

    public const COLUMNS = ['queued', 'roasting', 'grinding', 'packing'];

    /** Stok biji: berapa baris tampil sebelum "Tampilkan semua". */
    public const STOCK_PREVIEW = 8;

    public string $stockSearch = '';

    public bool $stockLowOnly = false;

    public bool $stockAll = false;

    #[Computed]
    public function columns(): array
    {
        $orders = Order::with('items')->whereIn('status', self::COLUMNS)->orderByRaw('COALESCE(paid_at, created_at)')->get()->groupBy('status');

        return collect(self::COLUMNS)->mapWithKeys(fn ($s) => [$s => $orders->get($s, collect())])->all();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'waiting' => Order::where('status', 'pending_payment')->count(),
            'inProduction' => Order::inProduction()->count(),
            'shippedToday' => Order::whereDate('shipped_at', today())->count(),
            'grams' => (int) \App\Models\OrderItem::whereHas('order', fn ($q) => $q->inProduction())->sum('grams'),
        ];
    }

    /** Semua biji aktif, stok paling tipis di atas. */
    #[Computed]
    public function stock()
    {
        return Coffee::active()->with('traceability')->orderBy('stock_green_bean_gram')->orderBy('name')->get();
    }

    #[Computed]
    public function stockSummary(): array
    {
        $all = $this->stock;

        return [
            'total' => $all->count(),
            'low' => $all->filter(fn ($c) => $c->stockState() === 'low')->count(),
            'soldout' => $all->filter(fn ($c) => $c->stockState() === 'soldout')->count(),
            'kg' => round($all->sum('stock_green_bean_gram') / 1000, 1),
            'max' => max(1, (int) $all->max('stock_green_bean_gram')),
        ];
    }

    /** Hasil setelah cari / filter; tanpa filter hanya sebagian sampai "Tampilkan semua". */
    #[Computed]
    public function stockRows(): array
    {
        $q = mb_strtolower(trim($this->stockSearch));
        $rows = $this->stock
            ->when($q !== '', fn ($c) => $c->filter(fn ($b) => str_contains(mb_strtolower($b->name . ' ' . $b->traceability?->process . ' ' . $b->traceability?->region), $q)))
            ->when($this->stockLowOnly, fn ($c) => $c->filter(fn ($b) => $b->stockState() !== 'ok'))
            ->values();

        $filtered = $q !== '' || $this->stockLowOnly;
        $shown = ($filtered || $this->stockAll) ? $rows : $rows->take(self::STOCK_PREVIEW);

        return ['rows' => $shown, 'matched' => $rows->count(), 'hidden' => $rows->count() - $shown->count(), 'filtered' => $filtered];
    }

    public function advance(int $id): void
    {
        Order::findOrFail($id)->advance();
        unset($this->columns, $this->stats);
    }

    public function ship(int $id): void
    {
        $this->validate(["tracking.$id" => ['required', 'string', 'min:6', 'max:40']], [
            "tracking.$id.required" => __('Isi nomor resi dulu.'),
            "tracking.$id.min" => __('Nomor resi terlalu pendek.'),
        ]);

        if (Order::findOrFail($id)->ship($this->tracking[$id])) {
            unset($this->tracking[$id], $this->columns, $this->stats);
        }
    }

    public function render()
    {
        return view('livewire.roastery');
    }
}
