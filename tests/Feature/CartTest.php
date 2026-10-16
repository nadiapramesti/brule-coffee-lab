<?php

use App\Livewire\Builder;
use App\Livewire\Cart;
use App\Livewire\Checkout;
use App\Models\Coffee;
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
    $this->profiles = RoastProfile::with('coffee')->get();
    $this->p1 = $this->profiles->first();
    $this->p2 = $this->profiles->first(fn ($p) => $p->coffee_id !== $this->p1->coffee_id);
    $this->ship = ['name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung 40111'];
});

it('adds the builder selection to the cart without trusting a price', function () {
    Livewire::test(Builder::class)->call('addToCart')->assertRedirect(route('cart'));

    expect(Store::count())->toBe(1);
    expect(collect(Store::raw()[0])->keys()->all())->not->toContain('price');
});

it('shows cart lines and removes one', function () {
    Store::add($this->p1->id, 200, 'v60');
    Store::add($this->p2->id, 500, 'espresso');

    $c = Livewire::test(Cart::class)->assertSee($this->p1->coffee->name)->assertSee($this->p2->coffee->name);
    $id = Store::raw()[0]['id'];
    $c->call('remove', $id);

    expect(Store::count())->toBe(1);
});

it('rejects bad grams, grind, unknown profile and over-limit lines', function () {
    expect(Store::add($this->p1->id, 300, 'v60'))->toBeFalse()
        ->and(Store::add($this->p1->id, 200, 'nope'))->toBeFalse()
        ->and(Store::add(999999, 200, 'v60'))->toBeFalse();

    foreach (array_slice(array_keys(\App\Models\OrderItem::GRINDS), 0, 6) as $grind) {
        foreach ([200, 500] as $g) {
            Store::add($this->p1->id, $g, $grind);
        }
    }
    expect(Store::raw())->toHaveCount(Store::MAX_LINES)
        ->and(Store::add($this->p2->id, 200, 'v60'))->toBeFalse();
});

it('places one order with several items, one shipping fee, and decrements each stock', function () {
    $s1 = $this->p1->coffee->stock_green_bean_gram;
    $s2 = $this->p2->coffee->stock_green_bean_gram;
    Store::add($this->p1->id, 200, 'v60');
    Store::add($this->p2->id, 500, 'espresso');

    Livewire::actingAs($this->customer)->test(Checkout::class)
        ->set('name', 'Nadia')->set('phone', '081234567890')->set('address', $this->ship['address'])
        ->call('submit');

    $o = Order::with('items')->firstOrFail();
    expect(Order::count())->toBe(1)
        ->and($o->items)->toHaveCount(2)
        ->and($o->subtotal)->toBe($this->p1->priceFor(200) + $this->p2->priceFor(500))
        ->and($o->total)->toBe($o->subtotal + Order::shippingFor($o->subtotal))
        ->and($this->p1->coffee->fresh()->stock_green_bean_gram)->toBe($s1 - $this->p1->coffee->greenBeanNeeded(200))
        ->and($this->p2->coffee->fresh()->stock_green_bean_gram)->toBe($s2 - $this->p2->coffee->greenBeanNeeded(500))
        ->and(Store::count())->toBe(0)
        ->and($o->itemSummary())->toContain('+1');
});

it('sums same-coffee lines before checking stock and rolls back on shortage', function () {
    $coffee = $this->p1->coffee;
    $coffee->update(['stock_green_bean_gram' => $coffee->greenBeanNeeded(1000) + 10]);

    expect(fn () => Order::placeMany($this->customer, [
        ['profile' => $this->p1, 'grams' => 1000, 'grind' => 'v60'],
        ['profile' => $this->p1, 'grams' => 200, 'grind' => 'v60'],
    ], $this->ship))->toThrow(RuntimeException::class);

    expect(Order::count())->toBe(0)
        ->and($coffee->fresh()->stock_green_bean_gram)->toBe($coffee->greenBeanNeeded(1000) + 10);
});

it('restocks every item when a multi-item order is cancelled', function () {
    $s1 = $this->p1->coffee->stock_green_bean_gram;
    $s2 = $this->p2->coffee->stock_green_bean_gram;
    $o = Order::placeMany($this->customer, [
        ['profile' => $this->p1, 'grams' => 200, 'grind' => 'v60'],
        ['profile' => $this->p2, 'grams' => 200, 'grind' => 'v60'],
    ], $this->ship);

    $o->cancelAndRestock();

    expect($this->p1->coffee->fresh()->stock_green_bean_gram)->toBe($s1)
        ->and($this->p2->coffee->fresh()->stock_green_bean_gram)->toBe($s2);
});

it('applies a voucher to the whole cart subtotal', function () {
    $v = \App\Models\Voucher::create(['code' => 'CART10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);
    $o = Order::placeMany($this->customer, [
        ['profile' => $this->p1, 'grams' => 200, 'grind' => 'v60'],
        ['profile' => $this->p2, 'grams' => 200, 'grind' => 'v60'],
    ], $this->ship, 'cart10');

    expect($o->discount)->toBe((int) round($o->subtotal * 0.10))->and($o->voucher_code)->toBe('CART10');
});

it('sends an empty checkout back to the cart and gates checkout behind login', function () {
    $this->get(route('checkout'))->assertRedirect(route('login'));
    $this->actingAs($this->customer)->get(route('checkout'))->assertRedirect(route('cart'));
});


it('merges identical racikan into qty and caps qty', function () {
    foreach (range(1, Store::MAX_QTY) as $i) {
        expect(Store::add($this->p1->id, 200, 'v60'))->toBeTrue();
    }
    expect(Store::raw())->toHaveCount(1)->and(Store::count())->toBe(Store::MAX_QTY)
        ->and(Store::add($this->p1->id, 200, 'v60'))->toBeFalse();
});

it('changes qty, weight and grind in the cart and merges lines that become identical', function () {
    Store::add($this->p1->id, 200, 'v60');
    Store::add($this->p1->id, 500, 'v60');
    [$a, $b] = array_column(Store::raw(), 'id');

    $c = Livewire::test(Cart::class)->call('step', $a, 1)->call('step', $a, 1)->call('step', $a, -1);
    expect(Store::lines()->firstWhere('id', $a)->qty)->toBe(2);

    $c->call('setGrams', $b, 200);
    expect(Store::raw())->toHaveCount(1)->and(Store::count())->toBe(3);

    $c->call('setGrind', Store::raw()[0]['id'], 'espresso')->call('setGrams', Store::raw()[0]['id'], 300);
    expect(Store::raw()[0]['grind'])->toBe('espresso')->and(Store::raw()[0]['grams'])->toBe(200);
});

it('expands qty into one order item per pack with correct totals and stock', function () {
    $before = $this->p1->coffee->stock_green_bean_gram;
    Store::add($this->p1->id, 200, 'v60');
    Store::add($this->p1->id, 200, 'v60');
    Store::add($this->p1->id, 200, 'v60');

    Livewire::actingAs($this->customer)->test(Checkout::class)
        ->set('name', 'Nadia')->set('phone', '081234567890')->set('address', $this->ship['address'])
        ->call('submit');

    $o = Order::with('items')->firstOrFail();
    expect($o->items)->toHaveCount(3)
        ->and($o->subtotal)->toBe($this->p1->priceFor(200) * 3)
        ->and($this->p1->coffee->fresh()->stock_green_bean_gram)->toBe($before - $this->p1->coffee->greenBeanNeeded(200) * 3);
});

it('blocks checkout in the cart when qty exceeds stock', function () {
    $coffee = $this->p1->coffee;
    $coffee->update(['stock_green_bean_gram' => $coffee->greenBeanNeeded(200) * 2]);
    foreach (range(1, 3) as $i) {
        Store::add($this->p1->id, 200, 'v60');
    }

    expect(Livewire::test(Cart::class)->instance()->problem)->not->toBeNull();
});
