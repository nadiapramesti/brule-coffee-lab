<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SalesReport;
use App\Support\SimplePdf;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportExportController extends Controller
{
    /** Ekspor laporan penjualan (Excel multi-sheet atau PDF) untuk rentang yang sama dengan halaman Laporan. */
    public function __invoke(Request $request): Response
    {
        $r = SalesReport::forPreset((string) $request->query('preset', '30d'), $request->query('from'), $request->query('to'));
        $s = $r->summary();
        $rp = fn ($v) => 'Rp' . number_format($v, 0, ',', '.');
        $period = $r->from->translatedFormat('j M Y') . ' - ' . $r->to->translatedFormat('j M Y');
        $monthly = $r->granularity() === 'month';
        $base = 'laporan-brule-' . $r->from->format('Ymd') . '-' . $r->to->format('Ymd');

        $summary = [
            [__('Jumlah pesanan dibayar'), $s['orders']], [__('Omzet (total dibayar)'), $s['revenue']], [__('Penjualan kopi'), $s['goods']],
            [__('Ongkir'), $s['shipping']], [__('Diskon voucher'), $s['discount']], [__('Rata-rata per pesanan'), $s['aov']], [__('Berat terjual (g)'), $s['grams']],
        ];
        $series = array_map(fn ($x) => [$x['label'], $x['count'], $x['total']], $r->series());
        $coffee = $r->byCoffee()->map(fn ($x) => [$x['name'], $x['orders'], $x['grams'], $x['revenue']])->all();
        $level = $r->byLevel()->map(fn ($x) => [$x['level'], $x['orders'], $x['grams'], $x['revenue']])->all();
        $voucher = $r->byVoucher()->map(fn ($x) => [$x['code'], $x['uses'], $x['discount']])->all();

        if ($request->query('format') === 'pdf') {
            $money = fn (array $rows, array $cols) => array_map(fn ($row) => array_map(fn ($v, $i) => in_array($i, $cols, true) ? $rp($v) : $v, $row, array_keys($row)), $rows);
            $pdf = SimplePdf::report('BRÛLÉ - ' . __('Laporan penjualan'), $period . ' · ' . __('dibuat') . ' ' . now()->translatedFormat('j M Y, H:i'), [
                ['heading' => __('Ringkasan'), 'headers' => [__('Metrik'), __('Nilai')], 'rows' => array_map(fn ($x) => [$x[0], str_contains($x[0], 'Jumlah') || str_contains($x[0], '(g)') ? number_format($x[1], 0, ',', '.') : $rp($x[1])], $summary), 'weights' => [3, 2], 'right' => [1]],
                ['heading' => $monthly ? __('Omzet per bulan') : __('Omzet per hari'), 'headers' => [$monthly ? __('Bulan') : __('Tanggal'), __('Pesanan'), __('Omzet')], 'rows' => $money($series, [2]), 'weights' => [3, 1, 2], 'right' => [1, 2]],
                ['heading' => __('Per kopi'), 'headers' => [__('Kopi'), __('Pesanan'), __('Berat (g)'), __('Penjualan')], 'rows' => $money(array_map(fn ($r) => [$r[0], $r[1], number_format($r[2], 0, ',', '.'), $r[3]], $coffee), [3]), 'weights' => [4, 1, 1.4, 2], 'right' => [1, 2, 3]],
                ['heading' => __('Per level roast'), 'headers' => [__('Level'), __('Pesanan'), __('Berat (g)'), __('Penjualan')], 'rows' => $money(array_map(fn ($r) => [$r[0], $r[1], number_format($r[2], 0, ',', '.'), $r[3]], $level), [3]), 'weights' => [4, 1, 1.4, 2], 'right' => [1, 2, 3]],
                ['heading' => __('Pemakaian voucher'), 'headers' => [__('Kode'), __('Dipakai'), __('Total diskon')], 'rows' => $money($voucher, [2]), 'weights' => [3, 1, 2], 'right' => [1, 2]],
            ]);

            return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename=\"$base.pdf\""]);
        }

        $orders = $r->orders()->map(fn ($o) => [
            $o->order_code, $o->paid_at->format('Y-m-d H:i'), $o->user?->name, $o->voucher_code, (int) $o->subtotal, (int) $o->shipping_cost, (int) $o->discount, (int) $o->total,
            (int) $o->items->sum('grams'),
        ])->all();

        $bin = SimpleXlsx::buildMany([
            ['name' => __('Ringkasan'), 'headers' => [__('Metrik'), __('Nilai')], 'rows' => array_merge([[__('Periode'), $period]], $summary), 'widths' => [32, 26]],
            ['name' => $monthly ? __('Per bulan') : __('Per hari'), 'headers' => [$monthly ? __('Bulan') : __('Tanggal'), __('Pesanan'), __('Omzet')], 'rows' => $series, 'widths' => [20, 12, 16]],
            ['name' => __('Per kopi'), 'headers' => [__('Kopi'), __('Pesanan'), __('Berat (g)'), __('Penjualan')], 'rows' => $coffee, 'widths' => [36, 12, 14, 16]],
            ['name' => __('Per level roast'), 'headers' => [__('Level'), __('Pesanan'), __('Berat (g)'), __('Penjualan')], 'rows' => $level, 'widths' => [24, 12, 14, 16]],
            ['name' => __('Voucher'), 'headers' => [__('Kode'), __('Dipakai'), __('Total diskon')], 'rows' => $voucher, 'widths' => [24, 12, 16]],
            ['name' => __('Pesanan'), 'headers' => [__('Kode'), __('Dibayar'), __('Pelanggan'), __('Voucher'), __('Kopi'), __('Ongkir'), __('Diskon'), __('Total'), __('Berat (g)')], 'rows' => $orders, 'widths' => [22, 17, 22, 16, 14, 12, 12, 14, 12]],
        ]);

        return response($bin, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => "attachment; filename=\"$base.xlsx\""]);
    }
}
