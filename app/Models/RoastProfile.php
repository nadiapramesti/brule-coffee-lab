<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoastProfile extends Model
{
    use HasFactory;

    public const LEVELS = [
        'light'        => 'Light',
        'medium_light' => 'Medium Light',
        'medium'       => 'Medium',
        'medium_dark'  => 'Medium Dark',
        'dark'         => 'Dark',
    ];

    protected $fillable = [
        'coffee_id', 'level', 'agtron',
        'flavor_values', 'tasting_notes', 'roast_curve',
        'price_per_100g',
    ];

    protected function casts(): array
    {
        return [
            'flavor_values' => 'array',
            'tasting_notes' => 'array',
            'roast_curve'   => 'array',
            'agtron'         => 'integer',
            'price_per_100g' => 'integer',
        ];
    }

    public function coffee(): BelongsTo
    {
        return $this->belongsTo(Coffee::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? $this->level;
    }

    public function priceFor(int $gram): int
    {
        return (int) round($this->price_per_100g * ($gram / 100));
    }
}
