<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\RoastProfile;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const STEPS = ['pending_payment', 'queued', 'roasting', 'grinding', 'packing', 'shipped', 'completed'];

    private const LABELS = [
        'pending_payment' => 'Menunggu pembayaran',
        'queued'          => 'Antri sangrai',
        'roasting'        => 'Disangrai',
        'grinding'        => 'Digiling',
        'packing'         => 'Dikemas',
        'shipped'         => 'Dikirim',
        'completed'       => 'Selesai',
        'cancelled'       => 'Dibatalkan',
    ];

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
        $order = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->where('order_code', strtoupper($code))
            ->firstOrFail();

        return response()->json($this->payload($order));
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

            'items'                => ['required', 'array', 'min:1', 'max:30'],
            'items.*.type'         => ['required', 'in:coffee,gear'],
            'items.*.qty'          => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.profile_id'   => ['required_if:items.*.type,coffee', 'integer'],
            'items.*.grams'        => ['required_if:items.*.type,coffee', 'integer', 'min:100', 'max:5000'],
            'items.*.grind'        => ['required_if:items.*.type,coffee', 'string', 'max:20'],
            'items.*.product_id'   => ['required_if:items.*.type,gear', 'integer'],
            'items.*.option'       => ['nullable', 'string', 'max:60'],
        ], [
            'phone.regex' => 'Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx',
            'address.min' => 'Alamat terlalu pendek. Tulis jalan, nomor, kota, dan kode pos.',
        ]);

        $items = collect($data['items']);
        $profiles = RoastProfile::whereIn('id', $items->where('type', 'coffee')->pluck('profile_id'))->get()->keyBy('id');
        $products = Product::whereIn('id', $items->where('type', 'gear')->pluck('product_id'))->get()->keyBy('id');

        // Satu bungkus = satu baris, sama seperti keranjang di web.
        $lines = [];
        foreach ($items as $i) {
            if ($i['type'] === 'coffee') {
                $profile = $profiles[$i['profile_id']] ?? null;
                if (! $profile) {
                    return response()->json(['message' => 'Kopi tidak ditemukan.'], 422);
                }
                for ($n = 0; $n < $i['qty']; $n++) {
                    $lines[] = ['profile' => $profile, 'grams' => (int) $i['grams'], 'grind' => $i['grind']];
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

    private function payload(Order $o): array
    {
        $step = array_search($o->status, self::STEPS, true);

        return [
            'code'           => $o->order_code,
            'status'         => $o->status,
            'status_label'   => self::LABELS[$o->status] ?? $o->status,
            'step'           => $step === false ? null : $step,
            'steps'          => collect(self::STEPS)->map(fn ($s) => ['key' => $s, 'label' => self::LABELS[$s]])->all(),
            'payment_method' => $o->payment_method,
            'subtotal'       => $o->subtotal,
            'shipping_cost'  => $o->shipping_cost,
            'addon_cost'     => $o->addon_cost,
            'discount'       => $o->discount,
            'voucher_code'   => $o->voucher_code,
            'total'          => $o->total,
            'gift_wrap'      => (bool) $o->gift_wrap,
            'card_message'   => $o->card_message,
            'recipient'      => [
                'name'    => $o->recipient_name,
                'phone'   => $o->recipient_phone,
                'address' => $o->shipping_address,
            ],
            'created_at'     => $o->created_at?->toIso8601String(),
            'items'          => $o->items->map(fn ($it) => [
                'name'           => $it->coffee_name_snapshot,
                'level'          => $it->roast_level_snapshot,
                'grams'          => $it->grams,
                'grind'          => $it->grind,
                'price_per_100g' => $it->price_per_100g_snapshot,
                'line_total'     => $it->line_total,
                'is_gear'        => $it->product_id !== null,
            ])->values(),
        ];
    }
}