<?php

namespace App\Livewire\Admin;

use App\Models\OrderItem;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
#[Title('Pelanggan - KROMATIK')]
class Customers extends Component
{
    use WithPagination;

    public const TABS = [
        '' => 'Semua',
        'ordered' => 'Pernah pesan',
        'repeat' => 'Langganan',
        'never' => 'Belum pesan',
        'new' => 'Baru (30 hari)',
    ];

    public const SORTS = [
        'newest' => 'Terbaru bergabung',
        'spent' => 'Total belanja',
        'orders' => 'Jumlah pesanan',
        'last' => 'Terakhir memesan',
    ];

    #[Url] public string $tab = '';
    #[Url(as: 'q')] public string $search = '';
    #[Url] public string $sort = 'newest';
    #[Url(as: 'id')] public ?int $open = null;

    public function updating($name): void
    {
        if (in_array($name, ['tab', 'search', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : '';
        $this->resetPage();
    }

    private function base()
    {
        $paid = fn ($q) => $q->whereNotNull('paid_at')->where('status', '!=', 'cancelled');

        return User::where('role', 'customer')
            ->withCount(['orders as orders_count' => fn ($q) => $q->where('status', '!=', 'cancelled')])
            ->withSum(['orders as spent' => $paid], 'total')
            ->withMax(['orders as last_order_at' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'created_at');
    }

    #[Computed]
    public function customers()
    {
        $q = trim($this->search);

        return $this->base()
            ->when($this->tab === 'ordered', fn ($x) => $x->has('orders'))
            ->when($this->tab === 'never', fn ($x) => $x->doesntHave('orders'))
            ->when($this->tab === 'repeat', fn ($x) => $x->has('orders', '>=', 2))
            ->when($this->tab === 'new', fn ($x) => $x->where('created_at', '>=', now()->subDays(30)))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->when($this->sort === 'spent', fn ($x) => $x->orderByDesc('spent'))
            ->when($this->sort === 'orders', fn ($x) => $x->orderByDesc('orders_count'))
            ->when($this->sort === 'last', fn ($x) => $x->orderByDesc('last_order_at'))
            ->orderByDesc('created_at')
            ->paginate(12);
    }

    #[Computed]
    public function stats(): array
    {
        $customers = User::where('role', 'customer');
        $total = (clone $customers)->count();
        $ordered = (clone $customers)->has('orders')->count();

        return [
            'total' => $total,
            'ordered' => $ordered,
            'repeat' => (clone $customers)->has('orders', '>=', 2)->count(),
            'new' => (clone $customers)->where('created_at', '>=', now()->subDays(30))->count(),
            'conversion' => $total ? round($ordered / $total * 100) : 0,
        ];
    }

    #[Computed]
    public function tabCounts(): array
    {
        $c = fn () => User::where('role', 'customer');

        return [
            '' => $c()->count(),
            'ordered' => $c()->has('orders')->count(),
            'repeat' => $c()->has('orders', '>=', 2)->count(),
            'never' => $c()->doesntHave('orders')->count(),
            'new' => $c()->where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    #[Computed]
    public function current(): ?User
    {
        return $this->open
            ? $this->base()->with(['orders' => fn ($q) => $q->latest()->limit(10)])->find($this->open)
            : null;
    }

    /** Kopi dan level yang paling sering dibeli pelanggan yang sedang dibuka. */
    #[Computed]
    public function favorites(): array
    {
        $c = $this->current;
        if (! $c) {
            return ['coffee' => null, 'level' => null, 'grams' => 0];
        }

        $items = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $c->id)->where('status', '!=', 'cancelled'))->get();

        return [
            'coffee' => $items->groupBy('coffee_name_snapshot')->map->sum('grams')->sortDesc()->keys()->first(),
            'level' => $items->groupBy(fn ($i) => $i->level_label)->map->sum('grams')->sortDesc()->keys()->first(),
            'grams' => (int) $items->sum('grams'),
        ];
    }

    #[Computed]
    public function addresses()
    {
        return $this->current
            ? $this->current->orders()->select('shipping_address')->distinct()->limit(3)->pluck('shipping_address')
            : collect();
    }

    public function show(int $id): void
    {
        $this->open = $id;
        unset($this->current, $this->favorites, $this->addresses);
    }

    public function close(): void
    {
        $this->open = null;
        unset($this->current, $this->favorites, $this->addresses);
    }

    public function render()
    {
        return view('livewire.admin.customers');
    }
}
