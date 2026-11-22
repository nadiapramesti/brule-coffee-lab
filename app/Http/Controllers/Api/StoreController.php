<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

/** Info toko yang boleh dibaca tanpa login: kontak WhatsApp dan hitungan ongkir keranjang. */
class StoreController extends Controller
{
    /** Nomor WhatsApp toko dari Pengaturan (format 62..., siap dipakai wa.me). */
    public function show()
    {
        return response()->json([
            'name'     => Setting::get('store_name'),
            'whatsapp' => preg_replace('/\D/', '', (string) Setting::get('contact_whatsapp')),
        ]);
    }

    /** Ongkir dan sisa belanja untuk gratis ongkir, dari subtotal keranjang di aplikasi. */
    public function shipping(Request $request)
    {
        $data = $request->validate(['subtotal' => ['required', 'integer', 'min:0', 'max:1000000000']]);
        $subtotal = (int) $data['subtotal'];

        return response()->json([
            'subtotal'           => $subtotal,
            'shipping'           => Order::shippingFor($subtotal),
            'free_shipping_from' => Setting::int('free_shipping_from'),
            'free_shipping_gap'  => Order::freeShippingGap($subtotal),
        ]);
    }
}
