<?php

namespace App\Support;

use App\Models\RoastProfile;

/** Aturan blend dua biji: porsi biji pertama (%), pembagian gram, harga berbobot, dan nama. */
class Blend
{
    /** Porsi biji pertama yang boleh dipilih; sisanya biji kedua. */
    public const RATIOS = [70, 60, 50];

    public static function validRatio(int $ratio): bool
    {
        return in_array($ratio, self::RATIOS, true);
    }

    /** @return array{0:int,1:int} gram biji pertama dan kedua; jumlahnya selalu sama dengan $grams. */
    public static function split(int $grams, int $ratio): array
    {
        $a = (int) round($grams * $ratio / 100);

        return [$a, $grams - $a];
    }

    /** Dua profil boleh diblend bila kopinya beda, aktif, dan levelnya sama (sangrai bareng). */
    public static function compatible(RoastProfile $a, RoastProfile $b): bool
    {
        return $a->coffee_id !== $b->coffee_id && $a->level === $b->level && $a->coffee?->is_active && $b->coffee?->is_active;
    }

    public static function price(RoastProfile $a, RoastProfile $b, int $grams, int $ratio): int
    {
        [$ga, $gb] = self::split($grams, $ratio);

        return $a->priceFor($ga) + $b->priceFor($gb);
    }

    public static function name(RoastProfile $a, RoastProfile $b, int $ratio): string
    {
        return $a->coffee->shortName().' '.$ratio.' / '.$b->coffee->shortName().' '.(100 - $ratio);
    }
}
