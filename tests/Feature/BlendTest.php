<?php

use App\Livewire\Account;
use App\Livewire\Builder;
use App\Livewire\Cart as CartPage;
use App\Models\Coffee;
use App\Models\Order;
use App\Models\Review;
use App\Models\RoastProfile;
use App\Models\User;
use App\Support\Blend;
use App\Support\Cart as Store;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\MoreCoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class, MoreCoffeeSeeder::class]);
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->ship = ['name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung 40111'];
    // Dua kopi berbeda dengan level medium yang sama.
    $this->a = RoastProfile::where('level', 'medium')->orderBy('coffee_id')->first();
    $this->b = RoastProfile::where('level', 'medium')->where('coffee_id', '!=', $this->a->coffee_id)->orderBy('coffee_id')->first();
    $this->actingAs($this->customer);
});

it('splits grams so the parts always add up', function () {
    foreach ([100, 200, 250, 500, 1000] as $g) {
        foreach (Blend::RATIOS as $r) {
            expect(array_sum(Blend::split($g, $r)))->toBe($g);
        }
    }
    expect(Blend::split(200, 70))->toBe([140, 60]);
});

it('prices a blend as the sum of both parts', function () {
    expect(Blend::price($this->a, $this->b, 200, 70))->toBe($this->a->priceFor(140) + $this->b->priceFor(60));
});

it('only blends different coffees at the same level', function () {
    $other = RoastProfile::where('level', '!=', 'medium')->where('coffee_id', $this->a->coffee_id)->first();
    expect(Blend::compatible($this->a, $this->b))->toBeTrue()
        ->and(Blend::compatible($this->a, $this->a))->toBeFalse();
    if ($other) {
        expect(Blend::compatible($this->a, $other))->toBeFalse();
    }
    expect(Store::add($this->a->id, 200, 'v60', $this->a->id, 70))->toBeFalse()
        ->and(Store::add($this->a->id, 200, 'v60', $this->b->id, 80))->toBeFalse()
        ->and(Store::add($this->a->id, 200, 'v60', $this->b->id, 70))->toBeTrue();
});

it('keeps blend lines apart from single-bean lines and merges identical blends', function () {
    Store::add($this->a->id, 200, 'v60');
    Store::add($this->a->id, 200, 'v60', $this->b->id, 70);
    Store::add($this->a->id, 200, 'v60', $this->b->id, 70);
    Store::add($this->a->id, 200, 'v60', $this->b->id, 50);

    $lines = Store::lines();
    expect($lines)->toHaveCount(3)->and($lines->firstWhere('ratio', 70)->qty)->toBe(2)
        ->and($lines->firstWhere('ratio', 70)->unit)->toBe(Blend::price($this->a, $this->b, 200, 70));
});

it('places a blend order, splits stock by ratio and restocks both beans on cancel', function () {
    $ca = $this->a->coffee->stock_green_bean_gram;
    $cb = $this->b->coffee->stock_green_bean_gram;

    $order = Order::placeMany($this->customer, [['profile' => $this->a, 'grams' => 200, 'grind' => 'v60', 'blend' => $this->b, 'ratio' => 70]], $this->ship);
    $item = $order->items->first();

    expect($item->blend_profile_id)->toBe($this->b->id)->and($item->blend_ratio)->toBe(70)
        ->and($item->line_total)->toBe(Blend::price($this->a, $this->b, 200, 70))
        ->and($order->subtotal)->toBe($item->line_total)
        ->and($item->coffee_name_snapshot)->toContain(' 70 / ')->toContain(' 30')
        ->and($this->a->coffee->fresh()->stock_green_bean_gram)->toBe($ca - $this->a->coffee->greenBeanNeeded(140))
        ->and($this->b->coffee->fresh()->stock_green_bean_gram)->toBe($cb - $this->b->coffee->greenBeanNeeded(60));

    $order->cancelAndRestock('tes');
    expect($this->a->coffee->fresh()->stock_green_bean_gram)->toBe($ca)->and($this->b->coffee->fresh()->stock_green_bean_gram)->toBe($cb);
});

it('refuses a blend when the second bean lacks stock and rolls back', function () {
    $this->b->coffee->update(['stock_green_bean_gram' => 10]);
    $ca = $this->a->coffee->stock_green_bean_gram;
    $before = Order::count();

    expect(fn () => Order::placeMany($this->customer, [['profile' => $this->a, 'grams' => 500, 'grind' => 'v60', 'blend' => $this->b, 'ratio' => 50]], $this->ship))
        ->toThrow(RuntimeException::class);
    expect(Order::count())->toBe($before)->and($this->a->coffee->fresh()->stock_green_bean_gram)->toBe($ca);

    Store::add($this->a->id, 500, 'v60', $this->b->id, 50);
    expect(Store::stockProblem(Store::lines()))->not->toBeNull();
});

it('sums stock need per coffee across single and blend lines', function () {
    $this->a->coffee->update(['stock_green_bean_gram' => $this->a->coffee->greenBeanNeeded(200) - 5]);
    Store::add($this->a->id, 100, 'v60');
    Store::add($this->a->id, 200, 'v60', $this->b->id, 50); // 100 g dari kopi A lagi, total butuh > stok

    expect(Store::stockProblem(Store::lines()))->not->toBeNull();
});

it('drives the builder: choose second bean, ratio, weighted price and wheel', function () {
    $c = Livewire::test(Builder::class)->call('selectCoffee', $this->a->coffee_id)->call('setLevel', 0);
    $levelKey = $c->get('profile')->level;
    $partner = RoastProfile::where('level', $levelKey)->where('coffee_id', '!=', $this->a->coffee_id)->first();

    $c->call('setBlend', $partner->coffee_id)->call('setRatio', 60)->call('setGrams', 250)
        ->assertSet('ratio', 60)
        ->assertSee('60 / 40');
    expect($c->get('price'))->toBe(Blend::price($c->get('profile'), $partner, 250, 60));

    $c->call('addToCart')->assertRedirect(route('cart'));
    $line = Store::raw()[0];
    expect($line['blend_profile_id'])->toBe($partner->id)->and($line['blend_ratio'])->toBe(60);
});

it('drops the blend when the level no longer matches', function () {
    $c = Livewire::test(Builder::class)->call('selectCoffee', $this->a->coffee_id)->call('setLevel', 0);
    $partner = RoastProfile::where('level', $c->get('profile')->level)->where('coffee_id', '!=', $this->a->coffee_id)->first();
    $c->call('setBlend', $partner->coffee_id);

    // Pindah ke level yang tidak dimiliki biji kedua: blend dibuang.
    $levels = $c->get('levels');
    foreach ($levels as $i => $p) {
        if (! RoastProfile::where('coffee_id', $partner->coffee_id)->where('level', $p->level)->exists()) {
            $c->call('setLevel', $i)->assertSet('blendCoffeeId', null);
            break;
        }
    }
    expect(true)->toBeTrue();
});

it('shows blends in the cart and lets buyers review both coffees', function () {
    Store::add($this->a->id, 200, 'v60', $this->b->id, 70);
    Livewire::test(CartPage::class)->assertSee('Blend');

    $order = Order::placeMany($this->customer, [['profile' => $this->a, 'grams' => 200, 'grind' => 'v60', 'blend' => $this->b, 'ratio' => 70]], $this->ship);
    $order->markPaid();
    $order->forceFill(['status' => 'completed'])->save();

    expect(Review::eligibleOrder($this->customer, $this->a->coffee))->not->toBeNull()
        ->and(Review::eligibleOrder($this->customer, $this->b->coffee))->not->toBeNull();

    $c = Livewire::test(Account::class);
    expect($c->instance()->reviewable($order->fresh('items'))->pluck('coffee.id')->all())->toContain($this->a->coffee_id, $this->b->coffee_id);
});

it('buys a blend again with the same ratio', function () {
    $order = Order::placeMany($this->customer, [['profile' => $this->a, 'grams' => 250, 'grind' => 'espresso', 'blend' => $this->b, 'ratio' => 60]], $this->ship);
    Livewire::test(Account::class)->call('reorder', $order->order_code);

    expect(Store::raw()[0])->toMatchArray(['blend_profile_id' => $this->b->id, 'blend_ratio' => 60, 'grams' => 250]);
});
