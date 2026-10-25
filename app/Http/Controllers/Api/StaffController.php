<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

/** Antrian roastery untuk roaster dan admin: lihat antrian, majukan tahap, kirim dengan resi. */
class StaffController extends Controller
{
    public const COLUMNS = ['queued', 'roasting', 'grinding', 'packing'];

    private const PER_COLUMN = 30;

    /** Lebih dari ini sejak dibayar = terlambat (janji toko: berangkat 24 jam). */
    private const LATE_HOURS = 24;

    public function queue(Request $request)
    {
        $this->roaster($request);

        $q = trim((string) $request->query('q'));
        $late = $request->boolean('late');
        $cut = now()->subHours(self::LATE_HOURS);
        $since = 'COALESCE(orders.paid_at, orders.created_at)';

        $base = fn () => Order::query()
            ->whereIn('orders.status', self::COLUMNS)
            ->when($q !== '', function ($x) use ($q) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
                $x->where(fn ($w) => $w->where('orders.order_code', 'like', $like)
                    ->orWhere('orders.recipient_name', 'like', $like)
                    ->orWhereRaw('EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = orders.id AND oi.coffee_name_snapshot LIKE ?)', [$like]));
            })
            ->when($late, fn ($x) => $x->whereRaw($since . ' < ?', [$cut]));

        $columns = [];
        foreach (self::COLUMNS as $status) {
            $total = $base()->where('orders.status', $status)->count();
            $orders = $total === 0 ? collect() : $base()->where('orders.status', $status)
                ->with('items')->orderByRaw($since)->orderBy('orders.id')->limit(self::PER_COLUMN)->get();

            $columns[] = [
                'status' => $status,
                'label'  => Order::STATUS_LABELS[$status],
                'total'  => $total,
                'grams'  => (int) OrderItem::whereHas('order', fn ($o) => $o->where('status', $status))->sum('grams'),
                'orders' => $orders->map(fn (Order $o) => $this->card($o))->values(),
            ];
        }

        $batches = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'queued')
            ->whereNotNull('order_items.roast_profile_id')
            ->selectRaw('order_items.coffee_name_snapshot as name, order_items.roast_level_snapshot as lvl, SUM(order_items.grams) as g, COUNT(DISTINCT order_items.order_id) as n')
            ->groupBy('order_items.coffee_name_snapshot', 'order_items.roast_level_snapshot')
            ->orderByDesc('g')->limit(20)->get()
            ->map(fn ($r) => [
                'name'   => $r->name,
                'level'  => (new OrderItem(['roast_level_snapshot' => $r->lvl]))->level_label,
                'grams'  => (int) $r->g,
                'orders' => (int) $r->n,
            ]);

        return response()->json([
            'stats' => [
                'waiting'       => Order::where('status', 'pending_payment')->count(),
                'in_production' => Order::inProduction()->count(),
                'shipped_today' => Order::whereDate('shipped_at', today())->count(),
                'grams'         => (int) OrderItem::whereHas('order', fn ($o) => $o->inProduction())->sum('grams'),
            ],
            'columns' => $columns,
            'batches' => $batches,
        ]);
    }

    public function show(Request $request, string $code)
    {
        $this->roaster($request);

        return response()->json($this->detail($this->find($code), $request));
    }

    public function advance(Request $request, string $code)
    {
        $this->roaster($request);
        $order = $this->find($code);

        if (! $order->advance()) {
            return response()->json(['message' => 'Pesanan ini tidak bisa dilanjutkan dari tahap sekarang.'], 422);
        }

        return response()->json($this->detail($order->fresh(['items', 'user']), $request));
    }

    /** Majukan banyak pesanan satu tahap. Tahap Dikemas dilewati karena butuh resi per paket. */
    public function advanceBulk(Request $request)
    {
        $this->roaster($request);
        $data = $request->validate([
            'codes'   => ['required', 'array', 'min:1', 'max:100'],
            'codes.*' => ['string', 'max:40'],
        ]);

        $moved = 0;
        $orders = Order::whereIn('order_code', array_map('strtoupper', $data['codes']))
            ->whereIn('status', self::COLUMNS)
            ->orderByRaw('COALESCE(paid_at, created_at)')->get();
        foreach ($orders as $o) {
            if ($o->advance()) {
                $moved++;
            }
        }

        return response()->json(['moved' => $moved, 'skipped' => count($data['codes']) - $moved]);
    }

    public function ship(Request $request, string $code)
    {
        $this->roaster($request);
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'min:6', 'max:40'],
        ], [
            'tracking_number.required' => 'Isi nomor resi dulu.',
            'tracking_number.min'      => 'Nomor resi terlalu pendek.',
        ]);

        $order = $this->find($code);

        if (! $order->ship($data['tracking_number'])) {
            return response()->json(['message' => 'Pesanan hanya bisa dikirim dari tahap Dikemas.'], 422);
        }

        return response()->json($this->detail($order->fresh(['items', 'user']), $request));
    }

    // ---- dipakai juga oleh AdminController ----

    public function find(string $code): Order
    {
        return Order::with(['items', 'user'])->where('order_code', strtoupper($code))->firstOrFail();
    }

    public function roaster(Request $request): void
    {
        abort_unless($request->user()->isRoaster(), 403, 'Khusus roaster dan admin.');
    }

    public function card(Order $o): array
    {
        $since = $o->paid_at ?? $o->created_at;
        $next = $o->nextStatus();

        return [
            'code'            => $o->order_code,
            'status'          => $o->status,
            'status_label'    => Order::STATUS_LABELS[$o->status] ?? $o->status,
            'recipient'       => $o->recipient_name,
            'customer'        => $o->relationLoaded('user') ? $o->user?->name : null,
            'summary'         => $o->itemSummary(),
            'grams'           => (int) $o->items->sum('grams'),
            'total'           => $o->total,
            'is_paid'         => $o->isPaid(),
            'payment_method'  => $o->payment_method,
            'is_late'         => in_array($o->status, self::COLUMNS, true) && $since->lt(now()->subHours(self::LATE_HOURS)),
            'since'           => $since->toIso8601String(),
            'created_at'      => $o->created_at?->toIso8601String(),
            'tracking_number' => $o->tracking_number,
            'can_advance'     => in_array($o->status, ['queued', 'roasting', 'grinding'], true),
            'can_ship'        => $o->status === 'packing',
            'next_label'      => $next ? (Order::STATUS_LABELS[$next] ?? null) : null,
        ];
    }

    public function detail(Order $o, Request $request): array
    {
        $isAdmin = $request->user()->isAdmin();

        return (new OrderController)->payload($o) + $this->card($o) + [
            'customer_info' => [
                'name'  => $o->user?->name,
                'email' => $o->user?->email,
                'phone' => $o->user?->phone,
            ],
            'admin_note'   => $o->admin_note,
            'can_complete' => $isAdmin && $o->status === 'shipped',
            'can_cancel'   => $isAdmin && in_array($o->status, ['pending_payment', 'queued'], true),
        ];
    }
}
