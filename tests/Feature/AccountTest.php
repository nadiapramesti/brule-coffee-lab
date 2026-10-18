<?php

use App\Livewire\Account;
use App\Livewire\Checkout;
use App\Models\Address;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use App\Support\Cart;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', 'customer@kromatik.test')->first();
});

function acctOrder(User $u, int $qty = 2): Order
{
    $p = RoastProfile::first();
    $lines = array_fill(0, $qty, ['profile' => $p, 'grams' => 200, 'grind' => 'whole_bean']);

    return Order::placeMany($u, $lines, ['name' => $u->name, 'phone' => '081234567890', 'address' => 'Jl. Dago No. 25, Bandung 40135'], null, 'online');
}

it('requires login for the account page', function () {
    $this->get('/akun')->assertRedirect('/login');
});

it('lists only my orders and filters by status', function () {
    $mine = acctOrder($this->user);
    $other = acctOrder(User::where('email', 'roaster@kromatik.test')->first(), 1);
    $mine->cancelAndRestock();

    Livewire::actingAs($this->user)->test(Account::class)
        ->assertSee($mine->order_code)->assertDontSee($other->order_code)
        ->set('filter', 'aktif')->assertDontSee($mine->order_code)
        ->set('filter', 'batal')->assertSee($mine->order_code);
});

it('buys again by putting the old items back into the cart', function () {
    $o = acctOrder($this->user, 3);

    Livewire::actingAs($this->user)->test(Account::class)
        ->call('reorder', $o->order_code)->assertRedirect(route('cart'));

    expect(Cart::count())->toBe(3);
});

it('cannot reorder someone else\'s order', function () {
    $o = acctOrder(User::where('email', 'roaster@kromatik.test')->first(), 1);

    Livewire::actingAs($this->user)->test(Account::class)->call('reorder', $o->order_code);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('manages the address book with a single default', function () {
    $c = Livewire::actingAs($this->user)->test(Account::class)->set('tab', 'alamat');

    $c->call('newAddress')->set('label', 'Rumah')->set('recipient', 'Nadia')->set('phone', '081234567890')
        ->set('address', 'Jl. Dago No. 25, Coblong, Bandung 40135')->call('saveAddress')->assertHasNoErrors();
    $c->call('newAddress')->set('label', 'Kantor')->set('recipient', 'Nadia')->set('phone', '081234567890')
        ->set('address', 'Jl. Asia Afrika No. 8, Bandung 40111')->call('saveAddress')->assertHasNoErrors();

    $first = Address::where('label', 'Rumah')->first();
    $second = Address::where('label', 'Kantor')->first();
    expect($first->is_default)->toBeTrue()->and($second->is_default)->toBeFalse();

    $c->call('setDefault', $second->id);
    expect($first->fresh()->is_default)->toBeFalse()->and($second->fresh()->is_default)->toBeTrue();

    $c->call('deleteAddress', $second->id);
    expect($first->fresh()->is_default)->toBeTrue();
});

it('validates the address form', function () {
    Livewire::actingAs($this->user)->test(Account::class)->set('tab', 'alamat')->call('newAddress')
        ->set('label', '')->set('phone', '123')->set('address', 'pendek')->call('saveAddress')
        ->assertHasErrors(['label', 'phone', 'address']);
});

it('prefills checkout from the default address and can save a new one', function () {
    Address::where('user_id', $this->user->id)->delete();
    Address::create(['user_id' => $this->user->id, 'label' => 'Rumah', 'recipient' => 'Budi', 'phone' => '081111111111',
        'address' => 'Jl. Merdeka No. 1, Bandung 40111', 'is_default' => true]);
    $this->actingAs($this->user);
    Cart::add(RoastProfile::first()->id, 200, 'whole_bean');

    Livewire::test(Checkout::class)
        ->assertSet('name', 'Budi')->assertSet('address', 'Jl. Merdeka No. 1, Bandung 40111')
        ->call('useAddress', null)->set('address', 'Jl. Baru No. 99, Surabaya 60111')->set('saveAddress', true)
        ->call('submit')->assertHasNoErrors();

    expect(Address::where('user_id', $this->user->id)->count())->toBe(2);
});

it('seeds a default address for the demo customer', function () {
    $this->seed(\Database\Seeders\UserSeeder::class);
    $u = \App\Models\User::where('email', 'customer@kromatik.test')->first();

    expect($u->addresses()->where('is_default', true)->count())->toBe(1);
});

it('names the address field in the Indonesian required message', function () {
    app()->setLocale('id');
    $m = validator(['address' => ''], ['address' => 'required'])->errors()->first('address');

    expect($m)->toContain('Alamat lengkap')->not->toContain('address');
});
