<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Coffee extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'story', 'image',
        'stock_green_bean_gram', 'cupping_score', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cupping_score' => 'float',
            'is_active' => 'boolean',
            'stock_green_bean_gram' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function traceability(): HasOne
    {
        return $this->hasOne(Traceability::class);
    }

    public function roastProfiles(): HasMany
    {
        return $this->hasMany(RoastProfile::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(RoastJournal::class);
    }

    // ===== Helper stok =====

    public function hasStockFor(int $gram): bool
    {
        // green bean susut ~15% pas disangrai
        return $this->stock_green_bean_gram >= $this->greenBeanNeeded($gram);
    }

    public function greenBeanNeeded(int $roastedGram): int
    {
        return (int) ceil($roastedGram / 0.85);
    }

    public function reduceStock(int $roastedGram): void
    {
        $this->decrement('stock_green_bean_gram', $this->greenBeanNeeded($roastedGram));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
