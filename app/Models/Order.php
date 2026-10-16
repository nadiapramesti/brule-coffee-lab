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
        return self::STATUS_LABELS[$this->status] ?? $this->status;
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
}
