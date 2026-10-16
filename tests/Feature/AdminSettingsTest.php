<?php

use App\Livewire\Admin\StoreSettings;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@kromatik.test')->first();
    $this->customer = User::where('email', 'customer@kromatik.test')->first();
});

it('keeps settings admin-only', function () {
    $this->get(route('admin.settings'))->assertRedirect(route('login'));
    foreach (['customer', 'roaster'] as $r) {
        $this->actingAs(User::where('email', "$r@kromatik.test")->first())->get(route('admin.settings'))->assertForbidden();
    }
    $this->actingAs($this->admin)->get(route('admin.settings'))->assertOk()->assertSee('Pengaturan toko');
});

it('falls back to the built-in defaults when nothing is stored', function () {
    expect(Setting::int('shipping_flat'))->toBe(15000)->and(Order::shippingFor(100000))->toBe(15000)->and(Order::shippingFor(500000))->toBe(0);
});

it('saves normalised values and writes an activity log', function () {
    Livewire::actingAs($this->admin)->test(StoreSettings::class)
        ->set('storeName', 'Kromatik Lab')->set('city', 'Cimahi')->set('whatsapp', '+62 812-3456-7890')->set('instagram', 'https://instagram.com/@kromatik.id/')
        ->set('email', 'hi@kromatik.id')->set('shippingFlat', '20000')->set('freeFrom', '300000')->set('lowStock', '8000')
        ->call('save')->assertHasNoErrors();

    expect(Setting::get('store_name'))->toBe('Kromatik Lab')
        ->and(Setting::get('contact_whatsapp'))->toBe('6281234567890')
        ->and(Setting::get('instagram'))->toBe('kromatik.id')
        ->and(Setting::int('low_stock_gram'))->toBe(8000)
        ->and(ActivityLog::where('action', 'settings.updated')->count())->toBe(1);
});

it('validates input', function () {
    Livewire::actingAs($this->admin)->test(StoreSettings::class)
        ->set('email', 'bukan-email')->set('whatsapp', '123')->set('instagram', 'ada spasi!')->set('shippingFlat', '-5')->set('freeFrom', 'abc')->set('storeName', '')
        ->call('save')->assertHasErrors(['email', 'whatsapp', 'instagram', 'shippingFlat', 'freeFrom', 'storeName']);
});

it('applies shipping settings to new orders and 0 disables free shipping', function () {
    $profile = RoastProfile::first();
    $ship = ['name' => 'N', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung'];

    Setting::put(['shipping_flat' => 22000, 'free_shipping_from' => 0]);
    $o = Order::place($this->customer, $profile, 1000, 'v60', $ship);
    expect($o->shipping_cost)->toBe(22000)->and($o->total)->toBe($o->subtotal + 22000);

    Setting::put(['free_shipping_from' => 50000]);
    expect(Order::shippingFor(60000))->toBe(0)->and(Order::shippingFor(40000))->toBe(22000);
});

it('shows the configured contact details in the public footer and checkout note', function () {
    Setting::put(['instagram' => 'toko.kopi', 'contact_whatsapp' => '6285711112222', 'contact_email' => 'kontak@toko.id', 'free_shipping_from' => 250000]);
    $this->get(route('landing'))->assertSee('instagram.com/toko.kopi', false)->assertSee('wa.me/6285711112222', false)->assertSee('mailto:kontak@toko.id', false);

    $p = RoastProfile::first();
    $this->actingAs($this->customer)->get(route('checkout', ['p' => $p->id, 'g' => 200]))->assertSee('Rp250.000');
});

it('uses the low-stock threshold from settings', function () {
    $c = \App\Models\Coffee::first();
    $c->update(['stock_green_bean_gram' => 7000, 'is_active' => true]);
    Setting::put(['low_stock_gram' => 5000]);
    $r = Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\Stock::class);
    expect($r->get('totals')['low'])->toBe(0);

    Setting::put(['low_stock_gram' => 9000]);
    $r2 = Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\Stock::class);
    expect($r2->get('totals')['low'])->toBeGreaterThanOrEqual(1);
});
