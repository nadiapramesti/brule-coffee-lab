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

    /** Berapa kartu tampil per kolom sebelum "Tampilkan lagi". */
    public const PER_COLUMN = 8;

    /** Lebih dari ini sejak dibayar = ditandai terlambat (janji toko: berangkat 24 jam). */
    public const LATE_HOURS = 24;

    public string $search = '';

    /** @var array<string,int> tambahan kartu yang dibuka per kolom */
    public array $extra = [];

    #[Computed]
    public function columns(): array
    {
        $q = trim($this->search);
        $orders = Order::with('items')->whereIn('status', self::COLUMNS)
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $query->where(fn ($w) => $w->where('order_code', 'like', $like)->orWhere('recipient_name', 'like', $like)
                    ->orWhereHas('items', fn ($i) => $i->where('coffee_name_snapshot', 'like', $like)));
            })
            ->orderByRaw('COALESCE(paid_at, created_at)')->get()->groupBy('status');

        return collect(self::COLUMNS)->mapWithKeys(function ($s) use ($orders) {
            $all = $orders->get($s, collect());
            $limit = self::PER_COLUMN + (int) ($this->extra[$s] ?? 0);

            return [$s => [
                'orders' => $all->take($limit),
                'total' => $all->count(),
                'hidden' => max(0, $all->count() - $limit),
                'grams' => (int) $all->sum(fn ($o) => $o->items->sum('grams')),
                'late' => $all->filter(fn ($o) => $this->isLate($o))->count(),
            ]];
        })->all();
    }

    public function isLate(Order $o): bool
    {
        return ($o->paid_at ?? $o->created_at)->lt(now()->subHours(self::LATE_HOURS));
    }

    public function showMore(string $status): void
    {
        if (in_array($status, self::COLUMNS, true)) {
            $this->extra[$status] = (int) ($this->extra[$status] ?? 0) + self::PER_COLUMN * 2;
        }
    }

    /** Rencana sangrai: pesanan masuk antrian dikelompokkan per biji + level, supaya disangrai sekali jalan. */
    #[Computed]
    public function batches(): \Illuminate\Support\Collection
    {
        return Order::with('items')->where('status', 'queued')->get()
            ->flatMap(fn ($o) => $o->items->map(fn ($i) => ['o' => $o->id, 'name' => $i->coffee_name_snapshot, 'level' => $i->level_label, 'grams' => (int) $i->grams]))
            ->groupBy(fn ($r) => $r['name'] . '|' . $r['level'])
            ->map(fn ($g) => ['name' => $g[0]['name'], 'level' => $g[0]['level'], 'grams' => $g->sum('grams'), 'orders' => $g->pluck('o')->unique()->count()])
            ->sortByDesc('grams')->values();
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
        unset($this->columns, $this->stats, $this->batches);
    }

    public function ship(int $id): void
    {
        $this->validate(["tracking.$id" => ['required', 'string', 'min:6', 'max:40']], [
            "tracking.$id.required" => __('Isi nomor resi dulu.'),
            "tracking.$id.min" => __('Nomor resi terlalu pendek.'),
        ]);

        if (Order::findOrFail($id)->ship($this->tracking[$id])) {
            unset($this->tracking[$id], $this->columns, $this->stats, $this->batches);
        }
    }

    public function render()
    {
        return view('livewire.roastery');
    }
}
