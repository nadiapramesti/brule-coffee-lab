<?php

use App\Livewire\Admin\CoffeeForm;
use App\Livewire\Admin\StoreSettings;
use App\Livewire\Builder;
use App\Livewire\Checkout;
use App\Models\Coffee;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\Setting;
use App\Models\User;
use App\Support\Cart as Store;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\MoreCoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->profile = RoastProfile::first();
    $this->actingAs($this->customer);
    $this->ship = ['name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung 40111'];
    $this->line = fn (int $g = 200) => (int) ($this->profile->price_per_100g * $g / 100);
});

function place($t, array $extras = [], ?string $voucher = null, string $pay = 'online', int $g = 200): Order {
    return Order::placeMany($t->customer, [['profile' => $t->profile, 'grams' => $g, 'grind' => 'v60']], $t->ship, $voucher, $pay, $extras);
}

it('adds gift wrap and card prices to the total and stores them on the order', function () {
    $o = place($this, ['gift_wrap' => true, 'card_message' => 'Selamat ulang tahun!']);

    expect($o->gift_wrap)->toBeTrue()
        ->and($o->card_message)->toBe('Selamat ulang tahun!')
        ->and($o->addon_cost)->toBe(20_000)
        ->and($o->total)->toBe(($this->line)() + $o->shipping_cost + 20_000);
});

it('charges only for the add-on that was picked', function () {
    expect(place($this, ['gift_wrap' => true])->addon_cost)->toBe(15_000)
        ->and(place($this, ['card_message' => 'Halo'])->addon_cost)->toBe(5_000)
        ->and(place($this)->addon_cost)->toBe(0);
});

it('cleans and truncates the card message', function () {
    $o = place($this, ['card_message' => "  <b>Hai</b>\n\n" . str_repeat('x', 300)]);

    expect(mb_strlen($o->card_message))->toBeLessThanOrEqual(Order::CARD_MAX)
        ->and($o->card_message)->not->toContain('<b>')
        ->and($o->card_message)->not->toContain("\n");
});

it('ignores add-ons when the store switches them off', function () {
    Setting::put(['addons_enabled' => '0']);
    $o = place($this, ['gift_wrap' => true, 'card_message' => 'Halo']);

    expect($o->addon_cost)->toBe(0)->and($o->gift_wrap)->toBeFalse()->and($o->card_message)->toBeNull();
});

it('keeps old orders at their original add-on price after settings change', function () {
    $o = place($this, ['gift_wrap' => true]);
    Setting::put(['addon_gift_wrap' => '99000']);

    expect($o->fresh()->addon_cost)->toBe(15_000);
});

it('does not count add-ons toward the voucher or free-shipping basis', function () {
    $without = place($this);
    $with = place($this, ['gift_wrap' => true, 'card_message' => 'Halo']);

    expect($with->shipping_cost)->toBe($without->shipping_cost)
        ->and($with->subtotal)->toBe($without->subtotal)
        ->and($with->discount ?? 0)->toBe($without->discount ?? 0);
});

it('includes add-ons in the COD limit check', function () {
    Setting::put(['cod_max' => (string) (($this->line)() + 15_000 + 1_000)]);

    expect(fn () => place($this, ['gift_wrap' => true, 'card_message' => 'Halo'], null, 'cod'))->toThrow(Exception::class);
    expect(place($this, [], null, 'cod')->isCod())->toBeTrue();
});

it('shows add-ons live in checkout and saves them on submit', function () {
    Store::add($this->profile->id, 200, 'v60');

    Livewire::actingAs($this->customer)->test(Checkout::class)
        ->set('giftWrap', true)->set('cardMessage', 'Buat kamu')
        ->assertSee('Rp20.000')->assertSee('Rp159.000')
        ->set('name', 'Nadia')->set('phone', '081234567890')->set('address', $this->ship['address'])
        ->call('submit')->assertHasNoErrors();

    $o = Order::firstOrFail();
    expect($o->gift_wrap)->toBeTrue()->and($o->card_message)->toBe('Buat kamu')->and($o->addon_cost)->toBe(20_000);
});

it('rejects a card message over the limit at checkout', function () {
    Store::add($this->profile->id, 200, 'v60');

    Livewire::actingAs($this->customer)->test(Checkout::class)
        ->set('name', 'Nadia')->set('phone', '081234567890')->set('address', $this->ship['address'])
        ->set('cardMessage', str_repeat('a', 200))
        ->call('submit');

    expect(Order::count())->toBe(1);
    expect(mb_strlen(Order::first()->card_message ?? ''))->toBeLessThanOrEqual(Order::CARD_MAX);
});

it('shows add-ons on invoice, admin order list and roastery queue', function () {
    $o = place($this, ['gift_wrap' => true, 'card_message' => 'Selamat wisuda']);
    $o->markPaid();

    $this->actingAs($this->admin)->get(route('admin.orders.print', $o->fresh()))
        ->assertOk()->assertSee('Selamat wisuda')->assertSee('Kemasan hadiah');

    $roaster = User::where('email', 'roaster@brule.test')->first();
    Livewire::actingAs($roaster)->test(\App\Livewire\Roastery::class)->assertSee('Selamat wisuda');
});

it('saves add-on settings from the admin form', function () {
    Livewire::actingAs($this->admin)->test(StoreSettings::class)
        ->set('giftWrapPrice', 25000)->set('cardPrice', 7000)->set('addonsEnabled', true)
        ->call('save')->assertHasNoErrors();

    expect(Order::giftWrapPrice())->toBe(25_000)->and(Order::cardPrice())->toBe(7_000);
});

it('accepts 100 g and 250 g in the cart and builder but still rejects other weights', function () {
    expect(Store::GRAMS)->toBe([100, 200, 250, 500, 1000]);

    $b = Livewire::actingAs($this->customer)->test(Builder::class);
    $b->call('setGrams', 100)->assertSet('grams', 100);
    $b->call('setGrams', 250)->assertSet('grams', 250);
    $b->call('setGrams', 300)->assertSet('grams', 250);

    $o = place($this, [], null, 'online', 100);
    expect($o->subtotal)->toBe(($this->line)(100));
});

it('seeds 14 coffees including the Carbonic Maceration process', function () {
    $this->seed(MoreCoffeeSeeder::class);

    expect(Coffee::count())->toBe(14)
        ->and(Coffee::where('name', 'like', '%Carbonic Maceration%')->exists())->toBeTrue()
        ->and(Coffee::where('name', 'like', '%Carbonic Maceration%')->first()->traceability?->process)->toBe('Carbonic Maceration');
});

it('derives a short name without process words', function () {
    $c = new Coffee(['name' => 'Colombia Pitalito Carbonic Maceration']);
    expect($c->shortName())->toBe('Colombia Pitalito');
});

it('lets the admin pick Carbonic Maceration as a process', function () {
    expect(CoffeeForm::PROCESSES)->toContain('Carbonic Maceration');
});

it('keeps the roastery stock list compact: preview, search, and low-stock filter', function () {
    $this->seed(MoreCoffeeSeeder::class);
    $roaster = User::where('email', 'roaster@brule.test')->first();
    Coffee::first()->update(['stock_green_bean_gram' => 0]);
    Coffee::skip(1)->first()->update(['stock_green_bean_gram' => 500]);

    $c = Livewire::actingAs($roaster)->test(\App\Livewire\Roastery::class);
    expect($c->get('stockRows')['rows'])->toHaveCount(\App\Livewire\Roastery::STOCK_PREVIEW)
        ->and($c->get('stockRows')['hidden'])->toBe(14 - \App\Livewire\Roastery::STOCK_PREVIEW);
    expect($c->get('stockRows')['rows']->first()->stock_green_bean_gram)->toBe(0); // paling tipis di atas

    $c->set('stockAll', true);
    expect($c->get('stockRows')['rows'])->toHaveCount(14);

    $c->set('stockAll', false)->set('stockLowOnly', true);
    expect($c->get('stockRows')['rows'])->toHaveCount(2)->and($c->get('stockRows')['filtered'])->toBeTrue();

    $c->set('stockLowOnly', false)->set('stockSearch', 'carbonic');
    expect($c->get('stockRows')['rows'])->toHaveCount(1);
    $c->set('stockSearch', 'zzz')->assertSee('Tidak ada biji yang cocok');
});

it('scales the roastery queue: per-column limit, search, late flag and roast-plan batches', function () {
    $roaster = User::where('email', 'roaster@brule.test')->first();
    foreach (range(1, 12) as $i) {
        $o = place($this);
        $o->markPaid();
        $o->forceFill(['paid_at' => now()->subHours($i * 5)])->save();
    }

    $c = Livewire::actingAs($roaster)->test(\App\Livewire\Roastery::class);
    $col = $c->get('columns')['queued'];
    expect($col['total'])->toBe(12)
        ->and($col['orders'])->toHaveCount(\App\Livewire\Roastery::PER_COLUMN)
        ->and($col['hidden'])->toBe(4)
        ->and($col['late'])->toBe(8); // 25, 30, ... 60 jam (> 24 jam sejak dibayar)

    // paling lama menunggu ada di atas
    expect($col['orders']->first()->paid_at->lt($col['orders']->last()->paid_at))->toBeTrue();

    $c->call('showMore', 'queued');
    expect($c->get('columns')['queued']['hidden'])->toBe(0);

    // rencana sangrai: satu biji + level, total gram semua pesanan
    $plan = $c->get('batches');
    expect($plan)->toHaveCount(1)->and($plan->first()['grams'])->toBe(12 * 200)->and($plan->first()['orders'])->toBe(12);

    $c->set('search', 'tidak-ada-yang-cocok');
    expect($c->get('columns')['queued']['total'])->toBe(0);
    $c->set('search', $this->profile->coffee->name);
    expect($c->get('columns')['queued']['total'])->toBe(12);
});
