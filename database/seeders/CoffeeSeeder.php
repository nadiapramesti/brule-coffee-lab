<?php

namespace Database\Seeders;

use App\Models\Coffee;
use Illuminate\Database\Seeder;

class CoffeeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $row) {
            $coffee = Coffee::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name'                  => $row['name'],
                    'story'                 => $row['story'],
                    'stock_green_bean_gram' => $row['stock_green_bean_gram'],
                    'cupping_score'         => $row['cupping_score'],
                    'is_active'             => true,
                ]
            );

            $coffee->traceability()->updateOrCreate(
                ['coffee_id' => $coffee->id],
                $row['traceability']
            );

            foreach ($row['roast_profiles'] as $profile) {
                $coffee->roastProfiles()->updateOrCreate(
                    ['level' => $profile['level']],
                    $profile
                );
            }
        }
    }

    /**
     * Kurva sangrai disusun dari titik-titik penting roasting:
     * charge (masuk) -> turning point -> drying -> maillard
     * -> first crack -> development -> drop (keluar).
     */
    private function curve(float $dropMinute, int $dropTemp): array
    {
        return [
            ['t' => 0.0,  'temp' => 200, 'phase' => 'charge'],
            ['t' => 1.3,  'temp' => 92,  'phase' => 'turning_point'],
            ['t' => 4.0,  'temp' => 140, 'phase' => 'drying'],
            ['t' => 7.0,  'temp' => 170, 'phase' => 'maillard'],
            ['t' => 9.2,  'temp' => 196, 'phase' => 'first_crack'],
            ['t' => $dropMinute, 'temp' => $dropTemp, 'phase' => 'drop'],
        ];
    }

    private function data(): array
    {
        return [
            // ============================================================
            // 1. ETHIOPIA YIRGACHEFFE - WASHED
            // ============================================================
            [
                'slug'  => 'ethiopia-yirgacheffe-washed',
                'name'  => 'Ethiopia Yirgacheffe Washed',
                'story' => 'Dipetik dari kebun kecil di dataran tinggi Yirgacheffe, '
                         . 'Gedeo. Proses washed-nya mencuci bersih lapisan '
                         . 'gula buah, menyisakan karakter asli biji: floral, '
                         . 'bersih, dan terang seperti teh. Ini kopi yang '
                         . 'paling jujur menunjukkan asal-usulnya.',
                'stock_green_bean_gram' => 18_000,
                'cupping_score'         => 88.2,
                'traceability' => [
                    'farmer'       => 'Tadesse Bekele',
                    'region'       => 'Yirgacheffe, Gedeo, Ethiopia',
                    'altitude'     => 2100,
                    'varietal'     => 'Heirloom (Kurume, Dega)',
                    'process'      => 'Washed',
                    'harvest_date' => '2026-01-18',
                    'q_grader'     => 'R. Mahendra (Q-Arabica #21884)',
                ],
                'roast_profiles' => [
                    [
                        'level'  => 'light',
                        'agtron' => 92,
                        'flavor_values' => [
                            'floral' => 92, 'citrus' => 85, 'berry' => 74,
                            'chocolate' => 18, 'nutty' => 12, 'spice' => 20,
                        ],
                        'tasting_notes'  => ['Jasmine', 'Blueberry', 'Bergamot', 'White Tea'],
                        'roast_curve'    => $this->curve(10.4, 203),
                        'price_per_100g' => 62_000,
                    ],
                    [
                        'level'  => 'medium_light',
                        'agtron' => 78,
                        'flavor_values' => [
                            'floral' => 78, 'citrus' => 80, 'berry' => 82,
                            'chocolate' => 30, 'nutty' => 22, 'spice' => 24,
                        ],
                        'tasting_notes'  => ['Blueberry', 'Jasmine', 'Lemon Zest', 'Honey'],
                        'roast_curve'    => $this->curve(11.2, 208),
                        'price_per_100g' => 60_000,
                    ],
                    [
                        'level'  => 'medium',
                        'agtron' => 65,
                        'flavor_values' => [
                            'floral' => 55, 'citrus' => 60, 'berry' => 70,
                            'chocolate' => 52, 'nutty' => 40, 'spice' => 32,
                        ],
                        'tasting_notes'  => ['Dark Berry', 'Milk Chocolate', 'Orange Peel'],
                        'roast_curve'    => $this->curve(12.1, 214),
                        'price_per_100g' => 58_000,
                    ],
                    [
                        'level'  => 'medium_dark',
                        'agtron' => 52,
                        'flavor_values' => [
                            'floral' => 30, 'citrus' => 34, 'berry' => 50,
                            'chocolate' => 72, 'nutty' => 60, 'spice' => 46,
                        ],
                        'tasting_notes'  => ['Cocoa', 'Dried Fruit', 'Brown Sugar'],
                        'roast_curve'    => $this->curve(13.0, 221),
                        'price_per_100g' => 56_000,
                    ],
                ],
            ],

            // ============================================================
            // 2. COLOMBIA HUILA - HONEY
            // ============================================================
            [
                'slug'  => 'colombia-huila-honey',
                'name'  => 'Colombia Huila Honey',
                'story' => 'Huila adalah lembah vulkanik dengan siang hangat '
                         . 'dan malam dingin. Proses honey menyisakan sebagian '
                         . 'lendir buah menempel saat dijemur, menghasilkan '
                         . 'manis karamel yang tebal tanpa kehilangan '
                         . 'keasaman jeruknya.',
                'stock_green_bean_gram' => 24_000,
                'cupping_score'         => 86.5,
                'traceability' => [
                    'farmer'       => 'Finca La Esperanza - Jose Hernandez',
                    'region'       => 'Pitalito, Huila, Colombia',
                    'altitude'     => 1750,
                    'varietal'     => 'Caturra, Castillo',
                    'process'      => 'Honey',
                    'harvest_date' => '2026-02-05',
                    'q_grader'     => 'A. Lesmana (Q-Arabica #19233)',
                ],
                'roast_profiles' => [
                    [
                        'level'  => 'light',
                        'agtron' => 90,
                        'flavor_values' => [
                            'floral' => 48, 'citrus' => 82, 'berry' => 55,
                            'chocolate' => 30, 'nutty' => 34, 'spice' => 22,
                        ],
                        'tasting_notes'  => ['Orange', 'Green Apple', 'Cane Sugar'],
                        'roast_curve'    => $this->curve(10.6, 204),
                        'price_per_100g' => 48_000,
                    ],
                    [
                        'level'  => 'medium_light',
                        'agtron' => 76,
                        'flavor_values' => [
                            'floral' => 40, 'citrus' => 74, 'berry' => 50,
                            'chocolate' => 50, 'nutty' => 48, 'spice' => 28,
                        ],
                        'tasting_notes'  => ['Caramel', 'Orange', 'Almond'],
                        'roast_curve'    => $this->curve(11.5, 209),
                        'price_per_100g' => 47_000,
                    ],
                    [
                        'level'  => 'medium',
                        'agtron' => 62,
                        'flavor_values' => [
                            'floral' => 26, 'citrus' => 58, 'berry' => 44,
                            'chocolate' => 70, 'nutty' => 64, 'spice' => 38,
                        ],
                        'tasting_notes'  => ['Caramel', 'Milk Chocolate', 'Orange Peel', 'Toffee'],
                        'roast_curve'    => $this->curve(12.4, 215),
                        'price_per_100g' => 45_000,
                    ],
                    [
                        'level'  => 'medium_dark',
                        'agtron' => 50,
                        'flavor_values' => [
                            'floral' => 14, 'citrus' => 36, 'berry' => 32,
                            'chocolate' => 84, 'nutty' => 76, 'spice' => 52,
                        ],
                        'tasting_notes'  => ['Dark Caramel', 'Roasted Almond', 'Cocoa'],
                        'roast_curve'    => $this->curve(13.2, 222),
                        'price_per_100g' => 44_000,
                    ],
                    [
                        'level'  => 'dark',
                        'agtron' => 42,
                        'flavor_values' => [
                            'floral' => 8, 'citrus' => 20, 'berry' => 20,
                            'chocolate' => 90, 'nutty' => 80, 'spice' => 64,
                        ],
                        'tasting_notes'  => ['Dark Chocolate', 'Molasses', 'Smoke'],
                        'roast_curve'    => $this->curve(14.1, 230),
                        'price_per_100g' => 43_000,
                    ],
                ],
            ],

            // ============================================================
            // 3. SUMATRA MANDHELING - NATURAL
            // ============================================================
            [
                'slug'  => 'sumatra-mandheling-natural',
                'name'  => 'Sumatra Mandheling Natural',
                'story' => 'Dari lereng Bukit Barisan, Sumatera Utara. '
                         . 'Dijemur utuh bersama buahnya selama berminggu-minggu, '
                         . 'menghasilkan body paling tebal di antara ketiganya. '
                         . 'Earthy, rempah, dan berat — karakter Indonesia '
                         . 'yang tidak bisa ditiru daerah lain.',
                'stock_green_bean_gram' => 21_000,
                'cupping_score'         => 85.0,
                'traceability' => [
                    'farmer'       => 'Koperasi Tani Lintong Nihuta',
                    'region'       => 'Lintong, Sumatera Utara, Indonesia',
                    'altitude'     => 1450,
                    'varietal'     => 'Ateng, Sigararutang',
                    'process'      => 'Natural',
                    'harvest_date' => '2026-03-02',
                    'q_grader'     => 'B. Wicaksono (Q-Arabica #20117)',
                ],
                'roast_profiles' => [
                    [
                        'level'  => 'medium_light',
                        'agtron' => 74,
                        'flavor_values' => [
                            'floral' => 22, 'citrus' => 30, 'berry' => 62,
                            'chocolate' => 56, 'nutty' => 50, 'spice' => 66,
                        ],
                        'tasting_notes'  => ['Tropical Fruit', 'Cedar', 'Brown Spice'],
                        'roast_curve'    => $this->curve(11.8, 210),
                        'price_per_100g' => 41_000,
                    ],
                    [
                        'level'  => 'medium',
                        'agtron' => 60,
                        'flavor_values' => [
                            'floral' => 14, 'citrus' => 22, 'berry' => 52,
                            'chocolate' => 72, 'nutty' => 62, 'spice' => 78,
                        ],
                        'tasting_notes'  => ['Dark Chocolate', 'Tobacco', 'Clove', 'Earthy'],
                        'roast_curve'    => $this->curve(12.7, 216),
                        'price_per_100g' => 39_000,
                    ],
                    [
                        'level'  => 'medium_dark',
                        'agtron' => 48,
                        'flavor_values' => [
                            'floral' => 8, 'citrus' => 14, 'berry' => 38,
                            'chocolate' => 86, 'nutty' => 72, 'spice' => 84,
                        ],
                        'tasting_notes'  => ['Bittersweet Cocoa', 'Cedar', 'Black Pepper'],
                        'roast_curve'    => $this->curve(13.6, 224),
                        'price_per_100g' => 38_000,
                    ],
                    [
                        'level'  => 'dark',
                        'agtron' => 40,
                        'flavor_values' => [
                            'floral' => 4, 'citrus' => 8, 'berry' => 22,
                            'chocolate' => 94, 'nutty' => 78, 'spice' => 88,
                        ],
                        'tasting_notes'  => ['Dark Chocolate', 'Smoke', 'Molasses', 'Earth'],
                        'roast_curve'    => $this->curve(14.5, 232),
                        'price_per_100g' => 37_000,
                    ],
                ],
            ],
        ];
    }
}
