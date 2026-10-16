<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoastJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_item_id', 'coffee_id',
        'brew_method', 'dose_gram', 'water_ml',
        'water_temp', 'brew_time_second',
        'rating', 'flavor_detected', 'notes', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'flavor_detected' => 'array',
            'is_public' => 'boolean',
            'rating' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coffee(): BelongsTo
    {
        return $this->belongsTo(Coffee::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function getRatioAttribute(): ?string
    {
        if (! $this->dose_gram || ! $this->water_ml) {
            return null;
        }

        return '1:' . round($this->water_ml / $this->dose_gram, 1);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
