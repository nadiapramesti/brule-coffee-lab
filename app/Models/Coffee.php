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
        'slug', 'name', 'story', 'story_en', 'image',
        'stock_green_bean_gram', 'cupping_score', 'is_active',
    ];

    /** Cerita kopi sesuai bahasa aktif; jatuh ke Indonesia kalau terjemahannya kosong. */
    public function localizedStory(): ?string
    {
        return app()->getLocale() === 'en' && $this->story_en
            ? $this->story_en
            : $this->story;
    }

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

    /**
     * Profil yang dipakai untuk mewakili kopi ini di luar builder.
     * Diambil yang medium; kalau tidak ada, yang di tengah daftar.
     */
    public function signatureProfile(): ?RoastProfile
    {
        $order = array_keys(RoastProfile::LEVELS);

        $sorted = $this->roastProfiles
            ->sortBy(fn ($p) => array_search($p->level, $order, true))
            ->values();

        return $sorted->firstWhere('level', 'medium')
            ?? $sorted->get(intdiv(max(0, $sorted->count() - 1), 2));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
