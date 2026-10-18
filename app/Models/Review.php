<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['user_id', 'coffee_id', 'order_id', 'rating', 'body', 'is_hidden'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_hidden' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function coffee(): BelongsTo
    {
        return $this->belongsTo(Coffee::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeVisible($q)
    {
        return $q->where('is_hidden', false);
    }

    /** Nama untuk ditampilkan publik: nama depan + inisial belakang ("Nadia P."). */
    public function displayName(): string
    {
        if (! $this->user || $this->user->trashed()) {
            return __('Pembeli');
        }

        $parts = preg_split('/\s+/', trim((string) $this->user->name)) ?: [];
        $first = $parts[0] ?? __('Pembeli');

        return count($parts) > 1 ? $first.' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : $first;
    }

    /**
     * Ulasan hanya untuk pembeli: user harus punya pesanan SELESAI yang berisi kopi ini.
     * Mengembalikan pesanan terbaru yang memenuhi syarat, atau null.
     */
    public static function eligibleOrder(User $user, Coffee $coffee): ?Order
    {
        return $user->orders()->where('status', 'completed')
            ->whereHas('items', fn ($i) => $i
                ->whereHas('roastProfile', fn ($q) => $q->where('coffee_id', $coffee->id))
                ->orWhereIn('blend_profile_id', RoastProfile::where('coffee_id', $coffee->id)->select('id')))
            ->latest('id')->first();
    }
}
