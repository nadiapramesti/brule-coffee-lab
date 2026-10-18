<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\SimplePdf;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderExportController extends Controller
{
    /** Ekspor Excel (default) atau PDF, mengikuti filter yang sedang aktif di daftar pesanan. */
    public function __invoke(Request $request): Response
    {
        $orders = Order::with(['user:id,name,email', 'items'])
            ->adminFilter((string) $request->query('tab'), (string) $request->query('q'), $request->query('from') ?: null, $request->query('to') ?: null)
            ->latest()->get();

        $base = 'pesanan-brule-' . now()->format('Ymd-His');

        if ($request->query('format') === 'pdf') {
            $rows = $orders->map(fn ($o) => [
                $o->order_code, $o->created_at->format('d/m/Y H:i'), __(Order::STATUS_LABELS[$o->status] ?? $o->status),
                $o->recipient_name, $this->items($o), 'Rp' . number_format($o->total, 0, ',', '.'), $o->tracking_number ?: '-',
            ])->all();

            $bin = SimplePdf::table(
                'BRÛLÉ - ' . __('Daftar pesanan'),
                now()->translatedFormat('j F Y, H:i') . ' · ' . $orders->count() . ' ' . __('pesanan') . ' · ' . __('Total') . ' Rp' . number_format($orders->where('status', '!=', 'cancelled')->sum('total'), 0, ',', '.'),
                [__('Kode'), __('Tanggal'), __('Status'), __('Penerima'), __('Item'), __('Total'), __('Resi')],
                $rows, [1.3, 1.2, 1.2, 1.6, 4, 1.3, 1.6], [5],
            );

            return response($bin, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename=\"$base.pdf\""]);
        }

        $bin = SimpleXlsx::build(
            __('Pesanan'),
            ['Kode', 'Tanggal', 'Status', 'Pelanggan', 'Email', 'Penerima', 'Telepon', 'Alamat', 'Item', 'Berat (g)', 'Subtotal', 'Ongkir', 'Tambahan', 'Total', 'Resi', 'Dibayar', 'Dikirim'],
            $orders->map(fn ($o) => [
                $o->order_code, $o->created_at->format('Y-m-d H:i'), __(Order::STATUS_LABELS[$o->status] ?? $o->status),
                $o->user?->name, $o->user?->email, $o->recipient_name, $o->recipient_phone, $o->shipping_address,
                $this->items($o), (int) $o->items->sum('grams'), (int) $o->subtotal, (int) $o->shipping_cost, (int) $o->addon_cost, (int) $o->total, $o->tracking_number,
                $o->paid_at?->format('Y-m-d H:i'), $o->shipped_at?->format('Y-m-d H:i'),
            ]),
            [16, 17, 18, 20, 26, 20, 15, 38, 52, 10, 12, 10, 11, 12, 18, 17, 17],
        );

        return response($bin, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => "attachment; filename=\"$base.xlsx\""]);
    }

    private function items(Order $o): string
    {
        return $o->groupedItems()->map(fn ($i) => $i->coffee_name_snapshot.($i->descr ? " ({$i->descr})" : '').($i->qty > 1 ? " ×{$i->qty}" : ''))->implode(' | ');
    }
}
