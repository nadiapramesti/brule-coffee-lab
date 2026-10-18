<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Midtrans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransNotificationController extends Controller
{
    /** Webhook dari Midtrans. Diverifikasi lewat signature_key, bukan sesi login. */
    public function __invoke(Request $request): JsonResponse
    {
        if (! Midtrans::enabled()) {
            return response()->json(['ok' => false], 404);
        }

        $p = $request->all();
        if (! Midtrans::validSignature($p)) {
            return response()->json(['ok' => false], 403);
        }

        $order = Order::where('midtrans_order_id', $p['order_id'] ?? '')->first();
        if ($order) {
            Midtrans::apply($order, $p);
        }

        // Selalu 200 untuk notifikasi sah, termasuk uji coba dari dashboard Midtrans.
        return response()->json(['ok' => true]);
    }
}
