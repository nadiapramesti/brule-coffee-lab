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

    public function reportExport(Request $request)
    {
        $this->admin($request);
        abort_unless(in_array($request->query('format'), ['pdf', 'xlsx'], true), 422, 'Format harus pdf atau xlsx.');

        return (new \App\Http\Controllers\Admin\ReportExportController)($request);
    }

    /** Ekspor daftar pesanan (Excel atau PDF) dengan filter yang sama seperti daftar di aplikasi. */
    public function orderExport(Request $request)
    {
        $this->admin($request);
        abort_unless(in_array($request->query('format'), ['pdf', 'xlsx'], true), 422, 'Format harus pdf atau xlsx.');

        return (new \App\Http\Controllers\Admin\OrderExportController)($request);
    }

    // ---------------------------------------------------------------- Stok keluar masuk

    public function stockHistory(Request $request)
    {
        $this->admin($request);
        $kopi = (int) $request->query('kopi', 0);
        $jenis = (string) $request->query('jenis', '');

        $page = \App\Models\StockMovement::with(['coffee:id,name', 'user:id,name', 'order:id,order_code'])
            ->when($kopi > 0, fn ($q) => $q->where('coffee_id', $kopi))
            ->when(array_key_exists($jenis, \App\Models\StockMovement::TYPES), fn ($q) => $q->where('type', $jenis))
            ->latest('id')->paginate(20);

        return [
            'types' => collect(\App\Models\StockMovement::TYPES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'coffees' => Coffee::orderBy('name')->get(['id', 'name']),
            'data' => $page->getCollection()->map(fn ($m) => [
                'id' => $m->id,
                'coffee' => $m->coffee?->name,
                'type' => $m->type,
                'type_label' => \App\Models\StockMovement::TYPES[$m->type] ?? $m->type,
                'grams' => (int) $m->grams,
                'balance_after' => (int) $m->balance_after,
                'reason' => $m->reason,
                'by' => $m->user?->name,
                'order_code' => $m->order?->order_code,
                'created_at' => $m->created_at?->toIso8601String(),
            ])->values(),
            'page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ];
    }

    /** mode: restock (masuk), waste (susut/rusak, keluar), count (koreksi hitung ke angka sebenarnya). */
    public function stockAdjustCoffee(Request $request, int $id)
    {
        $this->admin($request);
        $coffee = Coffee::findOrFail($id);
        $mode = (string) $request->input('mode', 'restock');
        abort_unless(in_array($mode, ['restock', 'waste', 'count'], true), 422, 'Mode tidak dikenal.');

        $min = $mode === 'count' ? 0 : 1;
        $d = $request->validate([
            'amount' => ['required', 'integer', "min:$min", 'max:1000000'],
            'reason' => [$mode === 'waste' ? 'required' : 'nullable', 'string', 'max:120'],
        ], [
            'amount.required' => 'Isi jumlah gram dulu.',
            'amount.integer' => 'Jumlah harus angka bulat.',
            'amount.min' => 'Jumlah terlalu kecil.',
            'amount.max' => 'Terlalu besar, maksimal 1.000.000 gram.',
            'reason.required' => 'Alasan wajib diisi untuk pengurangan stok.',
        ]);

        $grams = (int) $d['amount'];
        $reason = trim((string) ($d['reason'] ?? '')) ?: null;

        if ($mode === 'waste' && $grams > $coffee->stock_green_bean_gram) {
            return response()->json(['message' => 'Melebihi stok yang ada (' . number_format($coffee->stock_green_bean_gram) . ' g).'], 422);
        }

        match ($mode) {
            'restock' => $coffee->moveStock('restock', $grams, $reason),
            'waste' => $coffee->moveStock('waste', -$grams, $reason),
            default => $grams === $coffee->stock_green_bean_gram ? null : $coffee->moveStock('adjustment', $grams - $coffee->stock_green_bean_gram, $reason),
        };

        ActivityLog::record('stock.adjusted', $coffee, match ($mode) { 'restock' => 'Restock :n: +:g g', 'waste' => 'Stok :n dikurangi (susut): -:g g', default => 'Stok :n dikoreksi menjadi :g g' }, ['n' => $coffee->name, 'g' => number_format($grams, 0, ',', '.')]);

        return ['id' => $coffee->id, 'grams' => (int) $coffee->fresh()->stock_green_bean_gram];
    }

    public function stockAddProduct(Request $request, int $id)
    {
        $this->admin($request);
        $d = $request->validate(['amount' => ['required', 'integer', 'min:1', 'max:100000']], [
            'amount.required' => 'Isi jumlah unit dulu.',
            'amount.min' => 'Minimal 1 unit.',
            'amount.max' => 'Terlalu besar, maksimal 100.000 unit.',
        ]);
        $p = Product::findOrFail($id);
        $p->increment('stock', (int) $d['amount']);
        ActivityLog::record('product.restocked', $p, 'Stok :n ditambah :q unit', ['n' => $p->name, 'q' => (int) $d['amount']]);

        return ['id' => $p->id, 'stock' => (int) $p->fresh()->stock];
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

    private const ROLE_NAMES = ['admin' => 'Admin', 'roaster' => 'Roaster', 'customer' => 'Customer'];

    public function userStore(Request $request)
    {
        $this->admin($request);
        $d = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'roaster'])],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar. Contoh: roaster@brule.id',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Kata sandi sementara wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $u = new User(['name' => trim($d['name']), 'email' => strtolower(trim($d['email'])), 'password' => $d['password']]);
        $u->forceFill(['role' => $d['role'], 'email_verified_at' => now()])->save();
        ActivityLog::record('user.created', $u, 'Akun staf :n dibuat sebagai :r', ['n' => $u->name, 'r' => self::ROLE_NAMES[$d['role']]]);

        return response()->json(['message' => "Akun {$u->name} dibuat. Berikan kata sandi sementara ke orangnya dan minta diganti."], 201);
    }

    public function userUpdate(Request $request, int $id)
    {
        $this->admin($request);
        $u = User::findOrFail($id);
        $role = (string) $request->input('role', $u->role);
        abort_unless(array_key_exists($role, self::ROLE_NAMES), 422, 'Role tidak dikenal.');

        $roleChanged = $u->role !== $role;
        abort_if($roleChanged && $u->id === $request->user()->id, 422, 'Kamu tidak bisa mengubah role akunmu sendiri.');
        abort_if($roleChanged && $u->role === 'admin' && User::where('role', 'admin')->count() <= 1, 422, 'Harus ada minimal satu admin.');

        // Data diri hanya bisa diubah untuk akun staf; akun pembeli dikelola pembelinya sendiri.
        $fields = [];
        if ($u->isStaff()) {
            $d = $request->validate([
                'name' => ['required', 'string', 'max:80'],
                'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($u->id)],
                'phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
                'password' => ['nullable', 'string', 'min:8', 'max:72'],
            ], [
                'name.required' => 'Nama wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email belum benar. Contoh: roaster@brule.id',
                'email.unique' => 'Email ini sudah terdaftar.',
                'phone.regex' => 'Nomor telepon tidak valid. Contoh: 0812xxxxxxxx',
                'password.min' => 'Kata sandi minimal 8 karakter.',
            ]);
            $fields = [
                'name' => trim($d['name']),
                'email' => strtolower(trim($d['email'])),
                'phone' => filled($d['phone'] ?? null) ? $d['phone'] : null,
            ];
            if (filled($d['password'] ?? null)) {
                $fields['password'] = $d['password'];
            }
        }

        $old = $u->role;
        $u->fill($fields)->forceFill(['role' => $role])->save();
        if (isset($fields['password'])) {
            $u->revokeSessions();
        }

        if ($roleChanged) {
            ActivityLog::record('user.role_changed', $u, 'Role :n diubah dari :a ke :b', ['n' => $u->name, 'a' => self::ROLE_NAMES[$old], 'b' => self::ROLE_NAMES[$role]]);
        } else {
            ActivityLog::record('user.updated', $u, 'Data akun :n diperbarui', ['n' => $u->name]);
        }

        return ['message' => "Data {$u->name} diperbarui."];
    }

    public function userDelete(Request $request, int $id)
    {
        $this->admin($request);
        $u = User::findOrFail($id);
        abort_unless($u->isStaff(), 422, 'Hanya akun staf yang bisa dihapus dari sini.');
        abort_if($u->id === $request->user()->id, 422, 'Kamu tidak bisa menghapus akunmu sendiri.');
        abort_if($u->role === 'admin' && User::where('role', 'admin')->count() <= 1, 422, 'Harus ada minimal satu admin.');

        $name = $u->name;
        $u->deleteStaff();
        ActivityLog::record('user.deleted', null, 'Akun staf :n dihapus', ['n' => $name]);

        return ['message' => "Akun {$name} dihapus."];
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

    private const COFFEE_FLAVORS = ['floral', 'citrus', 'berry', 'chocolate', 'nutty', 'spice'];
    private const COFFEE_PROCESSES = ['Washed', 'Honey', 'Natural', 'Anaerobic', 'Wet-Hulled', 'Carbonic Maceration'];

    private function coffeePayload(Coffee $c): array
    {
        $c->loadMissing('traceability', 'roastProfiles');
        $t = $c->traceability;
        $profiles = [];
        foreach ($c->roastProfiles as $p) {
            $profiles[$p->level] = [
                'price' => (int) $p->price_per_100g,
                'agtron' => $p->agtron,
                'flavors' => array_merge(array_fill_keys(self::COFFEE_FLAVORS, 0), $p->flavor_values ?? []),
                'notes' => implode(', ', $p->tasting_notes ?? []),
            ];
        }

        return [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'story' => (string) $c->story,
            'story_en' => (string) $c->story_en,
            'stock' => (int) $c->stock_green_bean_gram,
            'cupping' => $c->cupping_score,
            'is_active' => (bool) $c->is_active,
            'image' => $c->image
                ? (\Illuminate\Support\Str::startsWith($c->image, ['http://', 'https://', '/']) ? $c->image : asset('storage/'.ltrim($c->image, '/')))
                : null,
            'farmer' => $t?->farmer, 'region' => $t?->region, 'altitude' => $t?->altitude,
            'varietal' => $t?->varietal, 'process' => $t?->process ?? 'Washed',
            'harvest_date' => $t?->harvest_date?->format('Y-m-d'), 'q_grader' => $t?->q_grader,
            'profiles' => $profiles,
            'levels' => \App\Models\RoastProfile::LEVELS,
            'flavor_keys' => self::COFFEE_FLAVORS,
            'processes' => self::COFFEE_PROCESSES,
        ];
    }

    public function coffeeShow(Request $request, int $id)
    {
        $this->admin($request);

        return $this->coffeePayload(Coffee::findOrFail($id));
    }

    public function coffeeMeta(Request $request)
    {
        $this->admin($request);

        return [
            'levels' => \App\Models\RoastProfile::LEVELS,
            'flavor_keys' => self::COFFEE_FLAVORS,
            'processes' => self::COFFEE_PROCESSES,
        ];
    }

    private function saveCoffee(Request $request, ?Coffee $existing)
    {
        $this->admin($request);
        $levels = array_keys(\App\Models\RoastProfile::LEVELS);

        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:90', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('coffees', 'slug')->ignore($existing?->id)],
            'story' => ['required', 'string', 'max:1500'],
            'story_en' => ['nullable', 'string', 'max:1500'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'cupping' => ['nullable', 'numeric', 'between:60,100'],
            'is_active' => ['nullable', 'boolean'],
            'farmer' => ['required', 'string', 'max:120'],
            'region' => ['required', 'string', 'max:120'],
            'altitude' => ['required', 'integer', 'between:0,4000'],
            'varietal' => ['required', 'string', 'max:120'],
            'process' => ['required', Rule::in(self::COFFEE_PROCESSES)],
            'harvest_date' => ['nullable', 'date'],
            'q_grader' => ['nullable', 'string', 'max:120'],
            'profiles' => ['required', 'array', 'min:1'],
        ];
        foreach ($levels as $l) {
            $rules["profiles.$l"] = ['nullable', 'array'];
            $rules["profiles.$l.price"] = ["required_with:profiles.$l.notes", 'nullable', 'integer', 'between:1000,5000000'];
            $rules["profiles.$l.agtron"] = ['nullable', 'integer', 'between:20,120'];
            $rules["profiles.$l.notes"] = ['nullable', 'string', 'max:200'];
            $rules["profiles.$l.flavors.*"] = ['integer', 'between:0,100'];
        }
        $data = $request->validate($rules, [
            'required' => 'Wajib diisi.',
            'integer' => 'Harus angka bulat.',
            'numeric' => 'Harus berupa angka.',
            'between' => 'Nilai di luar rentang yang diizinkan.',
            'max' => 'Terlalu panjang.',
            'slug.regex' => 'Slug hanya huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Slug ini sudah dipakai kopi lain.',
            'profiles.required' => 'Aktifkan minimal satu level sangrai.',
        ]);

        $chosen = collect($data['profiles'])->only($levels)->filter(fn ($p) => is_array($p) && isset($p['price']) && $p['price'] !== '');
        if ($chosen->isEmpty()) {
            abort(422, __('Aktifkan minimal satu level sangrai.'));
        }
        foreach ($chosen as $l => $p) {
            if (trim((string) ($p['notes'] ?? '')) === '') {
                abort(422, __('Catatan rasa level :l wajib diisi.', ['l' => \App\Models\RoastProfile::LEVELS[$l]]));
            }
        }

        // Level yang sudah pernah dipesan tidak boleh dimatikan.
        if ($existing) {
            $locked = \App\Models\RoastProfile::where('coffee_id', $existing->id)->whereHas('orderItems')->pluck('level')
                ->filter(fn ($l) => ! $chosen->has($l));
            if ($locked->isNotEmpty()) {
                abort(422, __('Level :l sudah pernah dipesan, tidak bisa dimatikan.', ['l' => $locked->map(fn ($l) => \App\Models\RoastProfile::LEVELS[$l])->join(', ')]));
            }
        }

        $wasNew = $existing === null;
        $slug = $data['slug'] ?? null;
        if (! $slug) {
            $slug = \Illuminate\Support\Str::slug($data['name']);
            $base = $slug;
            $i = 2;
            while (Coffee::where('slug', $slug)->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists()) {
                $slug = $base.'-'.$i++;
            }
        }

        $coffee = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $existing, $chosen, $wasNew, $slug) {
            $coffee = Coffee::updateOrCreate(['id' => $existing?->id], [
                'slug' => $slug,
                'name' => trim($data['name']),
                'story' => trim($data['story']),
                'story_en' => trim($data['story_en'] ?? '') ?: null,
                'cupping_score' => ($data['cupping'] ?? '') === '' ? null : (float) $data['cupping'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $delta = (int) $data['stock'] - (int) $coffee->stock_green_bean_gram;
            if ($delta !== 0) {
                $coffee->moveStock($wasNew ? 'initial' : 'adjustment', $delta, $wasNew ? null : 'Diubah lewat form kopi');
            }

            \App\Models\Traceability::updateOrCreate(['coffee_id' => $coffee->id], [
                'farmer' => trim($data['farmer']),
                'region' => trim($data['region']),
                'altitude' => (int) $data['altitude'],
                'varietal' => trim($data['varietal']),
                'process' => $data['process'],
                'harvest_date' => $data['harvest_date'] ?? null ?: null,
                'q_grader' => trim($data['q_grader'] ?? '') ?: null,
            ]);

            foreach ($chosen as $level => $p) {
                $old = $coffee->roastProfiles()->where('level', $level)->first();
                $coffee->roastProfiles()->updateOrCreate(['level' => $level], [
                    'agtron' => ($p['agtron'] ?? '') === '' ? null : (int) $p['agtron'],
                    'price_per_100g' => (int) $p['price'],
                    'flavor_values' => collect(self::COFFEE_FLAVORS)->mapWithKeys(fn ($f) => [$f => (int) ($p['flavors'][$f] ?? 50)])->all(),
                    'tasting_notes' => collect(explode(',', (string) $p['notes']))->map(fn ($n) => trim($n))->filter()->values()->all(),
                    'roast_curve' => $old?->roast_curve ?? \App\Models\RoastProfile::defaultCurve($level),
                ]);
            }
            $coffee->roastProfiles()->whereNotIn('level', $chosen->keys())->delete();

            return $coffee;
        });

        ActivityLog::record($wasNew ? 'coffee.created' : 'coffee.updated', $coffee, $wasNew ? 'Kopi :n ditambahkan' : 'Kopi :n diperbarui', ['n' => $coffee->name]);

        return response()->json(['message' => $wasNew ? 'Kopi ditambahkan.' : 'Kopi disimpan.', 'data' => $this->coffeePayload($coffee->fresh())], $wasNew ? 201 : 200);
    }

    public function coffeeStore(Request $request)
    {
        return $this->saveCoffee($request, null);
    }

    public function coffeeUpdate(Request $request, int $id)
    {
        return $this->saveCoffee($request, Coffee::findOrFail($id));
    }

    public function coffeePhoto(Request $request, int $id)
    {
        $this->admin($request);
        $c = Coffee::findOrFail($id);
        $request->validate(
            ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']],
            ['photo.required' => 'Pilih foto dulu.', 'photo.image' => 'File harus berupa gambar.', 'photo.mimes' => 'Format harus JPG, PNG, atau WebP.', 'photo.max' => 'Ukuran foto maksimal 3 MB.']
        );
        $old = $c->image;
        $c->update(['image' => $request->file('photo')->store('coffees', 'public')]);
        if ($old && ! \Illuminate\Support\Str::startsWith($old, ['http://', 'https://', '/'])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
        }
        ActivityLog::record('coffee.updated', $c, 'Foto kopi :n diperbarui', ['n' => $c->name]);

        return ['message' => 'Foto disimpan.', 'data' => $this->coffeePayload($c->fresh())];
    }

    public function coffeePhotoDelete(Request $request, int $id)
    {
        $this->admin($request);
        $c = Coffee::findOrFail($id);
        $old = $c->image;
        $c->update(['image' => null]);
        if ($old && ! \Illuminate\Support\Str::startsWith($old, ['http://', 'https://', '/'])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
        }

        return ['message' => 'Foto dihapus.', 'data' => $this->coffeePayload($c->fresh())];
    }

    public function products(Request $request)
    {
        $this->admin($request);
        $q = trim((string) $request->query('q', ''));
        $list = Product::when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderByDesc('is_active')->orderBy('name')->get();

        return [
            'categories' => Product::CATEGORIES,
            'data' => $list->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'category_label' => Product::CATEGORIES[$p->category] ?? $p->category,
                'price' => (int) $p->price,
                'stock' => (int) $p->stock,
                'is_active' => (bool) $p->is_active,
            ])->values(),
        ];
    }

    private function productPayload(Product $p): array
    {
        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'name' => $p->name,
            'name_en' => $p->name_en,
            'category' => $p->category,
            'description' => $p->description,
            'description_en' => $p->description_en,
            'price' => (int) $p->price,
            'stock' => (int) $p->stock,
            'option_label' => $p->option_label,
            'options' => $p->optionList(),
            'is_active' => (bool) $p->is_active,
            'image' => \App\Support\ApiImage::url($p->image),
        ];
    }

    public function productShow(Request $request, int $id)
    {
        $this->admin($request);

        return ['categories' => Product::CATEGORIES, 'data' => $this->productPayload(Product::findOrFail($id))];
    }

    private function saveProduct(Request $request, ?Product $existing)
    {
        $this->admin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'description' => ['required', 'string', 'max:400'],
            'description_en' => ['nullable', 'string', 'max:400'],
            'price' => ['required', 'integer', 'between:1000,50000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'option_label' => ['nullable', 'string', 'max:30'],
            'options' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'required' => 'Wajib diisi.',
            'integer' => 'Harus angka bulat.',
            'between' => 'Nilai di luar rentang yang diizinkan.',
            'max' => 'Terlalu panjang.',
        ]);

        $options = collect(explode(',', (string) ($data['options'] ?? '')))->map(fn ($o) => trim($o))->filter()->unique()->values();
        if ($options->count() > 12 || $options->contains(fn ($o) => mb_strlen($o) > 30)) {
            return response()->json(['message' => 'Maksimal 12 varian, masing-masing paling panjang 30 karakter.'], 422);
        }

        $name = trim($data['name']);
        $slug = $existing?->slug;
        if (! $slug) {
            $base = \Illuminate\Support\Str::slug($name) ?: 'produk';
            $slug = $base;
            for ($i = 2; Product::where('slug', $slug)->exists(); $i++) {
                $slug = $base.'-'.$i;
            }
        }

        $p = Product::updateOrCreate(['id' => $existing?->id], [
            'slug' => $slug,
            'name' => $name,
            'name_en' => trim((string) ($data['name_en'] ?? '')) ?: null,
            'category' => $data['category'],
            'description' => trim($data['description']),
            'description_en' => trim((string) ($data['description_en'] ?? '')) ?: null,
            'price' => (int) $data['price'],
            'stock' => (int) $data['stock'],
            'option_label' => $options->isNotEmpty() ? (trim((string) ($data['option_label'] ?? '')) ?: null) : null,
            'options' => $options->isNotEmpty() ? $options->all() : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        ActivityLog::record($existing ? 'product.updated' : 'product.created', $p,
            $existing ? 'Produk :n diperbarui' : 'Produk :n ditambahkan', ['n' => $p->name]);

        return response()->json(['message' => $existing ? 'Perlengkapan diperbarui.' : 'Perlengkapan ditambahkan.', 'data' => $this->productPayload($p)], $existing ? 200 : 201);
    }

    public function productPhoto(Request $request, int $id)
    {
        $this->admin($request);
        $p = Product::findOrFail($id);
        $request->validate(
            ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']],
            ['photo.required' => 'Pilih foto dulu.', 'photo.image' => 'File harus berupa gambar.', 'photo.mimes' => 'Format harus JPG, PNG, atau WebP.', 'photo.max' => 'Ukuran foto maksimal 3 MB.']
        );

        $old = $p->image;
        $p->update(['image' => $request->file('photo')->store('products', 'public')]);
        if ($old && ! \Illuminate\Support\Str::startsWith($old, ['http://', 'https://', '/'])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
        }
        ActivityLog::record('product.updated', $p, 'Foto produk :n diperbarui', ['n' => $p->name]);

        return ['message' => 'Foto disimpan.', 'data' => $this->productPayload($p->fresh())];
    }

    public function productPhotoDelete(Request $request, int $id)
    {
        $this->admin($request);
        $p = Product::findOrFail($id);
        $old = $p->image;
        $p->update(['image' => null]);
        if ($old && ! \Illuminate\Support\Str::startsWith($old, ['http://', 'https://', '/'])) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
        }

        return ['message' => 'Foto dihapus.', 'data' => $this->productPayload($p->fresh())];
    }

    public function productStore(Request $request)
    {
        return $this->saveProduct($request, null);
    }

    public function productUpdate(Request $request, int $id)
    {
        return $this->saveProduct($request, Product::findOrFail($id));
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
            'best_before_days' => max(1, Setting::int('best_before_days')),
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
            'best_before_days' => ['required', 'integer', 'min:1', 'max:365'],
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
            'shipping_flat' => (int) $d['shipping_flat'], 'free_shipping_from' => (int) $d['free_from'], 'low_stock_gram' => (int) $d['low_stock'], 'best_before_days' => (int) $d['best_before_days'],
            'addons_enabled' => ! empty($d['addons_enabled']) ? 1 : 0, 'addon_gift_wrap' => (int) $d['gift_wrap_price'], 'addon_card' => (int) $d['card_price'],
            'cod_enabled' => ! empty($d['cod_enabled']) ? 1 : 0, 'cod_max' => (int) $d['cod_max'],
        ]);
        ActivityLog::record('settings.updated', null, 'Pengaturan toko diperbarui');

        return $this->settingsPayload();
    }
}
