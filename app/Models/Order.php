<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    use HasFactory;

    public const FLOW = [
        'queued', 'roasting', 'grinding', 'packing', 'shipped', 'completed',
    ];

    public const STATUS_LABELS = [
        'pending_payment' => 'Menunggu Pembayaran',
        'queued'          => 'Masuk Antrian',
        'roasting'        => 'Sedang Disangrai',
        'grinding'        => 'Sedang Digiling',
        'packing'         => 'Dikemas',
        'shipped'         => 'Dikirim',
        'completed'       => 'Selesai',
        'cancelled'       => 'Dibatalkan',
    ];

    /** Tab filter daftar pesanan: kunci => [label, status]. */
    public const TABS = [
        '' => ['Semua', null],
        'unpaid' => ['Menunggu bayar', ['pending_payment']],
        'production' => ['Produksi', ['queued', 'roasting', 'grinding', 'packing']],
        'shipped' => ['Dikirim', ['shipped']],
        'completed' => ['Selesai', ['completed']],
        'cancelled' => ['Batal', ['cancelled']],
    ];

    public const PAYMENTS = [
        'online' => 'Bayar online',
        'cod'    => 'COD (bayar di tempat)',
    ];

    protected $fillable = [
        'order_code', 'user_id', 'status', 'payment_method', 'snap_token', 'midtrans_order_id', 'payment_channel',
        'subtotal', 'shipping_cost', 'gift_wrap', 'card_message', 'addon_cost', 'discount', 'voucher_id', 'voucher_code', 'total',
        'recipient_name', 'recipient_phone', 'shipping_address',
        'tracking_number', 'admin_note', 'roaster_id',
        'paid_at', 'roasted_at', 'shipped_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'gift_wrap'  => 'boolean',
            'addon_cost' => 'integer',
            'paid_at'    => 'datetime',
            'roasted_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function roaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'roaster_id');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isCod(): bool
    {
        return $this->payment_method === 'cod';
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /** Invoice & label hanya untuk pesanan yang sudah lunas; COD boleh karena kurirnya yang menagih. */
    public function canPrint(): bool
    {
        return $this->status !== 'cancelled' && ($this->isCod() || $this->isPaid());
    }

    public function getPaymentLabelAttribute(): string
    {
        return __(self::PAYMENTS[$this->payment_method] ?? $this->payment_method);
    }

    public function getStatusLabelAttribute(): string
    {
        return __(self::STATUS_LABELS[$this->status] ?? $this->status);
    }

    public function nextStatus(): ?string
    {
        $i = array_search($this->status, self::FLOW, true);

        return $i !== false && isset(self::FLOW[$i + 1])
            ? self::FLOW[$i + 1]
            : null;
    }

    public function progressPercent(): int
    {
        $i = array_search($this->status, self::FLOW, true);

        return $i === false ? 0 : (int) round(($i + 1) / count(self::FLOW) * 100);
    }

    public static function generateCode(): string
    {
        $prefix = 'BRL-' . now()->format('Ymd');
        $count  = static::whereDate('created_at', today())->count() + 1;

        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /** Filter yang dipakai daftar admin dan ekspor CSV, supaya hasilnya selalu sama. */
    public function scopeAdminFilter($query, string $tab = '', string $q = '', ?string $from = null, ?string $to = null)
    {
        $statuses = self::TABS[$tab][1] ?? null;
        $q = trim($q);

        return $query
            ->when($statuses, fn ($x) => $x->whereIn('status', $statuses))
            ->when($from, fn ($x) => $x->where('created_at', '>=', \Carbon\Carbon::parse($from)->startOfDay()))
            ->when($to, fn ($x) => $x->where('created_at', '<=', \Carbon\Carbon::parse($to)->endOfDay()))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('order_code', 'like', "%{$q}%")
                ->orWhere('recipient_name', 'like', "%{$q}%")
                ->orWhere('recipient_phone', 'like', "%{$q}%")
                ->orWhere('tracking_number', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))));
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'pending_payment' => 'border-[#E8782A]/50 text-[#F2A266]',
            'completed', 'shipped' => 'border-bluelab/60 text-[#9DBBEF]',
            'cancelled' => 'border-kraft/20 text-kraft/45',
            default => 'border-kraft/35 text-kraft',
        };
    }

    public function scopeInProduction($query)
    {
        return $query->whereIn('status', ['queued', 'roasting', 'grinding', 'packing']);
    }

    /** Ongkir flat dan batas gratis ongkir diatur admin di Pengaturan (0 = tanpa gratis ongkir). */
    public static function shippingFor(int $subtotal): int
    {
        $free = Setting::int('free_shipping_from');

        return $free > 0 && $subtotal >= $free ? 0 : Setting::int('shipping_flat');
    }

    /** Panjang maksimal pesan kartu ucapan (karakter). */
    public const CARD_MAX = 160;

    /** Add-on checkout (kemasan hadiah, kartu ucapan) bisa dimatikan admin; harganya diatur di Pengaturan. */
    public static function addonsEnabled(): bool
    {
        return (bool) Setting::int('addons_enabled');
    }

    public static function giftWrapPrice(): int
    {
        return max(0, Setting::int('addon_gift_wrap'));
    }

    public static function cardPrice(): int
    {
        return max(0, Setting::int('addon_card'));
    }

    /** Pesan kartu yang sudah dirapikan (null bila kosong). Spasi dan baris ganda dipadatkan. */
    public static function cleanCardMessage(?string $message): ?string
    {
        $m = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $message)));

        return $m === '' ? null : mb_substr($m, 0, self::CARD_MAX);
    }

    /** Pesanan ini memakai add-on (kemasan hadiah dan/atau kartu ucapan). */
    public function hasAddons(): bool
    {
        return $this->gift_wrap || filled($this->card_message);
    }

    /** Nama add-on yang dipilih, mis. "kemasan hadiah, kartu ucapan"; kosong bila tidak ada. */
    public function addonLabel(): string
    {
        return collect([
            $this->gift_wrap ? __('kemasan hadiah') : null,
            filled($this->card_message) ? __('kartu ucapan') : null,
        ])->filter()->implode(', ');
    }

    /** Biaya add-on untuk pilihan ini; 0 bila add-on dimatikan admin. */
    public static function addonCostFor(bool $giftWrap, ?string $card): int
    {
        if (! self::addonsEnabled()) {
            return 0;
        }

        return ($giftWrap ? self::giftWrapPrice() : 0) + (self::cleanCardMessage($card) !== null ? self::cardPrice() : 0);
    }

    /** Alasan COD tidak boleh dipakai untuk total ini, atau null bila boleh. Diatur admin di Pengaturan. */
    public static function codProblem(int $total): ?string
    {
        if (! Setting::int('cod_enabled')) {
            return __('COD sedang tidak tersedia.');
        }
        $max = Setting::int('cod_max');

        return $max > 0 && $total > $max
            ? __('COD hanya untuk pesanan sampai Rp:n.', ['n' => number_format($max, 0, ',', '.')])
            : null;
    }

    /** Pesanan satu item; pintasan untuk placeMany. */
    public static function place(User $user, RoastProfile $profile, int $grams, string $grind, array $ship, ?string $voucherCode = null): self
    {
        return self::placeMany($user, [['profile' => $profile, 'grams' => $grams, 'grind' => $grind]], $ship, $voucherCode);
    }

    /**
     * Buat satu pesanan berisi banyak item. Harga dihitung ulang di sini dari
     * database, bukan dipercaya dari browser, dan stok biji mentah dikunci
     * selama transaksi supaya dua pembeli tidak bisa mengambil stok yang sama.
     * Item dari kopi yang sama dijumlahkan dulu sebelum stok dicek.
     *
     * @param  array<int,array{profile:RoastProfile,grams:int,grind:string}>  $lines
     * @param  array{gift_wrap?:bool,card_message?:?string}  $extras  add-on checkout; harganya dihitung di sini, bukan dipercaya dari browser
     * @throws \RuntimeException bila stok tidak cukup atau keranjang kosong
     */
    public static function placeMany(User $user, array $lines, array $ship, ?string $voucherCode = null, string $payment = 'online', array $extras = []): self
    {
        if ($lines === []) {
            throw new \RuntimeException(__('Keranjangmu masih kosong.'));
        }

        $payment = $payment === 'cod' ? 'cod' : 'online';

        // Add-on yang dimatikan admin diabaikan, jadi tidak ada yang tercatat tanpa biaya.
        $giftWrap = self::addonsEnabled() && ! empty($extras['gift_wrap']);
        $card = self::addonsEnabled() ? self::cleanCardMessage($extras['card_message'] ?? null) : null;
        $addon = self::addonCostFor($giftWrap, $card);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $lines, $ship, $voucherCode, $payment, $giftWrap, $card, $addon) {
            // Kunci kopi berurutan berdasarkan id supaya dua transaksi tidak saling tunggu (deadlock).
            $coffees = Coffee::whereIn('id', collect($lines)->map(fn ($l) => $l['profile']->coffee_id)->unique())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $need = [];
            foreach ($lines as $l) {
                $c = $coffees[$l['profile']->coffee_id] ?? null;
                if (! $c || ! $c->is_active) {
                    throw new \RuntimeException(__('Stok biji mentahnya tidak cukup untuk berat ini. Coba ukuran yang lebih kecil.'));
                }
                $need[$c->id] = ($need[$c->id] ?? 0) + $c->greenBeanNeeded($l['grams']);
            }
            foreach ($need as $id => $gram) {
                if ($coffees[$id]->stock_green_bean_gram < $gram) {
                    throw new \RuntimeException(__('Stok biji mentahnya tidak cukup untuk berat ini. Coba ukuran yang lebih kecil.'));
                }
            }

            $subtotal = (int) collect($lines)->sum(fn ($l) => $l['profile']->priceFor($l['grams']));
            $shipping = self::shippingFor($subtotal);

            // Voucher dikunci & divalidasi ulang di sini, bukan dipercaya dari browser.
            $voucher = null;
            $discount = 0;
            if ($voucherCode !== null && $voucherCode !== '') {
                $voucher = Voucher::where('code', Voucher::normalize($voucherCode))->lockForUpdate()->first();
                $problem = $voucher ? $voucher->problemFor($subtotal, $user) : __('Kode voucher tidak ditemukan.');
                if ($problem) {
                    throw new \RuntimeException($problem);
                }
                $discount = $voucher->discountFor($subtotal);
            }

            if ($payment === 'cod' && ($problem = self::codProblem($subtotal + $shipping + $addon - $discount))) {
                throw new \RuntimeException($problem);
            }

            $order = self::create([
                'order_code'       => self::generateCode(),
                'user_id'          => $user->id,
                // COD tidak menunggu pembayaran: langsung antri sangrai, uangnya masuk saat paket diterima.
                'status'           => $payment === 'cod' ? 'queued' : 'pending_payment',
                'payment_method'   => $payment,
                'subtotal'         => $subtotal,
                'shipping_cost'    => $shipping,
                'gift_wrap'        => $giftWrap,
                'card_message'     => $card,
                'addon_cost'       => $addon,
                'discount'         => $discount,
                'voucher_id'       => $voucher?->id,
                'voucher_code'     => $voucher?->code,
                'total'            => $subtotal + $shipping + $addon - $discount,
                'recipient_name'   => $ship['name'],
                'recipient_phone'  => $ship['phone'],
                'shipping_address' => $ship['address'],
            ]);

            foreach ($lines as $l) {
                $profile = $l['profile'];
                $order->items()->create([
                    'roast_profile_id'        => $profile->id,
                    'grams'                   => $l['grams'],
                    'grind'                   => $l['grind'],
                    'coffee_name_snapshot'    => $coffees[$profile->coffee_id]->name,
                    'roast_level_snapshot'    => $profile->level_label,
                    'price_per_100g_snapshot' => $profile->price_per_100g,
                    'line_total'              => $profile->priceFor($l['grams']),
                ]);
                $coffees[$profile->coffee_id]->reduceStock($l['grams'], $order);
            }
            $voucher?->increment('used_count');

            ActivityLog::record('order.created', $order, 'Pesanan dibuat oleh :n', ['n' => $user->name]);
            Notice::send(Notice::admins(), 'order', 'Pesanan baru masuk', ':code · :n · Rp :t', [
                'code' => $order->order_code, 'n' => $user->name, 't' => number_format($order->total, 0, ',', '.'),
            ], route('admin.orders', ['order' => $order->order_code], false), skip: auth()->id());

            if ($payment === 'cod') {
                $order->announceQueued('Pesanan COD diterima', 'Pesanan :code (COD) masuk antrian sangrai.');
            }

            // Email baru dikirim setelah transaksi benar-benar tersimpan.
            \Illuminate\Support\Facades\DB::afterCommit(fn () => \App\Support\OrderMailer::send($order, 'placed'));

            return $order;
        });
    }

    /** Beri tahu pembeli dan staf bahwa pesanan masuk antrian sangrai. */
    private function announceQueued(string $title, string $body): void
    {
        $p = ['code' => $this->order_code, 'item' => $this->itemSummary()];
        Notice::send($this->user, 'order', $title, $body, $p, route('track', $this->order_code, false));
        foreach (Notice::staff() as $staff) {
            Notice::send($staff, 'queue', 'Pesanan masuk antrian', ':code · :item', $p,
                $staff->isAdmin() ? route('admin.orders', ['order' => $this->order_code], false) : route('roastery', [], false), skip: auth()->id());
        }
    }

    /** Simulasi pembayaran: tanpa gateway, tombolnya langsung menandai lunas. */
    public function markPaid(): bool
    {
        if ($this->status !== 'pending_payment') {
            return false;
        }

        $this->update(['status' => 'queued', 'paid_at' => now()]);
        ActivityLog::record('order.paid', $this, 'Pembayaran dikonfirmasi');

        $this->announceQueued('Pembayaran diterima', 'Pesanan :code masuk antrian sangrai.');
        \App\Support\OrderMailer::send($this, 'paid');

        return true;
    }

    /** Maju satu tahap produksi: queued -> roasting -> grinding -> packing. */
    public function advance(): bool
    {
        $next = $this->nextStatus();

        if (! $next || ! in_array($this->status, ['queued', 'roasting', 'grinding'], true)) {
            return false;
        }

        $from = $this->status;
        $this->update([
            'status' => $next,
            'roaster_id' => $this->roaster_id ?? auth()->id(),
            'roasted_at' => $next === 'roasting' ? now() : $this->roasted_at,
        ]);
        ActivityLog::record('order.status', $this, 'Status: :a → :b', [
            'a' => __(self::STATUS_LABELS[$from]), 'b' => __(self::STATUS_LABELS[$next]),
        ]);

        $titles = ['roasting' => 'Kopimu mulai disangrai', 'grinding' => 'Kopimu sedang digiling', 'packing' => 'Kopimu sedang dikemas'];
        if (isset($titles[$next])) {
            Notice::send($this->user, 'status', $titles[$next], ':code · :item', ['code' => $this->order_code, 'item' => $this->itemSummary()], route('track', $this->order_code, false));
        }

        return true;
    }

    /** packing -> shipped, wajib dengan nomor resi. */
    public function ship(string $tracking): bool
    {
        $tracking = trim($tracking);

        if ($this->status !== 'packing' || $tracking === '') {
            return false;
        }

        $this->update(['status' => 'shipped', 'tracking_number' => $tracking, 'shipped_at' => now()]);
        ActivityLog::record('order.shipped', $this, 'Dikirim dengan resi :r', ['r' => $tracking]);
        Notice::send($this->user, 'ship', 'Pesananmu dikirim', ':code · resi :r', ['code' => $this->order_code, 'r' => $tracking], route('track', $this->order_code, false));
        \App\Support\OrderMailer::send($this, 'shipped');

        return true;
    }

    public function complete(): bool
    {
        if ($this->status !== 'shipped') {
            return false;
        }

        // COD: uang tunai dari kurir dianggap diterima saat pesanan selesai, baru masuk laporan.
        $cod = $this->isCod() && ! $this->isPaid();
        $this->update(['status' => 'completed', 'completed_at' => now()] + ($cod ? ['paid_at' => now()] : []));
        ActivityLog::record('order.completed', $this, 'Pesanan ditandai selesai');
        if ($cod) {
            ActivityLog::record('order.paid', $this, 'Pembayaran COD diterima');
        }
        Notice::send($this->user, 'status', 'Pesanan selesai', 'Pesanan :code selesai. Selamat menyeduh!', ['code' => $this->order_code], route('track', $this->order_code, false));

        return true;
    }

    /** Batalkan pesanan yang belum disangrai dan kembalikan stok biji mentahnya. */
    public function cancelAndRestock(?string $reason = null): bool
    {
        if (! in_array($this->status, ['pending_payment', 'queued'], true)) {
            return false;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($reason) {
            foreach ($this->items()->with('roastProfile.coffee')->get() as $item) {
                $coffee = $item->roastProfile?->coffee;
                $coffee?->moveStock('cancel', $coffee->greenBeanNeeded($item->grams), null, $this);
            }
            if ($this->voucher_id) {
                Voucher::where('id', $this->voucher_id)->where('used_count', '>', 0)->decrement('used_count');
            }
            $this->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            ActivityLog::record('order.cancelled', $this, $reason ? 'Pesanan dibatalkan: :r' : 'Pesanan dibatalkan', ['r' => $reason]);
            Notice::send($this->user, 'cancel', 'Pesanan dibatalkan', $reason ? ':code · :r' : 'Pesanan :code dibatalkan.', ['code' => $this->order_code, 'r' => $reason], route('track', $this->order_code, false));
        });

        \App\Support\OrderMailer::send($this, 'cancelled');

        return true;
    }

    /**
     * Item yang identik (kopi, level, gilingan, berat) digabung untuk tampilan.
     * Di database tetap satu baris per bungkus supaya tiap bungkus punya jurnal sangrai sendiri.
     *
     * @return \Illuminate\Support\Collection<int,object{coffee_name_snapshot:string,level_label:string,grind_label:string,grams:int,qty:int,unit_price:int,line_total:int,total_grams:int}>
     */
    public function groupedItems(): \Illuminate\Support\Collection
    {
        return $this->items->groupBy(fn ($i) => implode('|', [$i->roast_profile_id, $i->coffee_name_snapshot, $i->roast_level_snapshot, $i->grind, $i->grams]))
            ->map(function ($g) {
                $f = $g->first();

                return (object) [
                    'coffee_name_snapshot' => $f->coffee_name_snapshot,
                    'level_label' => $f->level_label,
                    'grind_label' => $f->grind_label,
                    'grams' => $f->grams,
                    'qty' => $g->count(),
                    'unit_price' => (int) $f->line_total,
                    'line_total' => (int) $g->sum('line_total'),
                    'total_grams' => (int) $g->sum('grams'),
                ];
            })->values();
    }

    /** Ringkasan satu baris isi pesanan, mis. "Gayo · Medium 200 g × 3 +1 lainnya". */
    public function itemSummary(): string
    {
        $groups = $this->groupedItems();
        $i = $groups->first();
        if (! $i) {
            return '';
        }

        $text = "{$i->coffee_name_snapshot} · {$i->level_label} {$i->grams} g".($i->qty > 1 ? " × {$i->qty}" : '');

        return $groups->count() > 1 ? $text.' '.__('+:n lainnya', ['n' => $groups->count() - 1]) : $text;
    }
}
