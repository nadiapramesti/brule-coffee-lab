<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Notifikasi dalam aplikasi (bel di navbar). title/body disimpan sebagai kunci
 * terjemahan Indonesia + params, jadi teksnya ikut bahasa pembacanya.
 */
class Notice extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'params', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['params' => 'array', 'read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Kirim ke satu atau banyak penerima. $skip = id yang tidak perlu diberi tahu (pelaku sendiri). */
    public static function send(iterable|User $to, string $type, string $title, ?string $body = null, array $params = [], ?string $url = null, ?int $skip = null): void
    {
        $users = $to instanceof User ? collect([$to]) : collect($to);

        foreach ($users->filter(fn ($u) => $u && $u->id !== $skip)->unique('id') as $user) {
            static::create([
                'user_id' => $user->id, 'type' => $type, 'title' => $title,
                'body' => $body, 'params' => $params, 'url' => $url,
            ]);
        }
    }

    public static function admins(): Collection
    {
        return User::where('role', 'admin')->get();
    }

    public static function staff(): Collection
    {
        return User::whereIn('role', ['admin', 'roaster'])->get();
    }

    public function titleText(): string
    {
        return __($this->title, $this->params ?? []);
    }

    public function bodyText(): ?string
    {
        return $this->body ? __($this->body, $this->params ?? []) : null;
    }

    public function scopeUnread($q)
    {
        return $q->whereNull('read_at');
    }
}
