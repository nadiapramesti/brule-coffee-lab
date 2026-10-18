<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Produk non-kopi: stok dalam unit, tanpa level sangrai dan gilingan. */
class Product extends Model
{
    public const CATEGORIES = [
        'brewer'    => 'Alat seduh',
        'grinder'   => 'Penggiling',
        'filter'    => 'Filter & kertas',
        'merch'     => 'Merchandise',
        'accessory' => 'Aksesori',
    ];

    public const LOW_STOCK = 5;

    protected $fillable = [
        'slug', 'name', 'name_en', 'category', 'description', 'description_en',
        'price', 'stock', 'option_label', 'options', 'image', 'option_images', 'is_active',
    ];

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer', 'options' => 'array', 'option_images' => 'array', 'is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'en' && $this->name_en ? $this->name_en : $this->name;
    }

    public function localizedDescription(): string
    {
        return app()->getLocale() === 'en' && $this->description_en ? $this->description_en : $this->description;
    }

    public function categoryLabel(): string
    {
        return __(self::CATEGORIES[$this->category] ?? $this->category);
    }

    /** @return array<int,string> */
    public function optionList(): array
    {
        return array_values(array_filter((array) $this->options, 'is_string'));
    }

    public function hasOptions(): bool
    {
        return $this->optionList() !== [];
    }

    public function validOption(?string $option): bool
    {
        return $this->hasOptions() ? in_array($option, $this->optionList(), true) : $option === null || $option === '';
    }

    /** Foto pilihan tertentu bila ada, kalau tidak foto utama. */
    public function imageUrl(?string $option = null): ?string
    {
        $path = ($option !== null && $option !== '' ? ($this->option_images[$option] ?? null) : null) ?: $this->image;
        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset('storage/'.ltrim($path, '/'));
    }

    /** soldout | low | ok */
    public function stockState(): string
    {
        return $this->stock < 1 ? 'soldout' : ($this->stock <= self::LOW_STOCK ? 'low' : 'ok');
    }
}
