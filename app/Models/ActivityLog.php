<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'subject_type', 'subject_id', 'action', 'description', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    /**
     * Catat satu aksi. $description adalah kunci terjemahan (Indonesia);
     * $params diisi ke placeholder-nya saat ditampilkan, jadi log ikut
     * berganti bahasa mengikuti pembacanya.
     */
    public static function record(string $action, ?Model $subject, string $description, array $params = [], array $extra = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'description' => $description,
            'meta' => ['params' => $params] + $extra,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function text(): string
    {
        return __($this->description, $this->meta['params'] ?? []);
    }
}
