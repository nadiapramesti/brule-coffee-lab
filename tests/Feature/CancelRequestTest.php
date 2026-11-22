<?php

use App\Livewire\Admin\Orders;
use App\Livewire\Track;
use App\Models\Notice;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->order = Order::place($this->customer, RoastProfile::first(), 200, 'v60', [
        'name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung',
    ]);
    $this->order->markPaid();
});

it('lets the customer request cancellation only once roasting has started', function () {
    expect($this->order->fresh()->canRequestCancel())->toBeFalse(); // masih antre: batal langsung

    $this->order->update(['status' => 'roasting']);
    expect($this->order->fresh()->requestCancel('Salah level'))->toBeTrue();

    $o = $this->order->fresh();
    expect($o->cancel_requested_at)->not->toBeNull()->and($o->cancel_request_reason)->toBe('Salah level')->and($o->status)->toBe('roasting');
    expect($o->requestCancel('lagi'))->toBeFalse(); // tidak bisa dua kali
    expect(Notice::where('user_id', $this->admin->id)->where('type', 'cancel')->exists())->toBeTrue();
});

it('shows the request form to the owner only', function () {
    $this->order->update(['status' => 'grinding']);

    Livewire::actingAs($this->customer)->test(Track::class, ['code' => $this->order->order_code])
        ->assertSee('Ajukan pembatalan')
        ->set('cancelWhy', 'Berubah pikiran')->call('requestCancel')
        ->assertSee('Permintaan pembatalan sudah dikirim');

    $other = User::where('email', 'raka@brule.test')->first();
    Livewire::actingAs($other)->test(Track::class, ['code' => $this->order->order_code])
        ->assertDontSee('Ajukan pembatalan')->call('requestCancel')->assertForbidden();
});

it('lets the admin approve (no bean restock) or reject the request', function () {
    $coffee = RoastProfile::first()->coffee;
    $this->order->update(['status' => 'roasting']);
    $this->order->requestCancel('Salah pilih');
    $stock = $coffee->fresh()->stock_green_bean_gram;

    Livewire::actingAs($this->admin)->test(Orders::class)->call('show', $this->order->order_code)
        ->assertSee('Pelanggan minta pesanan dibatalkan')->call('rejectCancelRequest');
    expect($this->order->fresh()->cancel_requested_at)->toBeNull()->and($this->order->fresh()->status)->toBe('roasting');

    $this->order->fresh()->requestCancel('Tetap mau batal');
    Livewire::actingAs($this->admin)->test(Orders::class)->call('show', $this->order->order_code)->call('approveCancelRequest');
    expect($this->order->fresh()->status)->toBe('cancelled')->and($coffee->fresh()->stock_green_bean_gram)->toBe($stock);
});

it('ignores requests for orders that already shipped', function () {
    $this->order->update(['status' => 'shipped']);
    expect($this->order->fresh()->requestCancel('x'))->toBeFalse();
});
