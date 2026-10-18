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

    /** Batas aman satu aksi massal / satu halaman cetak. */
    public const BULK_MAX = 100;

    public string $search = '';

    public bool $onlyLate = false;

    /** @var array<string,int> tambahan kartu yang dibuka per kolom */
    public array $extra = [];

    /** @var array<int,int|string> id pesanan yang dicentang */
    public array $selected = [];

    public string $notice = '';

    private const SINCE = 'COALESCE(orders.paid_at, orders.created_at)';

    /** Pasang cari + filter terlambat ke query apa pun yang memakai tabel `orders` (Eloquent maupun query builder). */
    private function applyFilter($query)
    {
        $q = trim($this->search);

        return $query->whereIn('orders.status', self::COLUMNS)
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
                $query->where(fn ($w) => $w->where('orders.order_code', 'like', $like)->orWhere('orders.recipient_name', 'like', $like)
                    ->orWhereRaw('EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = orders.id AND oi.coffee_name_snapshot LIKE ?)', [$like]));
            })
            ->when($this->onlyLate, fn ($query) => $query->whereRaw(self::SINCE . ' < ?', [now()->subHours(self::LATE_HOURS)]));
    }

    private function filtered(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->applyFilter(Order::query());
    }

    /** Hitungan per kolom lewat query teragregasi (tidak memuat semua pesanan), lalu hanya kartu yang tampil yang dimuat. */
    #[Computed]
    public function columns(): array
    {
        $cut = now()->subHours(self::LATE_HOURS);

        $counts = $this->filtered()
            ->selectRaw('orders.status as st, COUNT(*) as c, SUM(CASE WHEN ' . self::SINCE . ' < ? THEN 1 ELSE 0 END) as late', [$cut])
            ->groupBy('orders.status')->get()->keyBy('st');

        $grams = $this->applyFilter(\Illuminate\Support\Facades\DB::table('orders')->join('order_items', 'order_items.order_id', '=', 'orders.id'))
            ->selectRaw('orders.status as st, SUM(order_items.grams) as g')
            ->groupBy('orders.status')->pluck('g', 'st');

        return collect(self::COLUMNS)->mapWithKeys(function ($s) use ($counts, $grams) {
            $total = (int) ($counts[$s]->c ?? 0);
            $limit = self::PER_COLUMN + (int) ($this->extra[$s] ?? 0);
            $orders = $total === 0 ? collect() : $this->filtered()->where('orders.status', $s)
                ->with('items')->orderByRaw(self::SINCE)->orderBy('orders.id')->limit($limit)->get();

            return [$s => [
                'orders' => $orders,
                'total' => $total,
                'hidden' => max(0, $total - $orders->count()),
                'grams' => (int) ($grams[$s] ?? 0),
                'late' => (int) ($counts[$s]->late ?? 0),
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

    public function filterBy(string $text): void
    {
        $this->search = $text;
        $this->extra = [];
    }

    public function updatedSearch(): void
    {
        $this->extra = [];
    }

    /** Rencana sangrai: antrian masuk dikelompokkan per biji + level lewat satu query agregat. */
    #[Computed]
    public function batches(): \Illuminate\Support\Collection
    {
        return \App\Models\OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'queued')
            ->whereNotNull('order_items.roast_profile_id')
            ->selectRaw('order_items.coffee_name_snapshot as name, order_items.roast_level_snapshot as lvl, SUM(order_items.grams) as g, COUNT(DISTINCT order_items.order_id) as n')
            ->groupBy('order_items.coffee_name_snapshot', 'order_items.roast_level_snapshot')
            ->orderByDesc('g')->limit(40)->get()
            ->map(fn ($r) => ['name' => $r->name, 'level' => (new \App\Models\OrderItem(['roast_level_snapshot' => $r->lvl]))->level_label, 'grams' => (int) $r->g, 'orders' => (int) $r->n]);
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

    /** Centang semua kartu yang sedang tampil di satu kolom (atau lepas kalau sudah semuanya tercentang). */
    public function selectColumn(string $status): void
    {
        if (! in_array($status, self::COLUMNS, true)) {
            return;
        }
        $ids = $this->columns[$status]['orders']->pluck('id')->map(fn ($i) => (string) $i)->all();
        $current = array_map('strval', $this->selected);
        $this->selected = count(array_diff($ids, $current)) === 0
            ? array_values(array_diff($current, $ids))
            : array_values(array_unique(array_merge($current, $ids)));
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /** Info untuk bar aksi massal: berapa yang dicentang, bisa dilanjutkan, dan bisa dicetak. */
    #[Computed]
    public function selection(): array
    {
        $ids = $this->selectedIds();
        if ($ids === []) {
            return ['count' => 0, 'advance' => 0, 'print' => 0, 'ids' => []];
        }
        $orders = Order::whereIn('id', $ids)->whereIn('status', self::COLUMNS)->get();

        return [
            'count' => $orders->count(),
            'advance' => $orders->filter(fn ($o) => in_array($o->status, ['queued', 'roasting', 'grinding'], true))->count(),
            'print' => $orders->filter->canPrint()->count(),
            'ids' => $orders->pluck('id')->all(),
        ];
    }

    /** @return array<int,int> */
    private function selectedIds(): array
    {
        return array_slice(array_values(array_unique(array_filter(array_map('intval', $this->selected)))), 0, self::BULK_MAX);
    }

    /** Majukan semua pesanan tercentang satu tahap. Kolom Dikemas dilewati karena butuh nomor resi per paket. */
    public function advanceSelected(): void
    {
        $moved = 0;
        foreach (Order::whereIn('id', $this->selectedIds())->orderByRaw(self::SINCE)->get() as $o) {
            if ($o->advance()) {
                $moved++;
            }
        }
        $skipped = count($this->selectedIds()) - $moved;
        $this->notice = $moved > 0
            ? __(':n pesanan dilanjutkan.', ['n' => $moved]) . ($skipped > 0 ? ' ' . __(':n dilewati (tahap Dikemas butuh resi).', ['n' => $skipped]) : '')
            : __('Tidak ada yang bisa dilanjutkan. Tahap Dikemas butuh nomor resi per paket.');
        $this->selected = [];
        unset($this->columns, $this->stats, $this->batches, $this->selection);
    }

    public function advance(int $id): void
    {
        Order::findOrFail($id)->advance();
        unset($this->columns, $this->stats, $this->batches, $this->selection);
    }

    public function ship(int $id): void
    {
        $this->validate(["tracking.$id" => ['required', 'string', 'min:6', 'max:40']], [
            "tracking.$id.required" => __('Isi nomor resi dulu.'),
            "tracking.$id.min" => __('Nomor resi terlalu pendek.'),
        ]);

        if (Order::findOrFail($id)->ship($this->tracking[$id])) {
            unset($this->tracking[$id], $this->columns, $this->stats, $this->batches, $this->selection);
        }
    }

    public function render()
    {
        return view('livewire.roastery');
    }
}
