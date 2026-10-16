<?php

use App\Livewire\Checkout;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use App\Support\Cart as Store;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->customer = User::where('email', 'customer@kromatik.test')->first();
    $this->admin = User::where('email', 'admin@kromatik.test')->first();
    $this->profile = RoastProfile::first();
    $this->ship = ['name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung 40111'];
});

function checkoutWith($t, string $payment) {
    Store::add($t->profile->id, 200, 'v60');

    return Livewire::actingAs($t->customer)->test(Checkout::class)
        ->set('name', 'Nadia')->set('phone', '081234567890')->set('address', $t->ship['address'])
        ->set('payment', $payment)->call('submit');
}

it('defaults to online payment, waiting for payment, and blocks printing until paid', function () {
    checkoutWith($this, 'online')->assertHasNoErrors();
    $o = Order::firstOrFail();

    expect($o->payment_method)->toBe('online')->and($o->status)->toBe('pending_payment')->and($o->canPrint())->toBeFalse();
    $this->actingAs($this->admin)->get(route('admin.orders.print', $o))->assertForbidden();

    $o->markPaid();
    $this->actingAs($this->admin)->get(route('admin.orders.print', $o->fresh()))
        ->assertOk()->assertSee('NON-COD')->assertSee('LUNAS')->assertDontSee('Tagihan dibayar penerima');
});

it('sends COD straight to the queue, prints with a COD bill, and counts as paid on completion', function () {
    checkoutWith($this, 'cod')->assertHasNoErrors();
    $o = Order::firstOrFail();

    expect($o->isCod())->toBeTrue()->and($o->status)->toBe('queued')->and($o->paid_at)->toBeNull()->and($o->canPrint())->toBeTrue();
    $this->actingAs($this->admin)->get(route('admin.orders.print', $o))
        ->assertOk()->assertSee('COD')->assertSee('Tagihan dibayar penerima')->assertSee(number_format($o->total, 0, ',', '.'));

    $o->advance(); $o->advance(); $o->advance();
    $o->ship('JNE123');
    $o->complete();

    expect($o->fresh()->paid_at)->not->toBeNull()->and($o->fresh()->status)->toBe('completed');
    $this->actingAs($this->admin)->get(route('admin.orders.print', $o))->assertSee('NON-COD')->assertSee('COD · Lunas');
});

it('rejects an unknown payment method and does not print cancelled orders', function () {
    checkoutWith($this, 'bitcoin')->assertHasErrors(['payment']);
    expect(Order::count())->toBe(0);

    $o = Order::place($this->customer, $this->profile, 200, 'v60', $this->ship);
    $o->cancelAndRestock();
    $this->actingAs($this->admin)->get(route('admin.orders.print', $o))->assertForbidden();
});

it('does not count COD revenue until the parcel is completed', function () {
    $o = Order::placeMany($this->customer, [['profile' => $this->profile, 'grams' => 200, 'grind' => 'v60']], $this->ship, null, 'cod');
    expect(Order::whereNotNull('paid_at')->count())->toBe(0);
    $o->advance(); $o->advance(); $o->advance(); $o->ship('JNE1'); $o->complete();
    expect(Order::whereNotNull('paid_at')->count())->toBe(1);
});
