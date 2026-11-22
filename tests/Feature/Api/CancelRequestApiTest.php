<?php

use App\Models\Notice;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Ajukan pembatalan lewat API: pelanggan mengajukan (roasting sampai packing),
 * admin menyetujui atau menolak. Persetujuan tidak mengembalikan biji yang sudah disangrai.
 */
beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->roaster = User::where('email', 'roaster@brule.test')->first();
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->order = Order::place($this->customer, RoastProfile::first(), 200, 'v60', [
        'name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung',
    ]);
    $this->order->markPaid();
    $this->code = $this->order->order_code;
});

test('payload: antre -> can_request_cancel false; roasting -> true; sesudah diajukan -> false dan terisi', function () {
    Sanctum::actingAs($this->customer);

    $this->getJson("/api/orders/{$this->code}")->assertOk()
        ->assertJsonPath('can_request_cancel', false)
        ->assertJsonPath('can_cancel', true)
        ->assertJsonPath('cancel_requested_at', null);

    $this->order->update(['status' => 'roasting']);
    $this->getJson("/api/orders/{$this->code}")->assertOk()
        ->assertJsonPath('can_request_cancel', true)
        ->assertJsonPath('can_cancel', false);

    $this->postJson("/api/orders/{$this->code}/cancel-request", ['reason' => 'Salah pilih level sangrai'])
        ->assertOk()
        ->assertJsonPath('can_request_cancel', false)
        ->assertJsonPath('cancel_request_reason', 'Salah pilih level sangrai');

    expect($this->getJson("/api/orders/{$this->code}")->json('cancel_requested_at'))->toBeString();
    expect(Notice::where('user_id', $this->admin->id)->where('type', 'cancel')->exists())->toBeTrue();
});

test('pelanggan: ajukan batal dari status roasting, grinding dan packing; alasan boleh kosong', function () {
    Sanctum::actingAs($this->customer);

    foreach (['roasting', 'grinding', 'packing'] as $status) {
        Order::where('id', $this->order->id)->update(['status' => $status, 'cancel_requested_at' => null, 'cancel_request_reason' => null]);

        $this->postJson("/api/orders/{$this->code}/cancel-request", [])->assertOk()
            ->assertJsonPath('cancel_request_reason', null);
        expect($this->order->fresh()->cancel_requested_at)->not->toBeNull();
    }
});

test('pelanggan: ditolak 422 saat masih antre, sudah diajukan, sudah dikirim, selesai atau batal', function () {
    Sanctum::actingAs($this->customer);

    $this->postJson("/api/orders/{$this->code}/cancel-request", ['reason' => 'x'])->assertStatus(422); // queued: batal langsung

    $this->order->update(['status' => 'roasting']);
    $this->postJson("/api/orders/{$this->code}/cancel-request", ['reason' => 'x'])->assertOk();
    $this->postJson("/api/orders/{$this->code}/cancel-request", ['reason' => 'x'])->assertStatus(422); // dua kali

    foreach (['shipped', 'completed', 'cancelled'] as $status) {
        Order::where('id', $this->order->id)->update(['status' => $status, 'cancel_requested_at' => null]);
        $this->postJson("/api/orders/{$this->code}/cancel-request", ['reason' => 'x'])->assertStatus(422);
    }
});

test('pelanggan: alasan lebih dari 160 karakter ditolak validasi', function () {
    Sanctum::actingAs($this->customer);
    $this->order->update(['status' => 'roasting']);

    $this->postJson("/api/orders/{$this->code}/cancel-request", ['reason' => str_repeat('a', 161)])
        ->assertStatus(422)->assertJsonValidationErrors('reason');
});

test('hanya pemilik pesanan yang bisa mengajukan; tanpa login 401', function () {
    $this->order->update(['status' => 'roasting']);

    $this->postJson("/api/orders/{$this->code}/cancel-request", [])->assertUnauthorized();

    $other = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($other);
    $this->postJson("/api/orders/{$this->code}/cancel-request", ['reason' => 'x'])->assertNotFound();
    expect($this->order->fresh()->cancel_requested_at)->toBeNull();
});

test('/track tanpa login tidak membuka alasan dan tidak menawarkan aksi batal', function () {
    $this->order->update(['status' => 'roasting']);
    Sanctum::actingAs($this->customer);
    $this->postJson("/api/orders/{$this->code}/cancel-request", ['reason' => 'rahasia pelanggan'])->assertOk();
    $this->app['auth']->forgetGuards();

    $res = $this->getJson("/api/track/{$this->code}")->assertOk();
    expect($res->json('can_request_cancel'))->toBeFalse();
    expect($res->json('cancel_request_reason'))->toBeNull();
    expect($res->content())->not->toContain('rahasia pelanggan');
});

test('admin: detail staff memuat alasan dan can_resolve_cancel', function () {
    $this->order->update(['status' => 'roasting']);
    $this->order->requestCancel('Salah pilih level sangrai');

    Sanctum::actingAs($this->admin);
    $this->getJson("/api/staff/orders/{$this->code}")->assertOk()
        ->assertJsonPath('cancel_request_reason', 'Salah pilih level sangrai')
        ->assertJsonPath('can_resolve_cancel', true);

    Sanctum::actingAs($this->roaster); // roaster melihat info, tapi tidak boleh memutuskan
    $this->getJson("/api/staff/orders/{$this->code}")->assertOk()->assertJsonPath('can_resolve_cancel', false);
});

test('admin menyetujui: status batal, permintaan dibersihkan, biji sangrai tidak dikembalikan', function () {
    $this->order->update(['status' => 'roasting']);
    $this->order->requestCancel('Salah level');
    $coffee = $this->order->items()->first()->roastProfile->coffee;
    $stockBefore = $coffee->stock_green_bean_gram;

    Sanctum::actingAs($this->admin);
    $this->postJson("/api/staff/orders/{$this->code}/cancel-request/approve")
        ->assertOk()
        ->assertJsonPath('status', 'cancelled')
        ->assertJsonPath('cancel_requested_at', null)
        ->assertJsonPath('can_resolve_cancel', false)
        ->assertJsonPath('invoice_url', null);

    expect($this->order->fresh()->status)->toBe('cancelled');
    expect($coffee->fresh()->stock_green_bean_gram)->toBe($stockBefore);
    expect(Notice::where('user_id', $this->customer->id)->where('type', 'cancel')->exists())->toBeTrue();
});

test('admin menolak: pesanan lanjut, permintaan dihapus, pelanggan boleh mengajukan lagi', function () {
    $this->order->update(['status' => 'packing']);
    $this->order->requestCancel('Salah level');

    Sanctum::actingAs($this->admin);
    $this->postJson("/api/staff/orders/{$this->code}/cancel-request/reject")
        ->assertOk()
        ->assertJsonPath('status', 'packing')
        ->assertJsonPath('cancel_requested_at', null)
        ->assertJsonPath('cancel_request_reason', null);

    Sanctum::actingAs($this->customer);
    $this->getJson("/api/orders/{$this->code}")->assertJsonPath('can_request_cancel', true);
});

test('approve dan reject: khusus admin (roaster dan pelanggan 403), tanpa permintaan 422', function () {
    $this->order->update(['status' => 'roasting']);
    $this->order->requestCancel('x');

    foreach (['approve', 'reject'] as $act) {
        Sanctum::actingAs($this->roaster);
        $this->postJson("/api/staff/orders/{$this->code}/cancel-request/{$act}")->assertForbidden();
        Sanctum::actingAs($this->customer);
        $this->postJson("/api/staff/orders/{$this->code}/cancel-request/{$act}")->assertForbidden();
    }
    expect($this->order->fresh()->cancel_requested_at)->not->toBeNull();

    Sanctum::actingAs($this->admin);
    Order::where('id', $this->order->id)->update(['cancel_requested_at' => null]);
    $this->postJson("/api/staff/orders/{$this->code}/cancel-request/approve")->assertStatus(422);
    $this->postJson("/api/staff/orders/{$this->code}/cancel-request/reject")->assertStatus(422);
    $this->postJson('/api/staff/orders/TIDAKADA/cancel-request/approve')->assertNotFound();
});

test('detail staff memuat track_url ke /lacak/{kode} untuk tombol resi WA', function () {
    Sanctum::actingAs($this->admin);

    expect($this->getJson("/api/staff/orders/{$this->code}")->json('track_url'))
        ->toEndWith("/lacak/{$this->code}");
});
