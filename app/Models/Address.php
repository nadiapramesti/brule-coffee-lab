<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    public const MAX_PER_USER = 5;

    protected $fillable = ['user_id', 'label', 'recipient', 'phone', 'address', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Jadikan alamat ini utama dan turunkan yang lain, dalam satu langkah. */
    public function makeDefault(): void
    {
        static::where('user_id', $this->user_id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
