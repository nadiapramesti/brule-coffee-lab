<?php

namespace App\Http\Controllers;

use App\Models\Order;

/** Invoice untuk pelanggan: dibuka lewat link bertanda tangan (email/WA/halaman lacak), bisa dicetak atau disimpan sebagai PDF dari browser. */
class InvoiceController extends Controller
{
    public function __invoke(Order $order)
    {
        abort_unless($order->canPrint(), 403, __('Invoice tersedia setelah pembayaran lunas.'));

        return view('invoice.show', ['order' => $order->load('items', 'user')]);
    }
}
