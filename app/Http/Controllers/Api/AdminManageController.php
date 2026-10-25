<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Coffee;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Models\Voucher;
use App\Support\SalesReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Menu admin lainnya di aplikasi: laporan, pelanggan, pengguna, voucher, ulasan, kopi, produk, log, pengaturan. */
class AdminManageController extends Controller
{
    private function admin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403, 'Khusus admin.');
        auth()->setUser($request->user()); // supaya ActivityLog mencatat pelakunya
    }

    // ---------------------------------------------------------------- Laporan

    public function report(Request $request)
    {
        $this->admin($request);
        $preset = array_key_exists($request->query('preset'), SalesReport::PRESETS) && $request->query('preset') !== 'custom'
            ? $request->query('preset') : '30d';
        $r = SalesReport::forPreset($preset);
        $now = $r->summary();
        $before = $r->previous()->summary();

        return [
            'preset' => $preset,
            'presets' => collect(SalesReport::PRESETS)->except('custom')->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'from' => $r->from->toDateString(),
            'to' => $r->to->toDateString(),
            'summary' => $now,
            'delta' => [
                'revenue' => SalesReport::delta($now['revenue'], $before['revenue']),
                'orders' => SalesReport::delta($now['orders'], $before['orders']),
                'aov' => SalesReport::delta($now['aov'], $before['aov']),
            ],
            'series' => $r->series(),
            'by_coffee' => $r->byCoffee()->take(8)->values(),
            'by_level' => $r->byLevel()->values(),
            'by_product' => $r->byProduct()->take(8)->values(),
            'by_voucher' => $r->byVoucher()->take(8)->values(),
        ];
    }

    // ---------------------------------------------------------------- Pelanggan

    public function customers(Request $request)
    {
        $this->admin($request);
        $tab = (string) $request->query('tab', '');
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'newest');
        $c = fn () => User::where('role', 'customer');

        $page = $c()
            ->withCount(['orders as orders_count' => fn ($x) => $x->where('status', '!=', 'cancelled')])
            ->withSum(['orders as spent' => fn ($x) => $x->whereNotNull('paid_at')->where('status', '!=', 'cancelled')], 'total')
            ->withMax(['orders as last_order_at' => fn ($x) => $x->where('status', '!=', 'cancelled')], 'created_at')
            ->when($tab === 'ordered', fn ($x) => $x->has('orders'))
            ->when($tab === 'never', fn ($x) => $x->doesntHave('orders'))
            ->when($tab === 'repeat', fn ($x) => $x->has('orders', '>=', 2))
            ->when($tab === 'new', fn ($x) => $x->where('created_at', '>=', now()->subDays(30)))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->when($sort === 'spent', fn ($x) => $x->orderByDesc('spent'))
            ->when($sort === 'orders', fn ($x) => $x->orderByDesc('orders_count'))
            ->when($sort === 'last', fn ($x) => $x->orderByDesc('last_order_at'))
            ->orderByDesc('created_at')
            ->paginate(12);

        $total = $c()->count();
        $ordered = $c()->has('orders')->count();

        return [
            'stats' => [
                'total' => $total,
                'ordered' => $ordered,
                'repeat' => $c()->has('orders', '>=', 2)->count(),
                'new' => $c()->where('created_at', '>=', now()->subDays(30))->count(),
                'conversion' => $total ? (int) round($ordered / $total * 100) : 0,
            ],
            'data' => $page->getCollection()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'orders' => (int) $u->orders_count,
                'spent' => (int) $u->spent,
                'last_order_at' => $u->last_order_at,
                'joined_at' => $u->created_at?->toIso8601String(),
                'blocked' => $u->isBlocked(),
            ])->values(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ];
    }

    // ---------------------------------------------------------------- Pengguna

    public function users(Request $request)
    {
        $this->admin($request);
        $role = (string) $request->query('role', '');
        $q = trim((string) $request->query('q', ''));

        $page = User::withCount('orders')
            ->when($role === 'blocked', fn ($w) => $w->whereNotNull('blocked_at'))
            ->when(in_array($role, ['admin', 'roaster', 'customer'], true), fn ($w) => $w->where('role', $role))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")))
            ->orderByRaw("case role when 'admin' then 0 when 'roaster' then 1 else 2 end")->orderBy('name')->paginate(15);

        $counts = User::selectRaw('role, count(*) as n')->groupBy('role')->pluck('n', 'role');

        return [
            'counts' => [
                'all' => (int) $counts->sum(),
                'admin' => (int) ($counts['admin'] ?? 0),
                'roaster' => (int) ($counts['roaster'] ?? 0),
                'customer' => (int) ($counts['customer'] ?? 0),
                'blocked' => User::whereNotNull('blocked_at')->count(),
            ],
            'data' => $page->getCollection()->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role,
                'orders' => (int) $u->orders_count,
                'blocked' => $u->isBlocked(),
                'blocked_reason' => $u->blocked_reason,
                'is_me' => $u->id === $request->user()->id,
            ])->values(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ];
    }

    public function block(Request $request, int $id)
    {
        $this->admin($request);
        $u = User::findOrFail($id);
        abort_if($u->isStaff() || $u->id === $request->user()->id, 422, 'Hanya akun pembeli yang bisa diblokir.');
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);
        $u->block($data['reason'] ?? '');
        ActivityLog::record('user.blocked', $u, 'Pelanggan :n diblokir', ['n' => $u->name]);

        return ['message' => "{$u->name} diblokir."];
    }

    public function unblock(Request $request, int $id)
    {
        $this->admin($request);
        $u = User::findOrFail($id);
        if ($u->isBlocked()) {
            $u->unblock();
            ActivityLog::record('user.unblocked', $u, 'Blokir pelanggan :n dibuka', ['n' => $u->name]);
        }

        return ['message' => "Blokir {$u->name} dibuka."];
    }

    // ---------------------------------------------------------------- Voucher

    private function voucherRow(Voucher $v): array
    {
        return [
            'id' => $v->id,
            'code' => $v->code,
            'type' => $v->type,
            'value' => (int) $v->value,
            'min_subtotal' => (int) $v->min_subtotal,
            'max_discount' => $v->max_discount,
            'quota' => $v->quota,
            'used_count' => (int) $v->used_count,
            'starts_at' => $v->starts_at?->toDateString(),
            'expires_at' => $v->expires_at?->toDateString(),
            'is_active' => (bool) $v->is_active,
            'state' => $v->state(),
            'note' => $v->note,
        ];
    }

    public function vouchers(Request $request)
    {
        $this->admin($request);
        $show = (string) $request->query('tampil', 'all');
        $all = Voucher::latest('id')->get();
        $list = match ($show) {
            'live' => $all->filter(fn ($v) => $v->state() === 'active'),
            'ended' => $all->filter(fn ($v) => $v->state() !== 'active'),
            default => $all,
        };

        return [
            'counts' => [
                'all' => $all->count(),
                'live' => $all->filter(fn ($v) => $v->state() === 'active')->count(),
                'ended' => $all->filter(fn ($v) => $v->state() !== 'active')->count(),
            ],
            'data' => $list->values()->map(fn ($v) => $this->voucherRow($v)),
        ];
    }

    private function voucherRules(?int $ignore, string $type): array
    {
        $max = $type === 'percent' ? 100 : 10000000;

        return [
            'code' => ['required', 'regex:/^[A-Z0-9_-]{3,24}$/', Rule::unique('vouchers', 'code')->ignore($ignore)],
            'type' => ['required', Rule::in(array_keys(Voucher::TYPES))],
            'value' => ['required', 'integer', 'min:1', "max:$max"],
            'min_subtotal' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'max_discount' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'note' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function saveVoucher(Request $request, ?Voucher $existing)
    {
        $request->merge(['code' => Voucher::normalize((string) $request->input('code', ''))]);
        $d = $request->validate($this->voucherRules($existing?->id, (string) $request->input('type', 'percent')), [
            'code.regex' => 'Kode 3-24 karakter: huruf, angka, strip, atau garis bawah.',
            'code.unique' => 'Kode ini sudah dipakai voucher lain.',
            'expires_at.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ]);

        if ($existing && isset($d['quota']) && (int) $d['quota'] < $existing->used_count) {
            return response()->json(['message' => "Kuota tidak boleh di bawah jumlah pemakaian ({$existing->used_count}).", 'errors' => ['quota' => ["Kuota tidak boleh di bawah jumlah pemakaian ({$existing->used_count})."]]], 422);
        }

        $v = $existing ?: new Voucher;
        $v->fill([
            'code' => $d['code'],
            'type' => $d['type'],
            'value' => (int) $d['value'],
            'min_subtotal' => (int) ($d['min_subtotal'] ?? 0),
            'max_discount' => $d['type'] === 'percent' && ! empty($d['max_discount']) ? (int) $d['max_discount'] : null,
            'quota' => ! empty($d['quota']) ? (int) $d['quota'] : null,
            'starts_at' => ! empty($d['starts_at']) ? \Carbon\Carbon::parse($d['starts_at'])->startOfDay() : null,
            'expires_at' => ! empty($d['expires_at']) ? \Carbon\Carbon::parse($d['expires_at'])->endOfDay() : null,
            'note' => $d['note'] ?? null,
            'is_active' => $d['is_active'] ?? true,
        ])->save();
        ActivityLog::record($existing ? 'voucher.updated' : 'voucher.created', $v, $existing ? 'Voucher :c diperbarui' : 'Voucher :c dibuat', ['c' => $v->code]);

        return response()->json($this->voucherRow($v), $existing ? 200 : 201);
    }

    public function voucherStore(Request $request)
    {
        $this->admin($request);

        return $this->saveVoucher($request, null);
    }

    public function voucherUpdate(Request $request, int $id)
    {
        $this->admin($request);

        return $this->saveVoucher($request, Voucher::findOrFail($id));
    }

    public function voucherToggle(Request $request, int $id)
    {
        $this->admin($request);
        $v = Voucher::findOrFail($id);
        $v->update(['is_active' => ! $v->is_active]);
        ActivityLog::record('voucher.toggled', $v, $v->is_active ? 'Voucher :c diaktifkan' : 'Voucher :c dimatikan', ['c' => $v->code]);

        return $this->voucherRow($v->fresh());
    }

    public function voucherDelete(Request $request, int $id)
    {
        $this->admin($request);
        $v = Voucher::findOrFail($id);
        abort_if($v->used_count > 0, 422, 'Voucher yang sudah dipakai tidak dihapus supaya riwayat pesanan utuh. Nonaktifkan saja.');
        ActivityLog::record('voucher.deleted', null, 'Voucher :c dihapus', ['c' => $v->code]);
        $v->delete();

        return ['message' => 'Voucher dihapus.'];
    }

    // ---------------------------------------------------------------- Ulasan

    public function reviews(Request $request)
    {
        $this->admin($request);
        $show = (string) $request->query('tampil', 'all');
        $list = Review::with(['user:id,name,email', 'coffee:id,name'])
            ->when($show === 'visible', fn ($q) => $q->where('is_hidden', false))
            ->when($show === 'hidden', fn ($q) => $q->where('is_hidden', true))
            ->latest('id')->limit(100)->get();

        return [
            'counts' => [
                'all' => Review::count(),
                'visible' => Review::where('is_hidden', false)->count(),
                'hidden' => Review::where('is_hidden', true)->count(),
            ],
            'data' => $list->map(fn ($r) => [
                'id' => $r->id,
                'coffee' => $r->coffee?->name,
                'name' => $r->user?->name,
                'email' => $r->user?->email,
                'rating' => (int) $r->rating,
                'body' => $r->body,
                'is_hidden' => (bool) $r->is_hidden,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values(),
        ];
    }

    public function reviewToggle(Request $request, int $id)
    {
        $this->admin($request);
        $r = Review::with('coffee')->findOrFail($id);
        $r->update(['is_hidden' => ! $r->is_hidden]);
        ActivityLog::record($r->is_hidden ? 'review.hidden' : 'review.shown', $r,
            $r->is_hidden ? 'Ulasan :n disembunyikan' : 'Ulasan :n ditampilkan kembali', ['n' => $r->coffee?->name]);

        return ['id' => $r->id, 'is_hidden' => (bool) $r->is_hidden];
    }

    public function reviewDelete(Request $request, int $id)
    {
        $this->admin($request);
        $r = Review::with('coffee')->findOrFail($id);
        ActivityLog::record('review.deleted', $r, 'Ulasan :n dihapus', ['n' => $r->coffee?->name]);
        $r->delete();

        return ['message' => 'Ulasan dihapus.'];
    }

    // ---------------------------------------------------------------- Kopi dan produk

    public function coffees(Request $request)
    {
        $this->admin($request);
        $q = trim((string) $request->query('q', ''));
        $list = Coffee::with(['traceability', 'roastProfiles:id,coffee_id,price_per_100g'])
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderByDesc('is_active')->orderBy('name')->get();

        return ['data' => $list->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'region' => $c->traceability?->region,
            'is_active' => (bool) $c->is_active,
            'stock_gram' => (int) $c->stock_green_bean_gram,
            'profiles' => $c->roastProfiles->count(),
            'from_price' => (int) ($c->roastProfiles->min('price_per_100g') ?? 0),
        ])->values()];
    }

    public function coffeeToggle(Request $request, int $id)
    {
        $this->admin($request);
        $c = Coffee::findOrFail($id);
        $c->update(['is_active' => ! $c->is_active]);
        ActivityLog::record('coffee.toggled', $c, $c->is_active ? 'Kopi :n ditampilkan di toko' : 'Kopi :n disembunyikan dari toko', ['n' => $c->name]);

        return ['id' => $c->id, 'is_active' => (bool) $c->is_active];
    }

    public function products(Request $request)
    {
        $this->admin($request);
        $q = trim((string) $request->query('q', ''));
        $list = Product::when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderByDesc('is_active')->orderBy('name')->get();

        return ['data' => $list->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->category,
            'price' => (int) $p->price,
            'stock' => (int) $p->stock,
            'is_active' => (bool) $p->is_active,
        ])->values()];
    }

    public function productToggle(Request $request, int $id)
    {
        $this->admin($request);
        $p = Product::findOrFail($id);
        $p->update(['is_active' => ! $p->is_active]);
        ActivityLog::record('product.toggled', $p, $p->is_active ? 'Produk :n ditampilkan di toko' : 'Produk :n disembunyikan dari toko', ['n' => $p->name]);

        return ['id' => $p->id, 'is_active' => (bool) $p->is_active];
    }

    // ---------------------------------------------------------------- Log aktivitas

    public function logs(Request $request)
    {
        $this->admin($request);
        $groups = ['order' => 'Pesanan', 'coffee' => 'Kopi', 'stock' => 'Stok', 'voucher' => 'Voucher', 'user' => 'Pengguna', 'settings' => 'Pengaturan'];
        $g = (string) $request->query('jenis', '');

        $page = ActivityLog::with('user:id,name')
            ->when(array_key_exists($g, $groups), fn ($q) => $q->where('action', 'like', $g . '.%'))
            ->latest('id')->paginate(20);

        return [
            'groups' => collect($groups)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'data' => $page->getCollection()->map(fn ($l) => [
                'id' => $l->id,
                'text' => $l->text(),
                'action' => $l->action,
                'by' => $l->user?->name,
                'created_at' => $l->created_at?->toIso8601String(),
            ])->values(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ];
    }

    // ---------------------------------------------------------------- Pengaturan toko

    private function settingsPayload(): array
    {
        return [
            'store_name' => Setting::get('store_name'),
            'tagline' => Setting::get('store_tagline'),
            'city' => Setting::get('store_city'),
            'address' => Setting::get('store_address'),
            'email' => Setting::get('contact_email'),
            'whatsapp' => '0' . ltrim(preg_replace('/^62/', '', (string) Setting::get('contact_whatsapp')), '0'),
            'instagram' => Setting::get('instagram'),
            'shipping_flat' => Setting::int('shipping_flat'),
            'free_from' => Setting::int('free_shipping_from'),
            'low_stock' => Setting::int('low_stock_gram'),
            'addons_enabled' => (bool) Setting::int('addons_enabled'),
            'gift_wrap_price' => Setting::int('addon_gift_wrap'),
            'card_price' => Setting::int('addon_card'),
            'cod_enabled' => (bool) Setting::int('cod_enabled'),
            'cod_max' => Setting::int('cod_max'),
        ];
    }

    public function settings(Request $request)
    {
        $this->admin($request);

        return $this->settingsPayload();
    }

    public function settingsSave(Request $request)
    {
        $this->admin($request);

        $ig = preg_replace('#^(https?://)?(www\.)?instagram\.com/#i', '', trim((string) $request->input('instagram', '')));
        $wa = preg_replace('/[\s\-().]/', '', (string) $request->input('whatsapp', ''));
        $request->merge([
            'instagram' => trim(ltrim($ig, '@'), '/ '),
            'whatsapp' => preg_match('/^(\+62|62)8/', $wa) ? '0' . preg_replace('/^(\+62|62)/', '', $wa) : $wa,
        ]);

        $d = $request->validate([
            'store_name' => ['required', 'string', 'max:40'],
            'tagline' => ['required', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:80'],
            'whatsapp' => ['required', 'regex:/^08[0-9]{8,12}$/'],
            'instagram' => ['required', 'regex:/^[A-Za-z0-9._]{1,30}$/'],
            'shipping_flat' => ['required', 'integer', 'min:0', 'max:1000000'],
            'free_from' => ['required', 'integer', 'min:0', 'max:100000000'],
            'low_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'addons_enabled' => ['boolean'],
            'gift_wrap_price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'card_price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'cod_enabled' => ['boolean'],
            'cod_max' => ['required', 'integer', 'min:0', 'max:100000000'],
        ], [
            'email.email' => 'Format email belum benar. Contoh: halo@brule.id',
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 0812xxxxxxxx',
            'instagram.regex' => 'Username Instagram hanya huruf, angka, titik, dan garis bawah.',
        ]);

        Setting::put([
            'store_name' => trim($d['store_name']), 'store_tagline' => trim($d['tagline']), 'store_city' => trim($d['city']),
            'store_address' => trim($d['address']), 'contact_email' => trim($d['email']),
            'contact_whatsapp' => '62' . substr($d['whatsapp'], 1), 'instagram' => $d['instagram'],
            'shipping_flat' => (int) $d['shipping_flat'], 'free_shipping_from' => (int) $d['free_from'], 'low_stock_gram' => (int) $d['low_stock'],
            'addons_enabled' => ! empty($d['addons_enabled']) ? 1 : 0, 'addon_gift_wrap' => (int) $d['gift_wrap_price'], 'addon_card' => (int) $d['card_price'],
            'cod_enabled' => ! empty($d['cod_enabled']) ? 1 : 0, 'cod_max' => (int) $d['cod_max'],
        ]);
        ActivityLog::record('settings.updated', null, 'Pengaturan toko diperbarui');

        return $this->settingsPayload();
    }
}
