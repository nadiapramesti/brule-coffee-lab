<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExportController extends Controller
{
    /** CSV mengikuti filter yang sedang aktif di daftar pesanan. */
    public function __invoke(Request $request): StreamedResponse
    {
        $orders = Order::with(['user:id,name,email', 'items'])
            ->adminFilter((string) $request->query('tab'), (string) $request->query('q'), $request->query('from') ?: null, $request->query('to') ?: null)
            ->latest()->cursor();

        $name = 'pesanan-kromatik-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
            fputcsv($out, ['Kode', 'Tanggal', 'Status', 'Pelanggan', 'Email', 'Penerima', 'Telepon', 'Alamat', 'Item', 'Berat (g)', 'Subtotal', 'Ongkir', 'Total', 'Resi', 'Dibayar', 'Dikirim']);

            foreach ($orders as $o) {
                fputcsv($out, array_map([self::class, 'safe'], [
                    $o->order_code, $o->created_at->format('Y-m-d H:i'), __(Order::STATUS_LABELS[$o->status] ?? $o->status),
                    $o->user?->name, $o->user?->email, $o->recipient_name, $o->recipient_phone, $o->shipping_address,
                    $o->items->map(fn ($i) => "{$i->coffee_name_snapshot} ({$i->level_label}, {$i->grind_label}) {$i->grams}g")->implode(' | '),
                    $o->items->sum('grams'), $o->subtotal, $o->shipping_cost, $o->total, $o->tracking_number,
                    $o->paid_at?->format('Y-m-d H:i'), $o->shipped_at?->format('Y-m-d H:i'),
                ]));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Cegah formula injection saat CSV dibuka di Excel/Sheets. */
    public static function safe(mixed $v): mixed
    {
        return is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $v : $v;
    }
}
