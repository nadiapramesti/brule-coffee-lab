<?php

use App\Livewire\Admin\Customers;
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
    $this->profile = RoastProfile::first();
});

function placeFor(User $u, int $g = 200): Order
{
    return Order::place($u, RoastProfile::first(), $g, 'v60', ['name' => $u->name, 'phone' => '0812', 'address' => 'Jl. Uji No. 1']);
}

it('keeps the customers page admin-only', function () {
    $this->get(route('admin.customers'))->assertRedirect(route('login'));
    $this->actingAs($this->customer)->get(route('admin.customers'))->assertForbidden();
    $this->actingAs(User::where('email', 'roaster@brule.test')->first())->get(route('admin.customers'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.customers'))->assertOk()->assertSee($this->customer->name);
});

it('lists only customers, with spend counted from paid non-cancelled orders', function () {
    $paid = placeFor($this->customer, 200); $paid->markPaid();
    $unpaid = placeFor($this->customer, 200);
    $cancelled = placeFor($this->customer, 200); $cancelled->cancelAndRestock();

    $row = Livewire::actingAs($this->admin)->test(Customers::class)->assertDontSee('admin@brule.test')
        ->get('customers')->getCollection()->firstWhere('id', $this->customer->id);

    expect((int) $row->spent)->toBe($paid->total)
        ->and($row->orders_count)->toBe(2); // pesanan batal tidak dihitung
});

it('filters by tab and search', function () {
    placeFor($this->customer)->markPaid();
    $never = User::factory()->create(['role' => 'customer', 'name' => 'Belum Beli Sama Sekali']);

    $t = Livewire::actingAs($this->admin)->test(Customers::class);
    $t->set('tab', 'ordered')->assertSee($this->customer->name)->assertDontSee('Belum Beli Sama Sekali');
    $t->set('tab', 'never')->assertSee('Belum Beli Sama Sekali')->assertDontSee($this->customer->name);
    $t->set('tab', '')->set('search', 'Belum Beli')->assertSee('Belum Beli Sama Sekali')->assertDontSee($this->customer->name);
    $t->set('search', '')->set('tab', 'repeat')->assertDontSee($this->customer->name);

    placeFor($this->customer);
    $t->set('tab', 'repeat')->assertSee($this->customer->name);
});

it('shows a customer detail with history and favourites', function () {
    $o = placeFor($this->customer, 500);
    Livewire::actingAs($this->admin)->test(Customers::class)->call('show', $this->customer->id)
        ->assertSee($o->order_code)->assertSee('Jl. Uji No. 1')->assertSee($this->profile->coffee->name);

    $this->actingAs($this->admin)->get(route('admin.customers', ['id' => $this->customer->id]))->assertOk()->assertSee('Riwayat pesanan');
});
