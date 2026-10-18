<?php

namespace App\Livewire\Admin;

use App\Models\Coffee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
#[Title('Dashboard admin - BRÛLÉ')]
class Dashboard extends Component
{
    use WithPagination;


    #[Url(as: 'hari')]
    public int $days = 14;

    /** '', unpaid, production, shipped, completed, cancelled */
    #[Url]
    public string $tab = '';

    #[Url(as: 'q')]
    public string $search = '';

    public const TABS = Order::TABS;

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : '';
        $this->resetPage();
    }

    #[Computed]
    public function tabCounts(): array
    {
        $counts = Order::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return collect(self::TABS)->map(fn ($t) => $t[1] === null
            ? (int) $counts->sum()
            : (int) collect($t[1])->sum(fn ($s) => $counts[$s] ?? 0))->all();
    }

    #[Computed]
    public function orders()
    {
        return Order::with(['user:id,name', 'items:id,order_id,roast_profile_id,coffee_name_snapshot,roast_level_snapshot,grind,grams,line_total'])
            ->adminFilter($this->tab, $this->search)->latest()->paginate(8);
    }

    public function updatedDays(): void
    {
        if (! in_array($this->days, [7, 14, 30], true)) {
            $this->days = 14;
        }
    }

    private function start(int $offsetPeriods = 0): CarbonImmutable
    {
        return CarbonImmutable::today()->subDays($this->days * ($offsetPeriods + 1) - 1);
    }

    /** Pesanan yang sudah dibayar dan tidak dibatalkan di rentang [from, to). */
    private function paid(CarbonImmutable $from, CarbonImmutable $to)
    {
        return Order::whereNotNull('paid_at')->where('status', '!=', 'cancelled')
            ->where('paid_at', '>=', $from)->where('paid_at', '<', $to);
    }

    #[Computed]
    public function kpis(): array
    {
        $now = CarbonImmutable::tomorrow();
        $cur = $this->paid($this->start(), $now)->get();
        $prev = $this->paid($this->start(1), $this->start())->get();

        $revenue = (int) $cur->sum('total');
        $prevRevenue = (int) $prev->sum('total');
        $count = $cur->count();

        return [
            'revenue' => ['value' => $revenue, 'delta' => $this->delta($revenue, $prevRevenue)],
            'orders' => ['value' => $count, 'delta' => $this->delta($count, $prev->count())],
            'aov' => ['value' => $count ? (int) round($revenue / $count) : 0, 'delta' => null],
            'customers' => [
                'value' => User::where('role', 'customer')->where('created_at', '>=', $this->start())->count(),
                'delta' => null,
            ],
            'grams' => (int) OrderItem::whereIn('order_id', $cur->pluck('id'))->sum('grams'),
        ];
    }

    private function delta(int $now, int $before): ?float
    {
        return $before > 0 ? round(($now - $before) / $before * 100) : null;
    }

    /** @return array<int,array{date:\Carbon\CarbonImmutable,total:int,count:int}> */
    #[Computed]
    public function daily(): array
    {
        $byDay = $this->paid($this->start(), CarbonImmutable::tomorrow())->get(['paid_at', 'total'])
            ->groupBy(fn ($o) => $o->paid_at->toDateString());

        return collect(range($this->days - 1, 0))->map(function ($ago) use ($byDay) {
            $d = CarbonImmutable::today()->subDays($ago);

            $g = $byDay->get($d->toDateString(), collect());

            return ['date' => $d, 'total' => (int) $g->sum('total'), 'count' => $g->count()];
        })->all();
    }

    #[Computed]
    public function statuses(): array
    {
        $counts = Order::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return collect(Order::STATUS_LABELS)->map(fn ($label, $s) => ['status' => $s, 'label' => $label, 'n' => (int) ($counts[$s] ?? 0)])->values()->all();
    }

    #[Computed]
    public function topCoffees()
    {
        $ids = $this->paid($this->start(), CarbonImmutable::tomorrow())->pluck('id');

        return OrderItem::whereIn('order_id', $ids)
            ->selectRaw('coffee_name_snapshot as name, sum(grams) as grams, sum(line_total) as revenue')
            ->groupBy('coffee_name_snapshot')->orderByDesc('grams')->limit(5)->get();
    }

    #[Computed]
    public function levels()
    {
        $ids = $this->paid($this->start(), CarbonImmutable::tomorrow())->pluck('id');

        return OrderItem::whereIn('order_id', $ids)
            ->selectRaw('roast_level_snapshot as level, sum(grams) as grams')
            ->groupBy('roast_level_snapshot')->orderByDesc('grams')->get();
    }

    #[Computed]
    public function lowStock()
    {
        return Coffee::active()->where('stock_green_bean_gram', '<', \App\Models\Setting::int('low_stock_gram'))->orderBy('stock_green_bean_gram')->get();
    }

    #[Computed]
    public function attention(): array
    {
        return [
            'unpaid' => Order::where('status', 'pending_payment')->count(),
            'queue' => Order::inProduction()->count(),
            'toShip' => Order::where('status', 'packing')->count(),
            'low' => $this->lowStock->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
