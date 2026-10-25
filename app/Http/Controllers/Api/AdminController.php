<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coffee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Dashboard, daftar pesanan, dan stok untuk admin. */
class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->admin($request);

        $days = in_array((int) $request->query('days'), [7, 14, 30], true) ? (int) $request->query('days') : 14;
        $start = fn (int $off = 0) => CarbonImmutable::today()->subDays($days * ($off + 1) - 1);
        $paid = fn ($from, $to) => Order::whereNotNull('paid_at')->where('status', '!=', 'cancelled')
            ->where('paid_at', '>=', $from)->where('paid_at', '<', $to);
        $delta = fn (int $now, int $before) => $before > 0 ? round(($now - $before) / $before * 100) : null;

        $tomorrow = CarbonImmutable::tomorrow();
        $cur = $paid($start(), $tomorrow)->get();
        $prev = $paid($start(1), $start())->get();

        $revenue = (int) $cur->sum('total');
        $count = $cur->count();

        $byDay = $cur->groupBy(fn ($o) => $o->paid_at->toDateString());
        $daily = collect(range($days - 1, 0))->map(function ($ago) use ($byDay) {
            $d = CarbonImmutable::today()->subDays($ago);
            $g = $byDay->get($d->toDateString(), collect());

            return ['date' => $d->toDateString(), 'total' => (int) $g->sum('total'), 'count' => $g->count()];
        })->values();

        $ids = $cur->pluck('id');
        $top = OrderItem::whereIn('order_id', $ids)->whereNotNull('roast_profile_id')
            ->selectRaw('coffee_name_snapshot as name, sum(grams) as grams, sum(line_total) as revenue')
            ->groupBy('coffee_name_snapshot')->orderByDesc('grams')->limit(5)->get()
            ->map(fn ($r) => ['name' => $r->name, 'grams' => (int) $r->grams, 'revenue' => (int) $r->revenue]);

        $counts = Order::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $low = Coffee::active()->where('stock_green_bean_gram', '<', Setting::int('low_stock_gram'))
            ->orderBy('stock_green_bean_gram')->get(['id', 'name', 'stock_green_bean_gram']);

        return response()->json([
            'days'   => $days,
            'kpis'   => [
                'revenue'   => ['value' => $revenue, 'delta' => $delta($revenue, (int) $prev->sum('total'))],
                'orders'    => ['value' => $count, 'delta' => $delta($count, $prev->count())],
                'aov'       => ['value' => $count ? (int) round($revenue / $count) : 0, 'delta' => null],
                'customers' => ['value' => User::where('role', 'customer')->where('created_at', '>=', $start())->count(), 'delta' => null],
                'grams'     => (int) OrderItem::whereIn('order_id', $ids)->sum('grams'),
            ],
            'daily'  => $daily,
            'statuses' => collect(Order::STATUS_LABELS)->map(fn ($label, $s) => [
                'status' => $s, 'label' => $label, 'n' => (int) ($counts[$s] ?? 0),
            ])->values(),
            'top_coffees' => $top,
            'attention' => [
                'unpaid'  => (int) ($counts['pending_payment'] ?? 0),
                'queue'   => Order::inProduction()->count(),
                'to_ship' => (int) ($counts['packing'] ?? 0),
                'low'     => $low->count(),
            ],
            'low_stock' => $low->map(fn ($c) => ['name' => $c->name, 'grams' => (int) $c->stock_green_bean_gram])->values(),
        ]);
    }

    public function orders(Request $request)
    {
        $this->admin($request);

        $tab = array_key_exists((string) $request->query('tab'), Order::TABS) ? (string) $request->query('tab') : '';
        $q = trim((string) $request->query('q'));
        $staff = new StaffController;

        $page = Order::with(['user:id,name', 'items'])->adminFilter($tab, $q)->latest()->paginate(15);

        $counts = Order::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $tabs = collect(Order::TABS)->map(fn ($t, $key) => [
            'key'   => (string) $key,
            'label' => $t[0],
            'count' => $t[1] === null ? (int) $counts->sum() : (int) collect($t[1])->sum(fn ($s) => $counts[$s] ?? 0),
        ])->values();

        return response()->json([
            'data'      => $page->getCollection()->map(fn (Order $o) => $staff->card($o))->values(),
            'page'      => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total'     => $page->total(),
            'tabs'      => $tabs,
        ]);
    }

    public function complete(Request $request, string $code)
    {
        $this->admin($request);
        $staff = new StaffController;
        $order = $staff->find($code);

        if (! $order->complete()) {
            return response()->json(['message' => 'Hanya pesanan berstatus Dikirim yang bisa diselesaikan.'], 422);
        }

        return response()->json($staff->detail($order->fresh(['items', 'user']), $request));
    }

    public function cancel(Request $request, string $code)
    {
        $this->admin($request);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:120']]);
        $staff = new StaffController;
        $order = $staff->find($code);

        if (! $order->cancelAndRestock($data['reason'] ?? 'Dibatalkan admin lewat aplikasi')) {
            return response()->json(['message' => 'Pesanan ini sudah tidak bisa dibatalkan.'], 422);
        }

        return response()->json($staff->detail($order->fresh(['items', 'user']), $request));
    }

    public function stock(Request $request)
    {
        $this->admin($request);

        $coffees = Coffee::active()->with('traceability')->orderBy('stock_green_bean_gram')->orderBy('name')->get();
        $max = max(1, (int) $coffees->max('stock_green_bean_gram'));

        return response()->json([
            'summary' => [
                'total'   => $coffees->count(),
                'low'     => $coffees->filter(fn ($c) => $c->stockState() === 'low')->count(),
                'soldout' => $coffees->filter(fn ($c) => $c->stockState() === 'soldout')->count(),
                'kg'      => round($coffees->sum('stock_green_bean_gram') / 1000, 1),
                'max'     => $max,
            ],
            'coffees' => $coffees->map(fn ($c) => [
                'name'    => $c->name,
                'grams'   => (int) $c->stock_green_bean_gram,
                'state'   => $c->stockState(),
                'process' => $c->traceability?->process,
                'region'  => $c->traceability?->region,
            ])->values(),
            'products' => Product::where('is_active', true)->orderBy('stock')->orderBy('name')->get()->map(fn ($p) => [
                'name'     => $p->name,
                'category' => $p->category,
                'stock'    => (int) $p->stock,
            ])->values(),
        ]);
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403, 'Khusus admin.');
    }
}
