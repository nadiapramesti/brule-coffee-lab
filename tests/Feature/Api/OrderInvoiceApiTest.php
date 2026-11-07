<?php

use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Detail pesanan API: invoice_url (link bertanda tangan) dan can_cancel.
 * Aturan: invoice hanya untuk pesanan lunas/COD yang tidak batal; batal hanya di
 * pending_payment dan queued (dari roasting ke atas stok biji tidak bisa dikembalikan).
 */
beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->customer = User::where('email', 'customer@brule.test')->first();
    Sanctum::actingAs($this->customer);
});

function buatPesananApi($test, string $payment): string
{
    $res = $test->postJson('/api/orders', [
        'name' => 'Nadia',
        'phone' => '081234567890',
        'address' => 'Jl. Merdeka No. 10, Bandung 40111',
        'payment' => $payment,
        'items' => [['type' => 'coffee', 'qty' => 1, 'profile_id' => RoastProfile::first()->id, 'grams' => 200, 'grind' => 'v60']],
    ])->assertCreated();

    return $res->json('code');
}

test('pesanan COD: invoice_url terisi (bertanda tangan) dan can_cancel true', function () {
    $code = buatPesananApi($this, 'cod');

    $res = $this->getJson("/api/orders/{$code}")->assertOk();

    expect($res->json('can_cancel'))->toBeTrue();
    $url = $res->json('invoice_url');
    expect($url)->toBeString()->toContain("/invoice/{$code}")->toContain('signature=');
});

test('pesanan online belum dibayar: invoice_url null, tapi masih boleh dibatalkan', function () {
    $code = buatPesananApi($this, 'online');

    $res = $this->getJson("/api/orders/{$code}")->assertOk();

    expect($res->json('status'))->toBe('pending_payment');
    expect($res->json('invoice_url'))->toBeNull();
    expect($res->json('can_cancel'))->toBeTrue();
});

test('pesanan online yang sudah lunas mendapat invoice_url', function () {
    $code = buatPesananApi($this, 'online');
    Order::where('order_code', $code)->update(['paid_at' => now(), 'status' => 'queued']);

    expect($this->getJson("/api/orders/{$code}")->json('invoice_url'))->toBeString()->toContain("/invoice/{$code}");
});

test('batal saat antri berhasil: stok kembali, invoice_url jadi null, can_cancel false', function () {
    $code = buatPesananApi($this, 'cod');

    $res = $this->postJson("/api/orders/{$code}/cancel")->assertOk();

    expect($res->json('status'))->toBe('cancelled');
    expect($res->json('can_cancel'))->toBeFalse();
    expect($res->json('invoice_url'))->toBeNull();
});

test('mulai roasting sampai selesai: can_cancel false dan server menolak batal (422)', function () {
    foreach (['roasting', 'grinding', 'packing', 'shipped', 'completed'] as $status) {
        $code = buatPesananApi($this, 'cod');
        Order::where('order_code', $code)->update(['status' => $status]);

        expect($this->getJson("/api/orders/{$code}")->json('can_cancel'))->toBeFalse("status {$status}");
        $this->postJson("/api/orders/{$code}/cancel")->assertStatus(422);
        expect(Order::where('order_code', $code)->value('status'))->toBe($status);
    }
});

test('lacak lewat kode tanpa login tidak pernah membocorkan invoice_url', function () {
    $code = buatPesananApi($this, 'cod');
    app('auth')->forgetGuards();

    $res = $this->getJson("/api/track/{$code}")->assertOk();

    expect($res->json('invoice_url'))->toBeNull();
    expect($res->json('can_cancel'))->toBeFalse();
});
