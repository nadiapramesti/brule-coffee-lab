<?php

namespace App\Http\Controllers\Roastery;

use App\Http\Controllers\Controller;
use App\Livewire\Roastery;
use App\Models\Order;
use Illuminate\Http\Request;

/** Cetak invoice + label banyak pesanan sekaligus dari antrian roastery (satu pesanan per halaman). */
class LabelController extends Controller
{
    public function __invoke(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($i) => (int) $i)->filter()->unique()->take(Roastery::BULK_MAX)->values();

        $orders = Order::with('items', 'user')->whereIn('id', $ids)->orderByRaw('COALESCE(paid_at, created_at)')->orderBy('id')->get()
            ->filter->canPrint()->values();

        abort_if($orders->isEmpty(), 404, __('Tidak ada pesanan yang bisa dicetak (belum lunas atau dibatalkan).'));

        return view('admin.orders.print', ['orders' => $orders, 'order' => $orders->first()]);
    }
}
