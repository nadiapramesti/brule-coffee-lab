<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderDocs;
use Illuminate\Http\Request;

/** Unduhan PDF: struk untuk pelanggan, label paket untuk roaster dan admin. */
class DocController extends Controller
{
    private function pdf(string $bin, string $name)
    {
        return response($bin, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename=\"$name\""]);
    }

    public function receipt(Request $request, string $code)
    {
        $user = $request->user();
        $order = Order::with('items')->where('order_code', strtoupper($code))
            ->when(! $user->isStaff(), fn ($q) => $q->where('user_id', $user->id))->firstOrFail();

        abort_unless($order->canPrint(), 403, 'Struk bisa diunduh setelah pembayaran lunas.');

        return $this->pdf(OrderDocs::receipt($order), 'struk-' . $order->order_code . '.pdf');
    }

    public function labels(Request $request)
    {
        abort_unless($request->user()->isRoaster(), 403, 'Khusus roaster dan admin.');

        $codes = collect(explode(',', (string) $request->query('codes')))
            ->map(fn ($c) => strtoupper(trim($c)))->filter()->unique()->take(100)->values();

        $orders = Order::with('items')->whereIn('order_code', $codes)->orderByRaw('COALESCE(paid_at, created_at)')->get()
            ->filter->canPrint()->values();

        abort_if($orders->isEmpty(), 404, 'Tidak ada paket yang bisa dicetak (belum lunas atau dibatalkan).');

        $name = $orders->count() === 1 ? 'label-' . $orders->first()->order_code : 'label-paket-' . now()->format('Ymd-His');

        return $this->pdf(OrderDocs::labels($orders), $name . '.pdf');
    }
}
