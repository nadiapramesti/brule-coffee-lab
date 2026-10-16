<?php

namespace Database\Seeders;

use App\Models\Coffee;
use Illuminate\Database\Seeder;

/**
 * Lima kopi tambahan (4 dari Indonesia + Kenya) supaya katalog tidak sepi.
 *
 * Profil rasa per level sangrai tidak ditulis satu-satu: tiap kopi punya
 * rasa dasar di sangrai light, lalu dihitung turun-naiknya. Makin gelap,
 * rasa terang (floral, citrus, berry) menyusut dan rasa berat (chocolate,
 * nutty, spice) naik. Pola ini yang bikin flavor wheel bergeser wajar
 * waktu slider digeser.
 *
 * Nama petani, koperasi, dan Q-grader di sini DATA CONTOH untuk demo.
 */
class MoreCoffeeSeeder extends Seeder
{
    /** level => [pengali rasa terang, tambahan rasa berat, agtron, menit drop, suhu drop, potongan harga] */
    private const SHIFT = [
        'light'        => [1.00,  0, 92, 10.4, 203,     0],
        'medium_light' => [0.88, 12, 78, 11.2, 208,  2_000],
        'medium'       => [0.72, 30, 65, 12.1, 214,  4_000],
        'medium_dark'  => [0.48, 50, 52, 13.0, 221,  6_000],
        'dark'         => [0.28, 62, 40, 13.8, 228,  8_000],
    ];

    public function run(): void
    {
        foreach ($this->data() as $row) {
            $coffee = Coffee::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name'                  => $row['name'],
                    'story'                 => $row['story'],
                    'story_en'              => $row['story_en'] ?? null,
                    'stock_green_bean_gram' => $row['stock'],
                    'cupping_score'         => $row['cupping'],
                    'is_active'             => true,
                ]
            );

            $coffee->traceability()->updateOrCreate(
                ['coffee_id' => $coffee->id],
                $row['traceability']
            );

            foreach ($row['levels'] as $level => $notes) {
                [$bright, $heavy, $agtron, $dropMin, $dropTemp, $cut] = self::SHIFT[$level];

                $values = [];
                foreach ($row['base'] as $axis => $v) {
                    $isBright = in_array($axis, ['floral', 'citrus', 'berry'], true);
                    $values[$axis] = (int) min(95, round($isBright ? $v * $bright : $v + $heavy));
                }

                $coffee->roastProfiles()->updateOrCreate(
                    ['level' => $level],
                    [
                        'agtron'         => $agtron,
                        'flavor_values'  => $values,
                        'tasting_notes'  => $notes,
                        'roast_curve'    => $this->curve($dropMin, $dropTemp),
                        'price_per_100g' => $row['price'] - $cut,
                    ]
                );
            }
        }
    }

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
            [
                'slug' => 'gayo-aceh-wet-hulled',
                'name' => 'Gayo Aceh Wet-Hulled',
                'story' => 'Ditanam petani kecil di sekitar Takengon, dataran tinggi '
                         . 'Gayo. Proses giling basah khas Sumatra memberi body tebal '
                         . 'dan karakter herbal manis yang tidak ada di kopi washed.',
                'story_en' => "Grown by smallholders around Takengon in the Gayo highlands. The Sumatran wet-hulled process gives a thick body and a sweet herbal character you won't find in washed coffees.",
                'stock' => 22_000, 'cupping' => 85.8, 'price' => 40_000,
                'traceability' => [
                    'farmer' => 'Kelompok Tani Bukit Pepanyi', 'region' => 'Takengon, Aceh Tengah, Indonesia',
                    'altitude' => 1400, 'varietal' => 'Gayo 1, Ateng Super', 'process' => 'Wet-Hulled',
                    'harvest_date' => '2026-02-10', 'q_grader' => 'D. Kurniawan (Q-Arabica #30412)',
                ],
                'base' => ['floral' => 40, 'citrus' => 34, 'berry' => 28, 'chocolate' => 40, 'nutty' => 34, 'spice' => 46],
                'levels' => [
                    'medium_light' => ['Brown Sugar', 'Herbal', 'Red Apple'],
                    'medium'       => ['Dark Chocolate', 'Cedar', 'Molasses'],
                    'medium_dark'  => ['Dark Cocoa', 'Tobacco', 'Caramelized Sugar'],
                    'dark'         => ['Bittersweet Chocolate', 'Smoke', 'Pipe Tobacco'],
                ],
            ],
            [
                'slug' => 'flores-bajawa-washed',
                'name' => 'Flores Bajawa Washed',
                'story' => 'Tumbuh di lereng Gunung Inerie, Bajawa, di tanah vulkanik '
                         . 'yang subur. Hasilnya kopi yang manis seperti karamel dengan '
                         . 'asam jeruk yang lembut. Cocok untuk yang baru mulai suka '
                         . 'kopi specialty.',
                'story_en' => 'Grown on the slopes of Mount Inerie in Bajawa, on fertile volcanic soil. The result is a caramel-sweet cup with soft citrus acidity. A good first step into specialty coffee.',
                'stock' => 15_000, 'cupping' => 85.5, 'price' => 46_000,
                'traceability' => [
                    'farmer' => 'Yohanes Nggoro', 'region' => 'Bajawa, Ngada, Flores, Indonesia',
                    'altitude' => 1500, 'varietal' => 'Typica, Catimor', 'process' => 'Washed',
                    'harvest_date' => '2026-06-22', 'q_grader' => 'D. Kurniawan (Q-Arabica #30412)',
                ],
                'base' => ['floral' => 58, 'citrus' => 66, 'berry' => 40, 'chocolate' => 28, 'nutty' => 36, 'spice' => 22],
                'levels' => [
                    'light'        => ['Lemon', 'Honey', 'Orange Blossom'],
                    'medium_light' => ['Caramel', 'Mandarin', 'Almond'],
                    'medium'       => ['Milk Chocolate', 'Caramel', 'Hazelnut'],
                    'medium_dark'  => ['Dark Caramel', 'Walnut', 'Cocoa'],
                ],
            ],
            [
                'slug' => 'toraja-sapan-wet-hulled',
                'name' => 'Toraja Sapan Wet-Hulled',
                'story' => 'Dari desa Sapan di pegunungan Tana Toraja. Kebunnya '
                         . 'dirawat turun-temurun dan dipanen dengan tangan. Rasanya '
                         . 'dalam dan berempah, dengan manis rempah yang lama tertinggal.',
                'story_en' => 'From the village of Sapan in the Tana Toraja mountains. The farms are tended across generations and picked by hand. The cup is deep and spiced, with a sweet spice that lingers.',
                'stock' => 12_000, 'cupping' => 85.2, 'price' => 44_000,
                'traceability' => [
                    'farmer' => 'Koperasi Kopi Sapan Mandiri', 'region' => 'Sapan, Tana Toraja, Indonesia',
                    'altitude' => 1600, 'varietal' => 'Typica, S795', 'process' => 'Wet-Hulled',
                    'harvest_date' => '2026-07-05', 'q_grader' => 'R. Mahendra (Q-Arabica #21884)',
                ],
                'base' => ['floral' => 36, 'citrus' => 30, 'berry' => 44, 'chocolate' => 42, 'nutty' => 30, 'spice' => 58],
                'levels' => [
                    'medium_light' => ['Dried Plum', 'Clove', 'Brown Sugar'],
                    'medium'       => ['Dark Chocolate', 'Cinnamon', 'Dried Fruit'],
                    'medium_dark'  => ['Bitter Cocoa', 'Black Pepper', 'Toasted Wood'],
                    'dark'         => ['Dark Roast', 'Clove', 'Smoky Cedar'],
                ],
            ],
            [
                'slug' => 'java-preanger-anaerobic-natural',
                'name' => 'Java Preanger Anaerobic Natural',
                'story' => 'Dari kebun di dataran Preanger, Jawa Barat. Ceri kopi '
                         . 'difermentasi dalam tangki tertutup tanpa oksigen sebelum '
                         . 'dijemur, sehingga rasa buahnya jauh lebih pekat dan '
                         . 'eksperimental dibanding kopi natural biasa.',
                'story_en' => 'From a farm in the Preanger highlands of West Java. The cherries ferment in sealed tanks without oxygen before drying, so the fruit flavors are far more intense and experimental than an ordinary natural.',
                'stock' => 8_000, 'cupping' => 86.9, 'price' => 54_000,
                'traceability' => [
                    'farmer' => 'Ujang Sudrajat', 'region' => 'Pangalengan, Bandung, Jawa Barat, Indonesia',
                    'altitude' => 1300, 'varietal' => 'Sigarar Utang, Lini S', 'process' => 'Anaerobic',
                    'harvest_date' => '2026-08-14', 'q_grader' => 'R. Mahendra (Q-Arabica #21884)',
                ],
                'base' => ['floral' => 52, 'citrus' => 44, 'berry' => 90, 'chocolate' => 24, 'nutty' => 14, 'spice' => 30],
                'levels' => [
                    'light'        => ['Strawberry', 'Red Wine', 'Rose'],
                    'medium_light' => ['Raspberry Jam', 'Cherry', 'Brown Sugar'],
                    'medium'       => ['Dark Berry', 'Cacao Nibs', 'Plum'],
                    'medium_dark'  => ['Dark Cherry', 'Cocoa', 'Rum Raisin'],
                ],
            ],
            [
                'slug' => 'kenya-aa-nyeri-washed',
                'name' => 'Kenya AA Nyeri Washed',
                'story' => 'Dari koperasi kecil di lereng Gunung Kenya, Nyeri. '
                         . 'Biji grade AA yang besar dan padat, dicuci dua kali. '
                         . 'Asamnya tajam seperti blackcurrant, kopi paling "terang" '
                         . 'di gudang kami.',
                'story_en' => 'From a small cooperative on the slopes of Mount Kenya, Nyeri. Large, dense AA-grade beans washed twice. The acidity is sharp like blackcurrant: the brightest coffee in our warehouse.',
                'stock' => 10_000, 'cupping' => 87.4, 'price' => 58_000,
                'traceability' => [
                    'farmer' => 'Mathenge Cooperative Society', 'region' => 'Nyeri, Central Province, Kenya',
                    'altitude' => 1800, 'varietal' => 'SL28, SL34', 'process' => 'Washed',
                    'harvest_date' => '2026-01-30', 'q_grader' => 'R. Mahendra (Q-Arabica #21884)',
                ],
                'base' => ['floral' => 60, 'citrus' => 88, 'berry' => 84, 'chocolate' => 16, 'nutty' => 10, 'spice' => 26],
                'levels' => [
                    'light'        => ['Blackcurrant', 'Grapefruit', 'Tomato Leaf'],
                    'medium_light' => ['Blackcurrant', 'Lime', 'Brown Sugar'],
                    'medium'       => ['Dark Berry', 'Orange', 'Molasses'],
                    'medium_dark'  => ['Dark Fruit', 'Cocoa', 'Toasted Sugar'],
                ],
            ],
        ];
    }
}
