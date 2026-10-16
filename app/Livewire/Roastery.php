<?php

namespace App\Livewire;

use App\Models\Coffee;
use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kromatik')]
#[Title('Antrian roastery - KROMATIK')]
class Roastery extends Component
{
    /** @var array<int,string> nomor resi per pesanan */
    public array $tracking = [];

    public const COLUMNS = ['queued', 'roasting', 'grinding', 'packing'];

    #[Computed]
    public function columns(): array
    {
        $orders = Order::with('items')->whereIn('status', self::COLUMNS)->orderBy('paid_at')->get()->groupBy('status');

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
        $order = Order::findOrFail($id);
        $next = $order->nextStatus();

        // packing -> shipped lewat ship() karena butuh nomor resi.
        if (! $next || ! in_array($order->status, ['queued', 'roasting', 'grinding'], true)) {
            return;
        }

        $order->update([
            'status' => $next,
            'roaster_id' => $order->roaster_id ?? auth()->id(),
            'roasted_at' => $next === 'roasting' ? now() : $order->roasted_at,
        ]);
        unset($this->columns, $this->stats);
    }

    public function ship(int $id): void
    {
        $this->validate(["tracking.$id" => ['required', 'string', 'min:6', 'max:40']], [
            "tracking.$id.required" => __('Isi nomor resi dulu.'),
            "tracking.$id.min" => __('Nomor resi terlalu pendek.'),
        ]);

        $order = Order::findOrFail($id);
        if ($order->status !== 'packing') {
            return;
        }

        $order->update(['status' => 'shipped', 'tracking_number' => trim($this->tracking[$id]), 'shipped_at' => now()]);
        unset($this->tracking[$id], $this->columns, $this->stats);
    }

    public function render()
    {
        return view('livewire.roastery');
    }
}
