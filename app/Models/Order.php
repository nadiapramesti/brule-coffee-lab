<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $fillable = [
        'order_code', 'user_id', 'status',
        'subtotal', 'shipping_cost', 'total',
        'recipient_name', 'recipient_phone', 'shipping_address',
        'tracking_number', 'roaster_id',
        'paid_at', 'roasted_at', 'shipped_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at'    => 'datetime',
            'roasted_at' => 'datetime',
            'shipped_at' => 'datetime',
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

            return $order;
        });
    }

    /** Simulasi pembayaran: tanpa gateway, tombolnya langsung menandai lunas. */
    public function markPaid(): void
    {
        if ($this->status === 'pending_payment') {
            $this->update(['status' => 'queued', 'paid_at' => now()]);
        }
    }

    /** Batalkan pesanan yang belum disangrai dan kembalikan stok biji mentahnya. */
    public function cancelAndRestock(): bool
    {
        if (! in_array($this->status, ['pending_payment', 'queued'], true)) {
            return false;
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            foreach ($this->items()->with('roastProfile.coffee')->get() as $item) {
                $coffee = $item->roastProfile?->coffee;
                $coffee?->increment('stock_green_bean_gram', $coffee->greenBeanNeeded($item->grams));
            }
            $this->update(['status' => 'cancelled']);
        });

        return true;
    }
}
