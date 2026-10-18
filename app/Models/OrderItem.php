<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    public const GRINDS = [
        'whole_bean'   => 'Biji Utuh',
        'v60'          => 'V60 / Pour Over',
        'espresso'     => 'Espresso',
        'french_press' => 'French Press',
        'tubruk'       => 'Tubruk',
        'aeropress'    => 'Aeropress',
    ];

    protected $fillable = [
        'order_id', 'roast_profile_id', 'product_id', 'grams', 'grind',
        'coffee_name_snapshot', 'roast_level_snapshot',
        'price_per_100g_snapshot', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'grams' => 'integer',
            'price_per_100g_snapshot' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Barang non-kopi: tidak disangrai, tidak digiling, tidak punya berat bungkus. */
    public function isProduct(): bool
    {
        return $this->product_id !== null || $this->roast_profile_id === null;
    }

    /** Keterangan di bawah nama: "Medium · V60" untuk kopi, pilihan varian (mis. "Hitam") untuk produk. */
    public function detail(): string
    {
        if ($this->isProduct()) {
            return (string) $this->roast_level_snapshot;
        }

        return $this->level_label.' · '.__($this->grind_label);
    }

    /** "200 g" untuk kopi; kosong untuk produk. */
    public function size(): string
    {
        return $this->isProduct() ? '' : $this->grams.' g';
    }

    public function roastProfile(): BelongsTo
    {
        return $this->belongsTo(RoastProfile::class);
    }

    public function journal(): HasOne
    {
        return $this->hasOne(RoastJournal::class);
    }

    /** Snapshot lama menyimpan kunci ('medium_light'); yang baru sudah label. */
    public function getLevelLabelAttribute(): string
    {
        return RoastProfile::LEVELS[$this->roast_level_snapshot] ?? $this->roast_level_snapshot;
    }

    public function getGrindLabelAttribute(): string
    {
        return self::GRINDS[$this->grind] ?? $this->grind;
    }
}
