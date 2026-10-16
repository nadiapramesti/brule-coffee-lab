<?php

namespace App\Livewire;

use App\Models\Coffee;
use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Antrian roastery - KROMATIK')]
class Roastery extends Component
{
    /** @var array<int,string> nomor resi per pesanan */
    public array $tracking = [];

    public const COLUMNS = ['queued', 'roasting', 'grinding', 'packing'];

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

    #[Computed]
    public function stock()
    {
        return Coffee::active()->orderBy('stock_green_bean_gram')->get();
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
