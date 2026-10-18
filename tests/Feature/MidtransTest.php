<?php

use App\Livewire\Track;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->customer = User::where('email', 'customer@kromatik.test')->first();
    $this->order = Order::place($this->customer, RoastProfile::first(), 200, 'v60', ['name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung 40111']);
    config(['services.midtrans.server_key' => 'SB-Mid-server-test', 'services.midtrans.client_key' => 'SB-Mid-client-test']);
});

function notif(Order $o, string $status, array $over = []): array
{
    $p = array_merge([
        'order_id' => $o->midtrans_order_id, 'status_code' => '200', 'gross_amount' => number_format((int) $o->total, 2, '.', ''),
        'transaction_status' => $status, 'fraud_status' => 'accept', 'payment_type' => 'bank_transfer',
    ], $over);
    $p['signature_key'] = hash('sha512', $p['order_id'] . $p['status_code'] . $p['gross_amount'] . 'SB-Mid-server-test');

    return $p;
}

it('requests a snap token once and reuses it', function () {
    Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'tok-123', 'redirect_url' => 'x'], 201)]);

    $t = Livewire::actingAs($this->customer)->test(Track::class, ['code' => $this->order->order_code]);
    $t->call('startPayment')->assertDispatched('snap-pay', token: 'tok-123');
    $t->call('startPayment');

    Http::assertSentCount(1);
    Http::assertSent(fn ($r) => $r['transaction_details']['gross_amount'] === (int) $this->order->total && str_starts_with($r['transaction_details']['order_id'], $this->order->order_code));
    expect($this->order->fresh()->midtrans_order_id)->not->toBeNull();
});

it('shows a friendly message when the gateway fails', function () {
    Http::fake(['*' => Http::response(['error_messages' => ['Unauthorized']], 401)]);

    Livewire::actingAs($this->customer)->test(Track::class, ['code' => $this->order->order_code])
        ->call('startPayment')->assertSet('payNotice', fn ($v) => str_starts_with($v, __('Gateway pembayaran sedang tidak bisa dihubungi. Coba lagi sebentar lagi.')))->assertNotDispatched('snap-pay');
});

it('disables the simulated pay button once Midtrans is configured', function () {
    Livewire::actingAs($this->customer)->test(Track::class, ['code' => $this->order->order_code])->call('pay')->assertForbidden();
    expect($this->order->fresh()->status)->toBe('pending_payment');

    config(['services.midtrans.server_key' => null]);
    Livewire::actingAs($this->customer)->test(Track::class, ['code' => $this->order->order_code])->call('pay');
    expect($this->order->fresh()->status)->toBe('queued');
});

it('marks the order paid from a signed webhook', function () {
    $this->order->forceFill(['midtrans_order_id' => $this->order->order_code . '-abcde'])->save();

    $this->postJson('/midtrans/notification', notif($this->order->fresh(), 'settlement'))->assertOk();

    $o = $this->order->fresh();
    expect($o->status)->toBe('queued')->and($o->paid_at)->not->toBeNull()->and($o->payment_channel)->toBe('bank_transfer');
});

it('rejects a webhook with a bad signature or the wrong amount', function () {
    $this->order->forceFill(['midtrans_order_id' => $this->order->order_code . '-abcde'])->save();
    $o = $this->order->fresh();

    $bad = notif($o, 'settlement');
    $bad['signature_key'] = 'x';
    $this->postJson('/midtrans/notification', $bad)->assertForbidden();

    $this->postJson('/midtrans/notification', notif($o, 'settlement', ['gross_amount' => '1000.00']))->assertOk();
    expect($this->order->fresh()->status)->toBe('pending_payment');
});

it('ignores pending and clears the token on expiry so the buyer can retry', function () {
    $this->order->forceFill(['snap_token' => 'tok', 'midtrans_order_id' => $this->order->order_code . '-abcde'])->save();
    $o = $this->order->fresh();

    $this->postJson('/midtrans/notification', notif($o, 'pending'))->assertOk();
    expect($this->order->fresh()->status)->toBe('pending_payment')->and($this->order->fresh()->snap_token)->toBe('tok');

    $this->postJson('/midtrans/notification', notif($o, 'expire'))->assertOk();
    expect($this->order->fresh()->snap_token)->toBeNull();
});

it('syncs payment status from the Midtrans status API', function () {
    $mid = $this->order->order_code . '-abcde';
    $this->order->forceFill(['snap_token' => 'tok', 'midtrans_order_id' => $mid])->save();
    $o = $this->order->fresh();
    Http::fake(['api.sandbox.midtrans.com/*' => Http::response(notif($o, 'settlement') + ['status_message' => 'ok'])]);

    Livewire::actingAs($this->customer)->test(Track::class, ['code' => $this->order->order_code])->call('syncPayment');

    expect($this->order->fresh()->status)->toBe('queued');
});

it('only lets the owner start or sync a payment, and 404s the webhook when not configured', function () {
    $other = User::where('email', 'raka@kromatik.test')->first();
    Livewire::actingAs($other)->test(Track::class, ['code' => $this->order->order_code])->call('startPayment')->assertForbidden();

    config(['services.midtrans.server_key' => null, 'services.midtrans.client_key' => null]);
    $this->postJson('/midtrans/notification', [])->assertNotFound();
});
