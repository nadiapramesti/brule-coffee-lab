<?php

namespace App\Support;

use App\Models\OrderItem;
use App\Models\RoastProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Keranjang berbasis session (jadi tamu pun bisa mengisi sebelum login).
 * Yang disimpan hanya id profil, berat, dan gilingan; harga selalu dihitung
 * ulang dari database setiap kali dibaca.
 */
class Cart
{
    public const KEY = 'krm.cart';
    public const MAX_LINES = 10;
    public const GRAMS = [200, 500, 1000];

    /** @return array<int,array{id:string,profile_id:int,grams:int,grind:string}> */
    public static function raw(): array
    {
        return array_values(array_filter((array) session(self::KEY, []), fn ($l) => is_array($l) && isset($l['id'], $l['profile_id'], $l['grams'], $l['grind'])));
    }

    public static function count(): int
    {
        return count(self::raw());
    }

    public static function add(int $profileId, int $grams, string $grind): bool
    {
        if (! in_array($grams, self::GRAMS, true) || ! array_key_exists($grind, OrderItem::GRINDS)) {
            return false;
        }
        if (! RoastProfile::whereKey($profileId)->exists() || self::count() >= self::MAX_LINES) {
            return false;
        }

        $lines = self::raw();
        $lines[] = ['id' => Str::random(8), 'profile_id' => $profileId, 'grams' => $grams, 'grind' => $grind];
        session([self::KEY => $lines]);

        return true;
    }

    public static function remove(string $id): void
    {
        session([self::KEY => array_values(array_filter(self::raw(), fn ($l) => $l['id'] !== $id))]);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Baris keranjang beserta modelnya. Baris yang profilnya sudah dihapus
     * atau kopinya dimatikan dibuang diam-diam.
     *
     * @return Collection<int,object{id:string,profile:RoastProfile,grams:int,grind:string,price:int}>
     */
    public static function lines(array $raw = null): Collection
    {
        $raw ??= self::raw();
        $profiles = RoastProfile::with('coffee')->whereIn('id', collect($raw)->pluck('profile_id'))->get()->keyBy('id');

        return collect($raw)->map(function ($l) use ($profiles) {
            $p = $profiles[$l['profile_id']] ?? null;
            if (! $p || ! $p->coffee?->is_active) {
                return null;
            }

            return (object) [
                'id' => $l['id'], 'profile' => $p, 'grams' => (int) $l['grams'],
                'grind' => $l['grind'], 'price' => $p->priceFor((int) $l['grams']),
            ];
        })->filter()->values();
    }

    /** Pesan error bila stok biji mentah tidak cukup untuk seluruh isi keranjang (digabung per kopi). */
    public static function stockProblem(Collection $lines): ?string
    {
        foreach ($lines->groupBy(fn ($l) => $l->profile->coffee_id) as $group) {
            $coffee = $group->first()->profile->coffee;
            $need = $group->sum(fn ($l) => $coffee->greenBeanNeeded($l->grams));
            if ($coffee->stock_green_bean_gram < $need) {
                return __('Stok :n tidak cukup untuk jumlah yang kamu pilih.', ['n' => $coffee->name]);
            }
        }

        return null;
    }
}
