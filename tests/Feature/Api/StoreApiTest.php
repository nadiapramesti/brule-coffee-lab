<?php

use App\Models\Setting;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;

/** Info toko publik: ongkir/sisa gratis ongkir untuk keranjang app, dan nomor WhatsApp toko. */
beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    Setting::put(['shipping_flat' => '15000']);
    Setting::put(['free_shipping_from' => '500000']);
});

test('free_shipping_gap: sisa belanja, 0 saat tepat di batas atau lebih', function () {
    $this->getJson('/api/cart/shipping?subtotal=0')->assertOk()
        ->assertJsonPath('free_shipping_gap', 500000)->assertJsonPath('shipping', 15000)->assertJsonPath('free_shipping_from', 500000);

    $this->getJson('/api/cart/shipping?subtotal=120000')->assertOk()
        ->assertJsonPath('free_shipping_gap', 380000)->assertJsonPath('shipping', 15000);

    $this->getJson('/api/cart/shipping?subtotal=499999')->assertJsonPath('free_shipping_gap', 1)->assertJsonPath('shipping', 15000);
    $this->getJson('/api/cart/shipping?subtotal=500000')->assertJsonPath('free_shipping_gap', 0)->assertJsonPath('shipping', 0);
    $this->getJson('/api/cart/shipping?subtotal=900000')->assertJsonPath('free_shipping_gap', 0)->assertJsonPath('shipping', 0);
});

test('free_shipping_gap 0 bila promo gratis ongkir dimatikan (batas 0)', function () {
    Setting::put(['free_shipping_from' => '0']);

    $this->getJson('/api/cart/shipping?subtotal=10000')->assertOk()
        ->assertJsonPath('free_shipping_gap', 0)->assertJsonPath('shipping', 15000)->assertJsonPath('free_shipping_from', 0);
});

test('mengikuti perubahan pengaturan admin', function () {
    Setting::put(['free_shipping_from' => '300000']);

    $this->getJson('/api/cart/shipping?subtotal=100000')->assertJsonPath('free_shipping_gap', 200000);
});

test('subtotal wajib: kosong, negatif atau bukan angka ditolak 422', function () {
    $this->getJson('/api/cart/shipping')->assertStatus(422)->assertJsonValidationErrors('subtotal');
    $this->getJson('/api/cart/shipping?subtotal=-5')->assertStatus(422);
    $this->getJson('/api/cart/shipping?subtotal=abc')->assertStatus(422);
});

test('rumus yang sama dengan web: Order::freeShippingGap', function () {
    expect(\App\Models\Order::freeShippingGap(200000))->toBe(300000)
        ->and(\App\Models\Order::freeShippingGap(500000))->toBe(0);
});

test('/store: nomor WhatsApp toko dari pengaturan, hanya angka', function () {
    Setting::put(['contact_whatsapp' => '6281234567890']);

    $this->getJson('/api/store')->assertOk()
        ->assertJsonPath('whatsapp', '6281234567890')
        ->assertJsonPath('name', 'BRÛLÉ');

    Setting::put(['contact_whatsapp' => '+62 812-3456-7890']);
    $this->getJson('/api/store')->assertJsonPath('whatsapp', '6281234567890');
});

test('/api/coffees tetap bisa dibuka tanpa login (regresi: sisa conflict marker)', function () {
    $this->getJson('/api/coffees')->assertOk()->assertJsonStructure(['data' => [['slug', 'profiles', 'trace']]]);
    $slug = $this->getJson('/api/coffees')->json('data.0.slug');
    $this->getJson("/api/coffees/{$slug}")->assertOk()->assertJsonPath('slug', $slug);
});
