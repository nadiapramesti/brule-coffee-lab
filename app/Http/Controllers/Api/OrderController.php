<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RoastProfile;
use App\Support\Midtrans;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json(['data' => $orders->map(fn ($o) => $this->payload($o))]);
    }

    public function show(Request $request, string $code)
    {
        return response()->json($this->payload($this->mine($request, $code)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:80'],
            'phone'        => ['required', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'address'      => ['required', 'string', 'min:15', 'max:400'],
            'payment'      => ['required', 'in:online,cod'],
            'voucher'      => ['nullable', 'string', 'max:24'],
            'gift_wrap'    => ['boolean'],
            'card_message' => ['nullable', 'string', 'max:' . Order::CARD_MAX],

            'items'              => ['required', 'array', 'min:1', 'max:30'],
            'items.*.type'       => ['required', 'in:coffee,gear'],
            'items.*.qty'        => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.profile_id' => ['required_if:items.*.type,coffee', 'integer'],
            'items.*.grams'      => ['required_if:items.*.type,coffee', 'integer', 'in:100,200,250,500,1000'],
            'items.*.grind'      => ['required_if:items.*.type,coffee', Rule::in(array_keys(OrderItem::GRINDS))],
            'items.*.product_id' => ['required_if:items.*.type,gear', 'integer'],
            'items.*.option'     => ['nullable', 'string', 'max:60'],
            // Blend dua biji: profil biji kedua + porsi biji pertama (%)
            'items.*.blend_profile_id' => ['nullable', 'integer'],
            'items.*.ratio'            => ['nullable', 'integer', Rule::in(\App\Support\Blend::RATIOS)],
        ], [
            'phone.regex' => 'Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx',
            'address.min' => 'Alamat terlalu pendek. Tulis jalan, nomor, kota, dan kode pos.',
        ]);

        $items = collect($data['items']);
        $profiles = RoastProfile::whereIn('id', $items->where('type', 'coffee')->pluck('profile_id'))->get()->keyBy('id');
        $blends = RoastProfile::whereIn('id', $items->where('type', 'coffee')->pluck('blend_profile_id')->filter())->get()->keyBy('id');
        $products = Product::whereIn('id', $items->where('type', 'gear')->pluck('product_id'))->get()->keyBy('id');

        // Satu bungkus = satu baris, sama seperti keranjang di web.
        $lines = [];
        foreach ($items as $i) {
            if ($i['type'] === 'coffee') {
                $profile = $profiles[$i['profile_id']] ?? null;
                if (! $profile) {
                    return response()->json(['message' => 'Kopi tidak ditemukan.'], 422);
                }
                $blend = null;
                if (! empty($i['blend_profile_id'])) {
                    $blend = $blends[$i['blend_profile_id']] ?? null;
                    if (! $blend || empty($i['ratio'])) {
                        return response()->json(['message' => 'Data blend tidak lengkap.'], 422);
                    }
                }
                for ($n = 0; $n < $i['qty']; $n++) {
                    $lines[] = ['profile' => $profile, 'grams' => (int) $i['grams'], 'grind' => $i['grind']]
                        + ($blend ? ['blend' => $blend, 'ratio' => (int) $i['ratio']] : []);
                }
            } else {
                $product = $products[$i['product_id']] ?? null;
                if (! $product) {
                    return response()->json(['message' => 'Produk tidak ditemukan.'], 422);
                }
                for ($n = 0; $n < $i['qty']; $n++) {
                    $lines[] = ['product' => $product, 'option' => $i['option'] ?? null];
                }
            }
        }

        try {
            $order = Order::placeMany(
                $request->user(),
                $lines,
                ['name' => $data['name'], 'phone' => $data['phone'], 'address' => $data['address']],
                $data['voucher'] ?? null,
                $data['payment'],
                ['gift_wrap' => $data['gift_wrap'] ?? false, 'card_message' => $data['card_message'] ?? null],
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->payload($order->load('items')), 201);
    }

    /** Minta token + link Snap. Buka redirect_url di WebView Flutter. */
    public function pay(Request $request, string $code)
    {
        $order = $this->mine($request, $code);

        if ($order->payment_method !== 'online' || $order->status !== 'pending_payment') {
            return response()->json(['message' => 'Pesanan ini tidak perlu dibayar online.'], 422);
        }
        if (! Midtrans::enabled()) {
            return response()->json(['message' => 'Gateway pembayaran belum dikonfigurasi.'], 503);
        }

        try {
            $token = Midtrans::token($order);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Gateway pembayaran sedang tidak bisa dihubungi. Coba lagi sebentar lagi.'], 502);
        }

        $host = config('services.midtrans.is_production') ? 'app.midtrans.com' : 'app.sandbox.midtrans.com';

        return response()->json([
            'token'        => $token,
            'redirect_url' => "https://{$host}/snap/v2/vtweb/{$token}",
        ]);
    }

    /** Tanya Midtrans status terbaru (webhook gak bisa nembus ke laptop lokal). */
    public function sync(Request $request, string $code)
    {
        $order = $this->mine($request, $code);

        if (Midtrans::enabled() && $order->midtrans_order_id && $order->status === 'pending_payment') {
            try {
                $p = Midtrans::status($order->midtrans_order_id);
                if ($p) {
                    Midtrans::apply($order, $p);
                }
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'Gateway pembayaran sedang tidak bisa dihubungi. Coba lagi sebentar lagi.'], 502);
            }
        }

        return response()->json($this->payload($order->fresh('items')));
    }

    public function cancel(Request $request, string $code)
    {
        $order = $this->mine($request, $code);

        if (! $order->cancelAndRestock('Dibatalkan pelanggan lewat aplikasi')) {
            return response()->json(['message' => 'Pesanan ini sudah tidak bisa dibatalkan.'], 422);
        }

        return response()->json($this->payload($order->fresh('items')));
    }

    private function mine(Request $request, string $code): Order
    {
        return Order::with('items')
            ->where('user_id', $request->user()->id)
            ->where('order_code', strtoupper($code))
            ->firstOrFail();
    }

    public function payload(Order $o): array
    {
        $flow = $o->flow();
        $step = array_search($o->status, $flow, true);
        $times = ['queued' => $o->paid_at, 'roasting' => $o->roasted_at, 'shipped' => $o->shipped_at];

        return [
            'code'            => $o->order_code,
            'status'          => $o->status,
            'status_label'    => Order::STATUS_LABELS[$o->status] ?? $o->status,
            'progress'        => $o->progressPercent(),
            'step'            => $step === false ? null : $step,
            'steps'           => array_map(fn ($s) => ['key' => $s, 'label' => Order::STATUS_LABELS[$s] ?? $s, 'at' => ($times[$s] ?? null)?->toIso8601String()], $flow),
            'payment_method'  => $o->payment_method,
            'is_paid'         => $o->isPaid(),
            'can_pay'         => $o->payment_method === 'online' && $o->status === 'pending_payment',
            'can_cancel'      => in_array($o->status, ['pending_payment', 'queued'], true),
            'tracking_number' => $o->tracking_number,
            'subtotal'        => $o->subtotal,
            'shipping_cost'   => $o->shipping_cost,
            'addon_cost'      => $o->addon_cost,
            'discount'        => $o->discount,
            'voucher_code'    => $o->voucher_code,
            'total'           => $o->total,
            'gift_wrap'       => (bool) $o->gift_wrap,
            'card_message'    => $o->card_message,
            'recipient'       => [
                'name'    => $o->recipient_name,
                'phone'   => $o->recipient_phone,
                'address' => $o->shipping_address,
            ],
            'created_at'      => $o->created_at?->toIso8601String(),
            'items'           => $o->items->map(fn ($it) => [
                'name'           => $it->coffee_name_snapshot,
                'detail'         => $it->detail(),
                'grams'          => $it->grams,
                'price_per_100g' => $it->price_per_100g_snapshot,
                'line_total'     => $it->line_total,
                'is_gear'        => $it->isProduct(),
            ])->values(),
        ];
    }
}