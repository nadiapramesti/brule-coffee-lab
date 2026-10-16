<?php

use App\Livewire\Checkout;
use App\Livewire\Roastery;
use App\Livewire\Track;
use App\Models\Coffee;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->customer = User::where('email', 'customer@kromatik.test')->first();
    $this->profile = RoastProfile::first();
});

function placeOrder($test, int $grams = 200) {
    return Livewire::actingAs($test->customer)
        ->withQueryParams(['p' => $test->profile->id, 'g' => $grams, 'grind' => 'v60'])
        ->test(Checkout::class)
        ->set('name', 'Nadia')->set('phone', '081234567890')
        ->set('address', 'Jl. Merdeka No. 10, Bandung 40111')
        ->call('submit');
}

it('requires login for checkout', function () {
    $this->get(route('checkout', ['p' => $this->profile->id]))->assertRedirect(route('login'));
});

it('creates an order with server-side price and decrements stock', function () {
    $coffee = $this->profile->coffee;
    $before = $coffee->stock_green_bean_gram;

    placeOrder($this);

    $order = Order::firstOrFail();
    expect($order->status)->toBe('pending_payment')
        ->and($order->subtotal)->toBe($this->profile->priceFor(200))
        ->and($order->total)->toBe($order->subtotal + Order::shippingFor($order->subtotal))
        ->and($coffee->fresh()->stock_green_bean_gram)->toBe($before - $coffee->greenBeanNeeded(200));
});

it('rejects orders when stock is insufficient', function () {
    $this->profile->coffee->update(['stock_green_bean_gram' => 10]);

    placeOrder($this)->assertSet('error', fn ($e) => filled($e));
    expect(Order::count())->toBe(0);
});

it('validates the phone number', function () {
    Livewire::actingAs($this->customer)
        ->withQueryParams(['p' => $this->profile->id])
        ->test(Checkout::class)
        ->set('phone', '123')->set('address', 'Jl. Merdeka No. 10, Bandung')
        ->call('submit')->assertHasErrors(['phone']);
});

it('pays, then cancelling after payment restocks only while still queued', function () {
    $coffee = $this->profile->coffee;
    $start = $coffee->stock_green_bean_gram;
    placeOrder($this);
    $order = Order::first();

    Livewire::actingAs($this->customer)->test(Track::class, ['code' => $order->order_code])->call('pay');
    expect($order->fresh()->status)->toBe('queued');

    Livewire::actingAs($this->customer)->test(Track::class, ['code' => $order->order_code])->call('cancel');
    expect($order->fresh()->status)->toBe('cancelled')
        ->and($coffee->fresh()->stock_green_bean_gram)->toBe($start);
});

it('does not let strangers pay or cancel someone else\'s order', function () {
    placeOrder($this);
    $order = Order::first();
    $other = User::where('email', 'raka@kromatik.test')->first();

    Livewire::actingAs($other)->test(Track::class, ['code' => $order->order_code])->call('pay')->assertForbidden();
});

it('keeps the roastery dashboard for roasters only', function () {
    $this->actingAs($this->customer)->get(route('roastery'))->assertForbidden();
    $this->actingAs(User::where('email', 'roaster@kromatik.test')->first())->get(route('roastery'))->assertOk();
});

it('moves an order through the queue and requires a tracking number to ship', function () {
    placeOrder($this);
    $order = Order::first();
    $order->markPaid();
    $roaster = User::where('email', 'roaster@kromatik.test')->first();

    $c = Livewire::actingAs($roaster)->test(Roastery::class);
    $c->call('advance', $order->id)->call('advance', $order->id)->call('advance', $order->id);
    expect($order->fresh()->status)->toBe('packing');

    $c->call('ship', $order->id)->assertHasErrors(["tracking.{$order->id}"]);
    $c->set("tracking.{$order->id}", 'JNE1234567')->call('ship', $order->id);

    $fresh = $order->fresh();
    expect($fresh->status)->toBe('shipped')->and($fresh->tracking_number)->toBe('JNE1234567')->and($fresh->roaster_id)->toBe($roaster->id);
});

it('shows the tracking page to anyone with the code', function () {
    placeOrder($this);
    $this->get(route('track', Order::first()->order_code))->assertOk()->assertSee('Menunggu Pembayaran');
});

it('sends each role to its own home from /dashboard', function () {
    $this->actingAs($this->customer)->get('/dashboard')->assertRedirect(route('track'));
    $this->actingAs(User::where('email', 'roaster@kromatik.test')->first())->get('/dashboard')->assertRedirect(route('roastery'));
});
