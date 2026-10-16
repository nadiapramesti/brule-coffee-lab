<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPES = [
        'initial' => 'Stok awal',
        'restock' => 'Restock',
        'sale' => 'Terjual',
        'cancel' => 'Pesanan batal',
        'adjustment' => 'Koreksi hitung',
        'waste' => 'Susut / rusak',
    ];

    protected $fillable = ['coffee_id', 'user_id', 'order_id', 'type', 'grams', 'balance_after', 'reason'];

    protected function casts(): array
    {
        return ['grams' => 'integer', 'balance_after' => 'integer'];
    }

    public function coffee(): BelongsTo
    {
        return $this->belongsTo(Coffee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return __(self::TYPES[$this->type] ?? $this->type);
    }

    public function tone(): string
    {
        return match ($this->type) {
            'restock', 'cancel', 'initial' => 'border-bluelab/60 text-[#9DBBEF]',
            'sale' => 'border-kraft/35 text-kraft',
            'waste' => 'border-[#E8782A]/50 text-[#F2A266]',
            default => 'border-kraft/30 text-kraft/80',
        };
    }
}
