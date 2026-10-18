<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Laporan penjualan satu rentang tanggal (inklusif). Dipakai halaman Laporan dan ekspor Excel/PDF
 * supaya angkanya pasti sama. Omzet = pesanan yang sudah dibayar dan tidak dibatalkan (nilai `total`).
 */
class SalesReport
{
    public CarbonImmutable $from;
    public CarbonImmutable $to;
    private ?Collection $orders = null;

    public function __construct(CarbonImmutable|string $from, CarbonImmutable|string $to)
    {
        $this->from = CarbonImmutable::parse($from)->startOfDay();
        $this->to = CarbonImmutable::parse($to)->endOfDay();
        if ($this->to->lt($this->from)) {
            [$this->from, $this->to] = [$this->to->startOfDay(), $this->from->endOfDay()];
        }
    }

    public const PRESETS = ['7d' => '7 hari', '30d' => '30 hari', '90d' => '90 hari', 'month' => 'Bulan ini', 'lastmonth' => 'Bulan lalu', 'custom' => 'Kustom'];

    /** Rentang dari preset; 'custom' memakai $from/$to (tanggal tak valid jatuh ke 30 hari terakhir, maks 366 hari). */
    public static function forPreset(string $preset, ?string $from = null, ?string $to = null): self
    {
        $today = CarbonImmutable::today();

        return match ($preset) {
            '7d' => new self($today->subDays(6), $today),
            '90d' => new self($today->subDays(89), $today),
            'month' => new self($today->startOfMonth(), $today),
            'lastmonth' => new self($today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()),
            'custom' => self::custom($from, $to) ?? new self($today->subDays(29), $today),
            default => new self($today->subDays(29), $today),
        };
    }

    private static function custom(?string $from, ?string $to): ?self
    {
        try {
            if (! $from || ! $to) {
                return null;
            }
            $r = new self($from, $to);

            return $r->days() <= 366 ? $r : new self($r->to->subDays(365), $r->to);
        } catch (\Throwable) {
            return null;
        }
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to->startOfDay()) + 1;
    }

    public function previous(): self
    {
        $n = $this->days();

        return new self($this->from->subDays($n), $this->from->subDay());
    }

    /** @return Collection<int, Order> */
    public function orders(): Collection
    {
        return $this->orders ??= Order::with(['user:id,name', 'items'])
            ->whereNotNull('paid_at')->where('status', '!=', 'cancelled')
            ->whereBetween('paid_at', [$this->from, $this->to])->orderBy('paid_at')->get();
    }

    public function summary(): array
    {
        $o = $this->orders();
        $revenue = (int) $o->sum('total');

        return [
            'orders' => $o->count(),
            'revenue' => $revenue,
            'goods' => (int) $o->sum('subtotal'),
            'shipping' => (int) $o->sum('shipping_cost'),
            'addons' => (int) $o->sum('addon_cost'),
            'discount' => (int) $o->sum('discount'),
            'grams' => (int) $o->flatMap->items->sum('grams'),
            'aov' => $o->count() ? (int) round($revenue / $o->count()) : 0,
        ];
    }

    public static function delta(int $now, int $before): ?int
    {
        return $before > 0 ? (int) round(($now - $before) / $before * 100) : null;
    }

    /** Per hari bila rentang <= 62 hari, selain itu per bulan. @return list<array{label:string,key:string,count:int,total:int}> */
    public function series(): array
    {
        $monthly = $this->days() > 62;
        $groups = $this->orders()->groupBy(fn ($o) => $o->paid_at->format($monthly ? 'Y-m' : 'Y-m-d'));
        $out = [];

        for ($c = $monthly ? $this->from->startOfMonth() : $this->from; $c->lte($this->to); $c = $monthly ? $c->addMonth() : $c->addDay()) {
            $key = $c->format($monthly ? 'Y-m' : 'Y-m-d');
            $g = $groups->get($key, collect());
            $out[] = ['label' => $c->translatedFormat($monthly ? 'M Y' : 'D, j M'), 'key' => $key, 'count' => $g->count(), 'total' => (int) $g->sum('total')];
        }

        return $out;
    }

    public function granularity(): string
    {
        return $this->days() > 62 ? 'month' : 'day';
    }

    /** @return Collection<int, array{name:string,orders:int,grams:int,revenue:int}> */
    public function byCoffee(): Collection
    {
        return $this->items()->reject->isProduct()->groupBy('coffee_name_snapshot')->map(fn ($g, $name) => [
            'name' => $name, 'orders' => $g->pluck('order_id')->unique()->count(), 'grams' => (int) $g->sum('grams'), 'revenue' => (int) $g->sum('line_total'),
        ])->sortByDesc('revenue')->values();
    }

    /** @return Collection<int, array{level:string,orders:int,grams:int,revenue:int}> */
    public function byLevel(): Collection
    {
        // Snapshot lama bisa berupa kunci ("medium_light"), yang baru berupa label ("Medium Light"): samakan.
        return $this->items()->reject->isProduct()->groupBy(fn ($i) => ucwords(str_replace('_', ' ', strtolower($i->roast_level_snapshot))))->map(fn ($g, $level) => [
            'level' => $level, 'orders' => $g->pluck('order_id')->unique()->count(), 'grams' => (int) $g->sum('grams'), 'revenue' => (int) $g->sum('line_total'),
        ])->sortByDesc('revenue')->values();
    }

    /** Produk non-kopi; `units` = jumlah barang terjual. @return Collection<int, array{name:string,orders:int,units:int,revenue:int}> */
    public function byProduct(): Collection
    {
        return $this->items()->filter->isProduct()->groupBy('coffee_name_snapshot')->map(fn ($g, $name) => [
            'name' => $name, 'orders' => $g->pluck('order_id')->unique()->count(), 'units' => $g->count(), 'revenue' => (int) $g->sum('line_total'),
        ])->sortByDesc('revenue')->values();
    }

    /** @return Collection<int, array{code:string,uses:int,discount:int}> */
    public function byVoucher(): Collection
    {
        return $this->orders()->filter(fn ($o) => $o->voucher_code)->groupBy('voucher_code')->map(fn ($g, $code) => [
            'code' => $code, 'uses' => $g->count(), 'discount' => (int) $g->sum('discount'),
        ])->sortByDesc('discount')->values();
    }

    /** @return Collection<int, OrderItem> */
    private function items(): Collection
    {
        return $this->orders()->flatMap->items;
    }
}
