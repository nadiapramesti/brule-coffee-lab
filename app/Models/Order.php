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

    protected $fillable = [
        'order_code', 'user_id', 'status',
        'subtotal', 'shipping_cost', 'total',
        'recipient_name', 'recipient_phone', 'shipping_address',
        'tracking_number', 'admin_note', 'roaster_id',
        'paid_at', 'roasted_at', 'shipped_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at'    => 'datetime',
            'roasted_at' => 'datetime',
            'shipped_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        $prefix = 'KRM-' . now()->format('Ymd');
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

    public const FREE_SHIPPING_FROM = 500000;
    public const SHIPPING_FLAT = 15000;

    public static function shippingFor(int $subtotal): int
    {
        return $subtotal >= self::FREE_SHIPPING_FROM ? 0 : self::SHIPPING_FLAT;
    }

    /**
     * Buat pesanan satu item. Harga dihitung ulang di sini dari database,
     * bukan dipercaya dari browser, dan stok biji mentah dikunci selama
     * transaksi supaya dua pembeli tidak bisa mengambil stok yang sama.
     *
     * @throws \RuntimeException bila stok tidak cukup
     */
    public static function place(User $user, RoastProfile $profile, int $grams, string $grind, array $ship): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $profile, $grams, $grind, $ship) {
            $coffee = Coffee::whereKey($profile->coffee_id)->lockForUpdate()->firstOrFail();

            if (! $coffee->is_active || ! $coffee->hasStockFor($grams)) {
                throw new \RuntimeException(__('Stok biji mentahnya tidak cukup untuk berat ini. Coba ukuran yang lebih kecil.'));
            }

            $line = $profile->priceFor($grams);
            $shipping = self::shippingFor($line);

            $order = self::create([
                'order_code'       => self::generateCode(),
                'user_id'          => $user->id,
                'status'           => 'pending_payment',
                'subtotal'         => $line,
                'shipping_cost'    => $shipping,
                'total'            => $line + $shipping,
                'recipient_name'   => $ship['name'],
                'recipient_phone'  => $ship['phone'],
                'shipping_address' => $ship['address'],
            ]);

            $order->items()->create([
                'roast_profile_id'        => $profile->id,
                'grams'                   => $grams,
                'grind'                   => $grind,
                'coffee_name_snapshot'    => $coffee->name,
                'roast_level_snapshot'    => $profile->level_label,
                'price_per_100g_snapshot' => $profile->price_per_100g,
                'line_total'              => $line,
            ]);

            $coffee->reduceStock($grams);

            ActivityLog::record('order.created', $order, 'Pesanan dibuat oleh :n', ['n' => $user->name]);

            return $order;
        });
    }

    /** Simulasi pembayaran: tanpa gateway, tombolnya langsung menandai lunas. */
    public function markPaid(): bool
    {
        if ($this->status !== 'pending_payment') {
            return false;
        }

        $this->update(['status' => 'queued', 'paid_at' => now()]);
        ActivityLog::record('order.paid', $this, 'Pembayaran dikonfirmasi');

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

        return true;
    }

    public function complete(): bool
    {
        if ($this->status !== 'shipped') {
            return false;
        }

        $this->update(['status' => 'completed', 'completed_at' => now()]);
        ActivityLog::record('order.completed', $this, 'Pesanan ditandai selesai');

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
                $coffee?->increment('stock_green_bean_gram', $coffee->greenBeanNeeded($item->grams));
            }
            $this->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            ActivityLog::record('order.cancelled', $this, $reason ? 'Pesanan dibatalkan: :r' : 'Pesanan dibatalkan', ['r' => $reason]);
        });

        return true;
    }
}
