<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Produk non-kopi demo + satu pesanan hanya-produk (lewati sangrai & giling). Aman dijalankan ulang. */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Slug lama mug enamel dipindah ke mug hitam matte supaya tidak muncul dua mug.
        Product::where('slug', 'mug-enamel-brule')->update(['slug' => 'mug-matte-black-brule']);

        $products = [
            ['hario-v60-dripper-02', 'Hario V60 Dripper 02', 'Hario V60 Dripper 02', 'brewer', 'Dripper keramik untuk 1–4 cangkir. Aliran stabil, mudah dibersihkan.', 'Ceramic dripper for 1–4 cups. Steady flow, easy to clean.', 185000, 24, 'Warna', ['Putih', 'Hitam']],
            ['aeropress-original', 'AeroPress Original', 'AeroPress Original', 'brewer', 'Seduh cepat 1–2 menit, ringan dibawa. Hasil bersih dan manis.', 'Brews in 1–2 minutes and packs light. Clean, sweet cups.', 520000, 12, null, null],
            ['french-press-600', 'French Press 600 ml', 'French Press 600 ml', 'brewer', 'Gelas borosilikat dan rangka stainless, muat 3–4 cangkir.', 'Borosilicate glass in a stainless frame, serves 3–4 cups.', 240000, 3, null, null],
            ['hand-grinder-burr', 'Penggiling Manual Burr', 'Manual Burr Grinder', 'grinder', 'Burr baja 38 mm, 20 klik penyetelan dari espresso sampai French press.', '38 mm steel burrs, 20 click settings from espresso to French press.', 890000, 8, null, null],
            ['kertas-filter-v60', 'Kertas Filter V60 (100 lembar)', 'V60 Paper Filters (100 sheets)', 'filter', 'Kertas putih tanpa rasa kertas, pas untuk dripper 02.', 'White, taste-free paper that fits the 02 dripper.', 55000, 60, null, null],
            ['timbangan-seduh', 'Timbangan Seduh 0,1 g', 'Brew Scale 0.1 g', 'accessory', 'Timer bawaan, tahan cipratan, baterai isi ulang USB-C.', 'Built-in timer, splash resistant, USB-C rechargeable.', 410000, 15, null, null],
            ['gooseneck-kettle', 'Gooseneck Kettle 600 ml', 'Gooseneck Kettle 600 ml', 'accessory', 'Cerat ramping untuk tuang terkontrol. Cocok untuk semua kompor dan induksi.', 'Slim spout for controlled pouring. Works on any stove and induction.', 450000, 0, null, null],
            ['kaos-brule', 'Kaos BRÛLÉ', 'BRÛLÉ T-shirt', 'merch', 'Katun combed 24s, logo B kromatogram bordir hitam di dada kiri. Potongan unisex.', 'Combed cotton 24s with the black B chromatogram logo embroidered on the left chest. Unisex cut.', 175000, 30, 'Ukuran', ['S', 'M', 'L', 'XL']],
            ['tote-bag-brule', 'Tote Bag Kanvas', 'Canvas Tote Bag', 'merch', 'Kanvas tebal warna natural, muat 2 kantong kopi dan satu dripper. Logo B kromatogram hitam di tengah.', 'Heavy natural canvas, fits two coffee bags and a dripper. Black B chromatogram logo at the center.', 95000, 40, null, null],
            ['mug-matte-black-brule', 'Mug Matte Black Ceramic 300 ml Model C', 'Matte Black Ceramic Mug 300 ml Model C', 'merch', 'Keramik hitam matte bergagang kotak, logo B kromatogram putih di samping.', 'Matte black ceramic with a square handle and the white B chromatogram logo on the side.', 85000, 4, null, null],
        ];

        $images = [
            'hario-v60-dripper-02' => '/products/v60-putih-final.jpg',
            'aeropress-original' => '/products/aeropress-final.jpg',
            'french-press-600' => '/products/french-press-final.jpg',
            'hand-grinder-burr' => '/products/grinder-final.jpg',
            'kertas-filter-v60' => '/products/filter-final.jpg',
            'timbangan-seduh' => '/products/timbangan-final.jpg',
            'gooseneck-kettle' => '/products/kettle-final.jpg',
            'kaos-brule' => '/products/kaos-brule-final.jpg',
            'tote-bag-brule' => '/products/tote-brule-final.jpg',
            'mug-matte-black-brule' => '/products/mug-brule-black-final.jpg',
        ];
        // Foto per pilihan: dripper hitam punya foto sendiri.
        $optionImages = ['hario-v60-dripper-02' => ['Putih' => '/products/v60-putih-final.jpg', 'Hitam' => '/products/v60-hitam-final.jpg']];

        foreach ($products as [$slug, $name, $nameEn, $cat, $desc, $descEn, $price, $stock, $label, $options]) {
            Product::updateOrCreate(['slug' => $slug], [
                'image' => $images[$slug] ?? null, 'option_images' => $optionImages[$slug] ?? null,
                'name' => $name, 'name_en' => $nameEn, 'category' => $cat,
                'description' => $desc, 'description_en' => $descEn,
                'price' => $price, 'stock' => $stock,
                'option_label' => $label, 'options' => $options, 'is_active' => true,
            ]);
        }

        $this->demoOrder();
    }

    private function demoOrder(): void
    {
        $user = User::where('email', 'raka@brule.test')->first();
        if (! $user) {
            return;
        }

        $at = now()->subDays(1)->setTime(10, 20);
        $order = Order::firstOrNew(['order_code' => sprintf('BRL-%s-%04d', $at->format('Ymd'), 50)]);
        $order->items()->delete();

        $lines = [['hario-v60-dripper-02', 'Putih'], ['kertas-filter-v60', null], ['kertas-filter-v60', null]];
        $subtotal = 0;
        $rows = [];
        foreach ($lines as [$slug, $option]) {
            $p = Product::where('slug', $slug)->firstOrFail();
            $subtotal += $p->price;
            $rows[] = [$p, $option];
        }

        $order->forceFill([
            'user_id' => $user->id, 'status' => 'queued', 'payment_method' => 'online',
            'subtotal' => $subtotal, 'shipping_cost' => 0, 'addon_cost' => 0, 'total' => $subtotal,
            'recipient_name' => $user->name, 'recipient_phone' => $user->phone,
            'shipping_address' => 'Jl. Contoh No. 50, Kota Contoh',
            'paid_at' => $at->copy()->addMinutes(6), 'created_at' => $at, 'updated_at' => $at,
        ])->save();

        foreach ($rows as [$p, $option]) {
            OrderItem::create([
                'order_id' => $order->id, 'roast_profile_id' => null, 'product_id' => $p->id,
                'grams' => 0, 'grind' => null,
                'coffee_name_snapshot' => $p->name, 'roast_level_snapshot' => (string) $option,
                'price_per_100g_snapshot' => $p->price, 'line_total' => $p->price,
            ]);
        }
    }
}
