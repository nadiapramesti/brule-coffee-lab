<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Collection;

/** Struk pembayaran (untuk pelanggan) dan label paket (untuk roaster) dalam bentuk PDF. */
class OrderDocs
{
    private static function rp(int $v): string
    {
        return 'Rp' . number_format($v, 0, ',', '.');
    }

    private static function when(?\DateTimeInterface $d): string
    {
        return $d ? \Carbon\Carbon::instance($d)->timezone('Asia/Jakarta')->translatedFormat('j M Y, H.i') . ' WIB' : '-';
    }

    public static function receipt(Order $o): string
    {
        $o->loadMissing('items');
        $items = $o->groupedItems()->map(fn ($g) => [$g->coffee_name_snapshot, $g->descr ?: '-', $g->qty, self::rp($g->line_total)])->all();

        $cost = [['Subtotal', self::rp((int) $o->subtotal)], ['Ongkir', self::rp((int) $o->shipping_cost)]];
        if ($o->addon_cost > 0) {
            $cost[] = ['Kemasan kado dan kartu', self::rp((int) $o->addon_cost)];
        }
        if ($o->discount > 0) {
            $cost[] = ['Diskon' . ($o->voucher_code ? ' ' . $o->voucher_code : ''), '- ' . self::rp((int) $o->discount)];
        }
        $cost[] = ['TOTAL', self::rp((int) $o->total)];

        return SimplePdf::report('BRÛLÉ - Struk pembayaran', $o->order_code . ' · dibuat ' . self::when(now()), [
            ['heading' => 'Pesanan', 'headers' => ['Keterangan', 'Isi'], 'weights' => [2, 6], 'rows' => [
                ['Kode pesanan', $o->order_code],
                ['Tanggal pesan', self::when($o->created_at)],
                ['Tanggal bayar', self::when($o->paid_at)],
                ['Metode bayar', $o->payment_label],
                ['Status', $o->status_label],
            ]],
            ['heading' => 'Isi pesanan', 'headers' => ['Item', 'Detail', 'Jml', 'Harga'], 'weights' => [4, 5, 1, 2], 'right' => [2, 3], 'rows' => $items],
            ['heading' => 'Rincian biaya', 'headers' => ['Keterangan', 'Jumlah'], 'weights' => [6, 2], 'right' => [1], 'rows' => $cost],
            ['heading' => 'Pengiriman', 'headers' => ['Keterangan', 'Isi'], 'weights' => [2, 6], 'rows' => [
                ['Penerima', $o->recipient_name],
                ['Telepon', $o->recipient_phone],
                ['Alamat', $o->shipping_address],
                ['Nomor resi', $o->tracking_number ?: '-'],
            ]],
        ]);
    }

    /** @param Collection<int, Order> $orders */
    public static function labels(Collection $orders): string
    {
        $sections = [];
        foreach ($orders as $o) {
            $o->loadMissing('items');
            $grams = (int) $o->items->sum('grams');
            $rows = [
                ['Penerima', $o->recipient_name],
                ['Telepon', $o->recipient_phone],
                ['Alamat', $o->shipping_address],
                ['Isi paket', $o->itemSummary()],
                ['Berat kopi', $grams > 0 ? number_format($grams, 0, ',', '.') . ' g' : '-'],
                ['Pembayaran', $o->isCod() ? 'COD - tagih ' . self::rp((int) $o->total) : 'Lunas'],
            ];
            if ($o->hasCoffee()) {
                $rows[] = ['Tgl sangrai', $o->roasted_at ? $o->roasted_at->timezone('Asia/Jakarta')->translatedFormat('j M Y') : '-'];
                $rows[] = ['Terbaik sebelum', $o->bestBefore() ? $o->bestBefore()->translatedFormat('j M Y') : '+' . Order::bestBeforeDays() . ' hari'];
            }
            if ($o->gift_wrap && $o->card_message) {
                $rows[] = ['Kartu ucapan', $o->card_message];
            }
            if ($o->tracking_number) {
                $rows[] = ['Nomor resi', $o->tracking_number];
            }
            $sections[] = ['heading' => 'Paket ' . $o->order_code, 'headers' => ['Keterangan', 'Isi'], 'weights' => [2, 6], 'rows' => $rows];
        }

        return SimplePdf::report('BRÛLÉ - Label paket', $orders->count() . ' paket · dibuat ' . self::when(now()), $sections);
    }
}
