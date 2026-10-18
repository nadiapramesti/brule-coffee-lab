<?php

namespace App\Support;

use App\Models\OrderItem;
use App\Models\RoastProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Keranjang. Tamu: disimpan di session. User yang login: disimpan di tabel
 * cart_lines, jadi tidak hilang saat session habis atau ganti perangkat; isi
 * keranjang tamu digabung ke akun saat login (lihat mergeGuestInto).
 * Yang disimpan hanya id profil, berat, gilingan, dan qty; harga selalu
 * dihitung ulang dari database setiap kali dibaca.
 */
class Cart
{
    public const KEY = 'krm.cart';
    public const MAX_LINES = 10;
    public const MAX_QTY = 10;
    /** Ukuran bungkus yang boleh dipesan (gram). Satu-satunya sumber: builder, keranjang, dan checkout membaca daftar ini. */
    public const GRAMS = [100, 200, 250, 500, 1000];

    /** @return array<int,array{id:string,profile_id:int,grams:int,grind:string,qty:int}> */
    public static function raw(?User $user = null): array
    {
        $user ??= auth()->user();

        if ($user) {
            return DB::table('cart_lines')->where('user_id', $user->id)->orderBy('id')->get()
                ->map(fn ($r) => ['id' => $r->line_id, 'profile_id' => (int) $r->roast_profile_id, 'grams' => (int) $r->grams, 'grind' => $r->grind, 'qty' => (int) $r->qty])
                ->all();
        }

        return array_values(array_filter((array) session(self::KEY, []), fn ($l) => is_array($l) && isset($l['id'], $l['profile_id'], $l['grams'], $l['grind'])));
    }

    private static function write(array $lines, ?User $user = null): void
    {
        $user ??= auth()->user();
        $lines = array_values($lines);

        if (! $user) {
            session([self::KEY => $lines]);

            return;
        }

        DB::transaction(function () use ($user, $lines) {
            DB::table('cart_lines')->where('user_id', $user->id)->delete();
            foreach ($lines as $l) {
                DB::table('cart_lines')->insert([
                    'user_id' => $user->id, 'line_id' => $l['id'], 'roast_profile_id' => $l['profile_id'],
                    'grams' => $l['grams'], 'grind' => $l['grind'], 'qty' => self::qty($l),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    /** Dipanggil saat login: pindahkan keranjang tamu (session) ke keranjang akun. */
    public static function mergeGuestInto(User $user): void
    {
        $guest = array_values(array_filter((array) session(self::KEY, []), fn ($l) => is_array($l) && isset($l['id'], $l['profile_id'], $l['grams'], $l['grind'])));
        if ($guest === []) {
            return;
        }

        $lines = self::raw($user);
        foreach ($guest as $g) {
            foreach ($lines as &$l) {
                if ($l['profile_id'] === $g['profile_id'] && $l['grams'] === $g['grams'] && $l['grind'] === $g['grind']) {
                    $l['qty'] = min(self::MAX_QTY, self::qty($l) + self::qty($g));
                    continue 2;
                }
            }
            unset($l);
            if (count($lines) < self::MAX_LINES) {
                $lines[] = $g;
            }
        }

        self::write($lines, $user);
        session()->forget(self::KEY);
    }

    /** Jumlah bungkus (qty dijumlahkan), dipakai badge di header. */
    public static function count(): int
    {
        return (int) array_sum(array_map(fn ($l) => self::qty($l), self::raw()));
    }

    private static function qty(array $l): int
    {
        return max(1, min(self::MAX_QTY, (int) ($l['qty'] ?? 1)));
    }

    public static function add(int $profileId, int $grams, string $grind): bool
    {
        if (! in_array($grams, self::GRAMS, true) || ! array_key_exists($grind, OrderItem::GRINDS)) {
            return false;
        }
        if (! RoastProfile::whereKey($profileId)->exists()) {
            return false;
        }

        $lines = self::raw();
        // Racikan yang sama persis digabung jadi qty, bukan baris baru.
        foreach ($lines as &$l) {
            if ($l['profile_id'] === $profileId && $l['grams'] === $grams && $l['grind'] === $grind) {
                if (self::qty($l) >= self::MAX_QTY) {
                    return false;
                }
                $l['qty'] = self::qty($l) + 1;
                self::write($lines);

                return true;
            }
        }
        unset($l);

        if (count($lines) >= self::MAX_LINES) {
            return false;
        }
        $lines[] = ['id' => Str::random(8), 'profile_id' => $profileId, 'grams' => $grams, 'grind' => $grind, 'qty' => 1];
        self::write($lines);

        return true;
    }

    public static function setQty(string $id, int $qty): void
    {
        $qty = max(1, min(self::MAX_QTY, $qty));
        self::write(array_map(fn ($l) => $l['id'] === $id ? [...$l, 'qty' => $qty] : $l, self::raw()));
    }

    /** Ganti ukuran / gilingan satu baris. Kalau jadi sama dengan baris lain, keduanya digabung. */
    public static function change(string $id, ?int $grams, ?string $grind): void
    {
        $lines = self::raw();
        $i = array_search($id, array_column($lines, 'id'), true);
        if ($i === false) {
            return;
        }
        if ($grams !== null && in_array($grams, self::GRAMS, true)) {
            $lines[$i]['grams'] = $grams;
        }
        if ($grind !== null && array_key_exists($grind, OrderItem::GRINDS)) {
            $lines[$i]['grind'] = $grind;
        }

        foreach ($lines as $j => $o) {
            if ($j !== $i && $o['profile_id'] === $lines[$i]['profile_id'] && $o['grams'] === $lines[$i]['grams'] && $o['grind'] === $lines[$i]['grind']) {
                $lines[$j]['qty'] = min(self::MAX_QTY, self::qty($o) + self::qty($lines[$i]));
                unset($lines[$i]);
                break;
            }
        }
        self::write(array_values($lines));
    }

    public static function remove(string $id): void
    {
        self::write(array_values(array_filter(self::raw(), fn ($l) => $l['id'] !== $id)));
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
        if ($user = auth()->user()) {
            DB::table('cart_lines')->where('user_id', $user->id)->delete();
        }
    }

    /**
     * Baris keranjang beserta modelnya. Baris yang profilnya sudah dihapus
     * atau kopinya dimatikan dibuang diam-diam.
     *
     * @return Collection<int,object{id:string,profile:RoastProfile,grams:int,grind:string,qty:int,unit:int,price:int}>
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

            $qty = self::qty($l);
            $unit = $p->priceFor((int) $l['grams']);

            return (object) [
                'id' => $l['id'], 'profile' => $p, 'grams' => (int) $l['grams'],
                'grind' => $l['grind'], 'qty' => $qty, 'unit' => $unit, 'price' => $unit * $qty,
            ];
        })->filter()->values();
    }

    /** Pesan error bila stok biji mentah tidak cukup untuk seluruh isi keranjang (digabung per kopi). */
    public static function stockProblem(Collection $lines): ?string
    {
        foreach ($lines->groupBy(fn ($l) => $l->profile->coffee_id) as $group) {
            $coffee = $group->first()->profile->coffee;
            $need = $group->sum(fn ($l) => $coffee->greenBeanNeeded($l->grams) * $l->qty);
            if ($coffee->stock_green_bean_gram < $need) {
                return __('Stok :n tidak cukup untuk jumlah yang kamu pilih.', ['n' => $coffee->name]);
            }
        }

        return null;
    }
}
