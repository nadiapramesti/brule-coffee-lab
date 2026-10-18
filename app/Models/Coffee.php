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

    /** Nama proses yang ikut di belakang nama kopi; dibuang untuk nama pendek di kartu dan label kemasan. */
    public const PROCESS_WORDS = [' Washed', ' Natural', ' Honey', ' Wet-Hulled', ' Anaerobic', ' Carbonic Maceration'];

    public function shortName(): string
    {
        return trim(str_replace(self::PROCESS_WORDS, '', $this->name));
    }

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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Ulasan yang tidak disembunyikan admin, terbaru dulu. */
    public function visibleReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_hidden', false)->latest('id');
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

    public function reduceStock(int $roastedGram, ?Order $order = null): void
    {
        $this->moveStock('sale', -$this->greenBeanNeeded($roastedGram), null, $order);
    }

    /**
     * Satu-satunya pintu perubahan stok. Mengubah saldo dan mencatat
     * pergerakannya dalam satu langkah, supaya riwayat selalu cocok dengan saldo.
     * $grams bertanda: positif masuk, negatif keluar (gram biji mentah).
     */
    public function moveStock(string $type, int $grams, ?string $reason = null, ?Order $order = null): StockMovement
    {
        $before = (int) $this->stock_green_bean_gram;
        $this->increment('stock_green_bean_gram', $grams);
        $this->warnIfLow($before);

        return $this->stockMovements()->create([
            'user_id' => auth()->id(),
            'order_id' => $order?->id,
            'type' => $type,
            'grams' => $grams,
            'balance_after' => $this->stock_green_bean_gram,
            'reason' => $reason,
        ]);
    }

    /** Kabari admin sekali saat stok baru saja melewati batas menipis (bukan tiap penjualan). */
    private function warnIfLow(int $before): void
    {
        $limit = Setting::int('low_stock_gram');

        if ($before >= $limit && $this->stock_green_bean_gram < $limit) {
            Notice::send(Notice::admins(), 'stock', 'Stok menipis', ':n tersisa :kg kg', [
                'n' => $this->name, 'kg' => number_format($this->stock_green_bean_gram / 1000, 1, ',', '.'),
            ], route('admin.stock', [], false), skip: auth()->id());
        }
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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

    /** Foto produk yang diupload admin; null kalau belum ada (tampilannya jatuh ke ilustrasi kantong). */
    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return \Illuminate\Support\Str::startsWith($this->image, ['http://', 'https://', '/'])
            ? $this->image
            : asset('storage/'.ltrim($this->image, '/'));
    }

    /** Harga termurah untuk kemasan terkecil (200 g), buat label "mulai dari" di katalog. */
    public function startingPrice(int $gram = 200): ?int
    {
        $min = $this->roastProfiles->min('price_per_100g');

        return $min === null ? null : (int) round($min * ($gram / 100));
    }

    /** soldout | low | ok, dipakai badge stok di katalog. */
    public function stockState(): string
    {
        if (! $this->hasStockFor(200)) {
            return 'soldout';
        }

        return $this->stock_green_bean_gram < Setting::int('low_stock_gram') ? 'low' : 'ok';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
