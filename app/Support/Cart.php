<?php

namespace App\Support;

use App\Models\OrderItem;
use App\Models\Product;
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
                ->map(fn ($r) => [
                    'id' => $r->line_id, 'kind' => $r->kind ?? 'coffee', 'profile_id' => (int) $r->roast_profile_id,
                    'product_id' => (int) $r->product_id, 'option' => $r->option,
                    'blend_profile_id' => $r->blend_profile_id ? (int) $r->blend_profile_id : null, 'blend_ratio' => $r->blend_ratio ? (int) $r->blend_ratio : null,
                    'grams' => (int) $r->grams, 'grind' => (string) $r->grind, 'qty' => (int) $r->qty,
                ])
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
                    'user_id' => $user->id, 'line_id' => $l['id'], 'kind' => self::kind($l),
                    'roast_profile_id' => self::kind($l) === 'coffee' ? $l['profile_id'] : null,
                    'product_id' => self::kind($l) === 'product' ? $l['product_id'] : null,
                    'option' => $l['option'] ?? null,
                    'blend_profile_id' => $l['blend_profile_id'] ?? null, 'blend_ratio' => $l['blend_ratio'] ?? null,
                    'grams' => $l['grams'], 'grind' => $l['grind'] ?: 'whole_bean', 'qty' => self::qty($l),
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
                if (self::same($l, $g)) {
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

    public static function kind(array $l): string
    {
        return ($l['kind'] ?? 'coffee') === 'product' ? 'product' : 'coffee';
    }

    /** Dua baris dianggap sama bila kopi/produk, ukuran, gilingan atau pilihannya identik. */
    private static function same(array $a, array $b): bool
    {
        if (self::kind($a) !== self::kind($b)) {
            return false;
        }

        return self::kind($a) === 'product'
            ? ($a['product_id'] ?? 0) === ($b['product_id'] ?? 0) && ($a['option'] ?? null) === ($b['option'] ?? null)
            : $a['profile_id'] === $b['profile_id'] && $a['grams'] === $b['grams'] && $a['grind'] === $b['grind']
                && ($a['blend_profile_id'] ?? null) === ($b['blend_profile_id'] ?? null) && ($a['blend_ratio'] ?? null) === ($b['blend_ratio'] ?? null);
    }

    private static function qty(array $l): int
    {
        return max(1, min(self::MAX_QTY, (int) ($l['qty'] ?? 1)));
    }

    /** Tambah racikan. Dengan $blendProfileId + $ratio jadi blend dua biji (ratio = porsi biji pertama, %). */
    public static function add(int $profileId, int $grams, string $grind, ?int $blendProfileId = null, ?int $ratio = null): bool
    {
        if (! in_array($grams, self::GRAMS, true) || ! array_key_exists($grind, OrderItem::GRINDS)) {
            return false;
        }
        $a = RoastProfile::with('coffee')->find($profileId);
        if (! $a) {
            return false;
        }
        if ($blendProfileId !== null || $ratio !== null) {
            $b = $blendProfileId ? RoastProfile::with('coffee')->find($blendProfileId) : null;
            if (! $b || ! $ratio || ! Blend::validRatio($ratio) || ! Blend::compatible($a, $b)) {
                return false;
            }
        }

        $lines = self::raw();
        // Racikan yang sama persis digabung jadi qty, bukan baris baru.
        foreach ($lines as &$l) {
            if (self::kind($l) === 'coffee' && $l['profile_id'] === $profileId && $l['grams'] === $grams && $l['grind'] === $grind
                && ($l['blend_profile_id'] ?? null) === $blendProfileId && ($l['blend_ratio'] ?? null) === $ratio) {
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
        $lines[] = ['id' => Str::random(8), 'kind' => 'coffee', 'profile_id' => $profileId, 'product_id' => 0, 'option' => null, 'blend_profile_id' => $blendProfileId, 'blend_ratio' => $ratio, 'grams' => $grams, 'grind' => $grind, 'qty' => 1];
        self::write($lines);

        return true;
    }

    /** Tambah produk non-kopi. $option wajib salah satu varian bila produk punya varian. */
    public static function addProduct(int $productId, ?string $option = null): bool
    {
        $product = Product::active()->find($productId);
        if (! $product || ! $product->validOption($option)) {
            return false;
        }
        $option = $option === '' ? null : $option;

        $lines = self::raw();
        foreach ($lines as &$l) {
            if (self::kind($l) === 'product' && $l['product_id'] === $productId && ($l['option'] ?? null) === $option) {
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
        $lines[] = ['id' => Str::random(8), 'kind' => 'product', 'profile_id' => 0, 'product_id' => $productId, 'option' => $option, 'grams' => 0, 'grind' => '', 'qty' => 1];
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
        if ($i === false || self::kind($lines[$i]) !== 'coffee') {
            return;
        }
        if ($grams !== null && in_array($grams, self::GRAMS, true)) {
            $lines[$i]['grams'] = $grams;
        }
        if ($grind !== null && array_key_exists($grind, OrderItem::GRINDS)) {
            $lines[$i]['grind'] = $grind;
        }

        foreach ($lines as $j => $o) {
            if ($j !== $i && self::same($o, $lines[$i])) {
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
     * Baris keranjang beserta modelnya. Baris yang profil/produknya sudah dihapus
     * atau dimatikan dibuang diam-diam. Baris kopi punya `profile`, baris produk punya `product`.
     *
     * @return Collection<int,object{id:string,kind:string,profile:?RoastProfile,product:?Product,option:?string,grams:int,grind:string,qty:int,unit:int,price:int}>
     */
    public static function lines(array $raw = null): Collection
    {
        $raw ??= self::raw();
        $profiles = RoastProfile::with('coffee')->whereIn('id', collect($raw)->where('kind', '!=', 'product')->flatMap(fn ($l) => [$l['profile_id'], $l['blend_profile_id'] ?? null])->filter()->unique())->get()->keyBy('id');
        $products = Product::whereIn('id', collect($raw)->where('kind', 'product')->pluck('product_id'))->get()->keyBy('id');

        return collect($raw)->map(function ($l) use ($profiles, $products) {
            $qty = self::qty($l);

            if (self::kind($l) === 'product') {
                $pr = $products[$l['product_id']] ?? null;
                if (! $pr || ! $pr->is_active) {
                    return null;
                }

                return (object) [
                    'id' => $l['id'], 'kind' => 'product', 'profile' => null, 'blend' => null, 'ratio' => null, 'product' => $pr, 'option' => $l['option'] ?? null,
                    'grams' => 0, 'grind' => '', 'qty' => $qty, 'unit' => $pr->price, 'price' => $pr->price * $qty,
                ];
            }

            $p = $profiles[$l['profile_id']] ?? null;
            if (! $p || ! $p->coffee?->is_active) {
                return null;
            }

            $blend = null;
            $ratio = null;
            if (! empty($l['blend_profile_id'])) {
                $blend = $profiles[$l['blend_profile_id']] ?? null;
                $ratio = (int) ($l['blend_ratio'] ?? 0);
                if (! $blend || ! Blend::validRatio($ratio) || ! Blend::compatible($p, $blend)) {
                    return null;
                }
            }

            $unit = $blend ? Blend::price($p, $blend, (int) $l['grams'], $ratio) : $p->priceFor((int) $l['grams']);

            return (object) [
                'id' => $l['id'], 'kind' => 'coffee', 'profile' => $p, 'blend' => $blend, 'ratio' => $ratio, 'product' => null, 'option' => null,
                'grams' => (int) $l['grams'], 'grind' => $l['grind'], 'qty' => $qty, 'unit' => $unit, 'price' => $unit * $qty,
            ];
        })->filter()->values();
    }

    /** @return array<int,array{0:\App\Models\Coffee,1:int}> kopi dan gram sangrai yang dipakai satu bungkus baris ini */
    public static function coffeeGrams(object $l): array
    {
        if (! $l->blend) {
            return [[$l->profile->coffee, $l->grams]];
        }
        [$ga, $gb] = Blend::split($l->grams, $l->ratio);

        return [[$l->profile->coffee, $ga], [$l->blend->coffee, $gb]];
    }

    /** Pesan error bila stok biji mentah atau stok produk tidak cukup untuk seluruh isi keranjang. */
    public static function stockProblem(Collection $lines): ?string
    {
        // Kebutuhan biji mentah dijumlahkan per kopi; blend memecah gramnya ke dua kopi.
        $need = [];
        $names = [];
        foreach ($lines->where('kind', 'coffee') as $l) {
            foreach (self::coffeeGrams($l) as [$coffee, $g]) {
                $need[$coffee->id] = ($need[$coffee->id] ?? 0) + $coffee->greenBeanNeeded($g) * $l->qty;
                $names[$coffee->id] = $coffee;
            }
        }
        foreach ($need as $id => $gram) {
            if ($names[$id]->stock_green_bean_gram < $gram) {
                return __('Stok :n tidak cukup untuk jumlah yang kamu pilih.', ['n' => $names[$id]->name]);
            }
        }

        foreach ($lines->where('kind', 'product')->groupBy(fn ($l) => $l->product->id) as $group) {
            $product = $group->first()->product;
            if ($product->stock < $group->sum('qty')) {
                return __('Stok :n tidak cukup untuk jumlah yang kamu pilih.', ['n' => $product->localizedName()]);
            }
        }

        return null;
    }
}
