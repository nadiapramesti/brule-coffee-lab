<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    public const TYPES = ['percent' => 'Persen (%)', 'fixed' => 'Potongan tetap (Rp)'];

    protected $fillable = ['code', 'type', 'value', 'min_subtotal', 'max_discount', 'quota', 'used_count', 'starts_at', 'expires_at', 'is_active', 'note'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public static function normalize(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', $code));
    }

    public function setCodeAttribute(string $v): void
    {
        $this->attributes['code'] = self::normalize($v);
    }

    public static function findByCode(string $code): ?self
    {
        return self::where('code', self::normalize($code))->first();
    }

    /** active | inactive | scheduled | expired | exhausted */
    public function state(): string
    {
        return match (true) {
            ! $this->is_active => 'inactive',
            $this->starts_at && $this->starts_at->isFuture() => 'scheduled',
            $this->expires_at && $this->expires_at->isPast() => 'expired',
            $this->quota !== null && $this->used_count >= $this->quota => 'exhausted',
            default => 'active',
        };
    }

    /** Pesan error (kunci terjemahan) bila voucher tidak bisa dipakai, null bila boleh. */
    public function problemFor(int $subtotal, ?User $user = null): ?string
    {
        $problem = match ($this->state()) {
            'inactive' => __('Voucher ini sedang tidak aktif.'),
            'scheduled' => __('Voucher ini belum berlaku.'),
            'expired' => __('Voucher ini sudah kedaluwarsa.'),
            'exhausted' => __('Kuota voucher ini sudah habis.'),
            default => null,
        };
        if ($problem) {
            return $problem;
        }
        if ($subtotal < $this->min_subtotal) {
            return __('Minimal belanja Rp:n untuk voucher ini.', ['n' => number_format($this->min_subtotal, 0, ',', '.')]);
        }
        if ($user && Order::where('user_id', $user->id)->where('voucher_id', $this->id)->where('status', '!=', 'cancelled')->exists()) {
            return __('Kamu sudah pernah memakai voucher ini.');
        }

        return null;
    }

    public function discountFor(int $subtotal): int
    {
        $d = $this->type === 'percent'
            ? (int) floor($subtotal * $this->value / 100)
            : $this->value;

        if ($this->type === 'percent' && $this->max_discount) {
            $d = min($d, $this->max_discount);
        }

        return max(0, min($d, $subtotal));
    }

    public function benefitLabel(): string
    {
        $v = $this->type === 'percent' ? $this->value . '%' : 'Rp' . number_format($this->value, 0, ',', '.');

        return $this->type === 'percent' && $this->max_discount ? $v . ' (maks Rp' . number_format($this->max_discount, 0, ',', '.') . ')' : $v;
    }
}
