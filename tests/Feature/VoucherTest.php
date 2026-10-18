<?php

use App\Livewire\Admin\Vouchers;
use App\Livewire\Checkout;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->profile = RoastProfile::first();
    $this->ship = ['name' => 'N', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung'];
    $this->line = $this->profile->priceFor(200);
});

function mkVoucher(array $o = []): Voucher
{
    return Voucher::create($o + ['code' => 'TEST10', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 0, 'is_active' => true]);
}

it('computes percent (with cap) and fixed discounts, never above the subtotal', function () {
    expect(mkVoucher(['value' => 10])->discountFor(100000))->toBe(10000)
        ->and(mkVoucher(['code' => 'CAP', 'value' => 50, 'max_discount' => 20000])->discountFor(100000))->toBe(20000)
        ->and(mkVoucher(['code' => 'FIX', 'type' => 'fixed', 'value' => 999999])->discountFor(80000))->toBe(80000);
});

it('applies a voucher at checkout and the order stores discount, code and usage', function () {
    $v = mkVoucher(['quota' => 5]);
    $o = Order::place($this->customer, $this->profile, 200, 'v60', $this->ship, ' test10 ');

    $d = intdiv($this->line * 10, 100);
    expect($o->discount)->toBe($d)->and($o->voucher_code)->toBe('TEST10')
        ->and($o->total)->toBe($this->line + $o->shipping_cost - $d)
        ->and($v->fresh()->used_count)->toBe(1);
});

it('rejects unusable vouchers and leaves no order or usage behind', function () {
    $cases = [
        ['NOPE', null],
        ['OFF', ['is_active' => false]],
        ['OLD', ['expires_at' => now()->subDay()]],
        ['SOON', ['starts_at' => now()->addDay()]],
        ['FULL', ['quota' => 1, 'used_count' => 1]],
        ['BIG', ['min_subtotal' => 99999999]],
    ];
    foreach ($cases as [$code, $attrs]) {
        $attrs && mkVoucher($attrs + ['code' => $code]);
        expect(fn () => Order::place($this->customer, $this->profile, 200, 'v60', $this->ship, $code))->toThrow(RuntimeException::class);
    }
    expect(Order::count())->toBe(0)->and(Voucher::sum('used_count'))->toBe(1);
});

it('allows one use per customer and frees the quota again when the order is cancelled', function () {
    $v = mkVoucher(['quota' => 3]);
    $o = Order::place($this->customer, $this->profile, 200, 'v60', $this->ship, 'TEST10');
    expect(fn () => Order::place($this->customer, $this->profile, 200, 'v60', $this->ship, 'TEST10'))->toThrow(RuntimeException::class, 'sudah pernah');

    $o->cancelAndRestock();
    expect($v->fresh()->used_count)->toBe(0);
    Order::place($this->customer, $this->profile, 200, 'v60', $this->ship, 'TEST10');
    expect($v->fresh()->used_count)->toBe(1);
});

it('shows the discount in the checkout summary and places the order with it', function () {
    mkVoucher();
    $t = Livewire::actingAs($this->customer)->withQueryParams(['p' => $this->profile->id, 'g' => 200])->test(Checkout::class)
        ->set('voucherInput', 'salah')->call('applyVoucher')->assertSee('tidak ditemukan')
        ->set('voucherInput', 'test10')->call('applyVoucher')->assertSet('voucher', 'TEST10')->assertSee('Diskon');
    expect($t->get('discount'))->toBe(intdiv($this->line * 10, 100));

    $t->set('name', 'Nadia')->set('phone', '081234567890')->set('address', 'Jl. Merdeka No. 10, Bandung')->call('submit');
    expect(Order::first()->voucher_code)->toBe('TEST10');
});

it('keeps voucher admin admin-only and runs CRUD with validation', function () {
    $this->actingAs($this->customer)->get(route('admin.vouchers'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.vouchers'))->assertOk();

    $t = Livewire::actingAs($this->admin)->test(Vouchers::class)->call('openForm')
        ->set('code', 'bad code!')->set('value', '150')->call('save')->assertHasErrors(['code', 'value']);
    $t->set('code', ' hemat 5 ')->assertSet('code', ' hemat 5 ');

    $t->set('code', 'kopi-baru')->set('value', '15')->set('maxDiscount', '30000')->set('quota', '10')->set('expiresAt', now()->addMonth()->format('Y-m-d'))->call('save')->assertHasNoErrors();
    $v = Voucher::where('code', 'KOPI-BARU')->first();
    expect($v)->not->toBeNull()->and($v->max_discount)->toBe(30000)->and($v->state())->toBe('active');

    $t->call('openForm')->set('code', 'KOPI-BARU')->set('value', '5')->call('save')->assertHasErrors('code');

    $t->call('toggle', $v->id);
    expect($v->fresh()->state())->toBe('inactive');

    $v->update(['used_count' => 2]);
    $t->call('delete', $v->id);
    expect(Voucher::find($v->id))->not->toBeNull();
    $t->call('openForm', $v->id)->set('quota', '1')->call('save')->assertHasErrors('quota');

    $v->update(['used_count' => 0]);
    $t->call('delete', $v->id);
    expect(Voucher::find($v->id))->toBeNull();
});
