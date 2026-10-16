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

    /** Kurva standar untuk level baru: makin gelap, makin lama dan panas saat drop. */
    public static function defaultCurve(string $level): array
    {
        [$minute, $temp] = match ($level) {
            'light' => [9.8, 202],
            'medium_light' => [10.6, 207],
            'medium' => [11.4, 212],
            'medium_dark' => [12.2, 218],
            default => [13.0, 224],
        };

        return [
            ['t' => 0.0, 'temp' => 200, 'phase' => 'charge'],
            ['t' => 1.3, 'temp' => 92, 'phase' => 'turning_point'],
            ['t' => 4.0, 'temp' => 140, 'phase' => 'drying'],
            ['t' => 7.0, 'temp' => 170, 'phase' => 'maillard'],
            ['t' => 9.2, 'temp' => 196, 'phase' => 'first_crack'],
            ['t' => $minute, 'temp' => $temp, 'phase' => 'drop'],
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
