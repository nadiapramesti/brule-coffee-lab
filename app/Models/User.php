<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'provider', 'provider_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->orderBy('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(RoastJournal::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Admin dan roaster bekerja di panel, bukan belanja di toko. */
    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'roaster'], true);
    }

    /** Nama rute tempat kerja masing-masing peran. */
    public function homeRoute(): string
    {
        return match (true) {
            $this->isAdmin() => 'admin.dashboard',
            $this->isRoaster() => 'roastery',
            default => 'account',
        };
    }

    /** Admin boleh melakukan apa pun yang boleh dilakukan roaster. */
    public function isRoaster(): bool
    {
        return in_array($this->role, ['roaster', 'admin'], true);
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function isCustomer(): bool
    {
        return ! $this->isStaff();
    }

    /** Blokir pelanggan: tidak bisa masuk dan semua sesi yang sedang berjalan diputus. */
    public function block(string $reason = ''): void
    {
        $this->forceFill(['blocked_at' => now(), 'blocked_reason' => trim($reason) !== '' ? trim($reason) : null])->save();
        $this->revokeSessions();
    }

    public function unblock(): void
    {
        $this->forceFill(['blocked_at' => null, 'blocked_reason' => null])->save();
    }

    /** Putuskan semua sesi login (driver sesi database). */
    public function revokeSessions(): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $this->id)->delete();
        }
    }

    /**
     * Alasan akun ini belum boleh dihapus sendiri, atau null kalau boleh.
     * Pesanan yang masih berjalan harus selesai dulu supaya tidak ada pesanan yatim.
     */
    public function selfDeleteBlocker(): ?string
    {
        if ($this->isStaff()) {
            return __('Akun admin dan roaster dihapus oleh admin lewat menu Pengguna.');
        }
        if ($this->orders()->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
            return __('Masih ada pesanan yang berjalan. Hapus akun setelah pesananmu selesai atau dibatalkan.');
        }

        return null;
    }

    /**
     * Hapus akun pembeli tanpa merusak riwayat toko: data pribadi dibuang,
     * pesanan dan ulasan tetap ada sebagai catatan tanpa nama.
     */
    public function deleteAccount(): void
    {
        DB::transaction(function () {
            $this->addresses()->delete();
            DB::table('cart_lines')->where('user_id', $this->id)->delete();
            DB::table('notices')->where('user_id', $this->id)->delete();
            $this->revokeSessions();

            $this->forceFill([
                'name' => __('Akun dihapus'),
                'email' => 'dihapus-'.$this->id.'@brule.invalid',
                'phone' => null,
                'password' => Str::random(40),
                'provider' => null,
                'provider_id' => null,
                'remember_token' => null,
            ])->save();
            $this->delete();
        });
    }

    /** Hapus akun staf: riwayat kerja tetap atas namanya, tapi email dibebaskan dan tidak bisa masuk lagi. */
    public function deleteStaff(): void
    {
        DB::transaction(function () {
            $this->revokeSessions();
            $this->forceFill([
                'email' => 'dihapus-'.$this->id.'@brule.invalid',
                'password' => Str::random(40),
                'provider' => null,
                'provider_id' => null,
                'remember_token' => null,
            ])->save();
            $this->delete();
        });
    }
}
