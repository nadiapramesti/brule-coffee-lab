<?php

use App\Livewire\Admin\ActivityLogs;
use App\Livewire\Admin\Users;
use App\Models\ActivityLog;
use App\Models\Coffee;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->roaster = User::where('email', 'roaster@brule.test')->first();
});

it('keeps users and log pages admin-only', function () {
    foreach (['admin.users', 'admin.logs'] as $r) {
        $this->get(route($r))->assertRedirect(route('login'));
    }
    foreach (['admin.users', 'admin.logs'] as $r) {
        $this->actingAs($this->customer)->get(route($r))->assertForbidden();
        $this->actingAs($this->roaster)->get(route($r))->assertForbidden();
        $this->actingAs($this->admin)->get(route($r))->assertOk();
    }
});

it('lists, filters and searches users', function () {
    $t = Livewire::actingAs($this->admin)->test(Users::class)->assertSee($this->customer->email);
    $t->call('setRole', 'roaster')->assertSee($this->roaster->email)->assertDontSee($this->customer->email);
    $t->call('setRole', 'bogus')->assertSet('role', '')->set('search', 'raka')->assertSee('raka@brule.test')->assertDontSee($this->roaster->email);
    expect($t->get('counts')[''])->toBe(User::count());
});

it('changes a role, logs it, and protects self and the last admin', function () {
    $t = Livewire::actingAs($this->admin)->test(Users::class);

    $t->call('openRole', $this->customer->id)->set('newRole', 'roaster')->call('saveRole')->assertHasNoErrors();
    expect($this->customer->fresh()->role)->toBe('roaster')
        ->and(ActivityLog::where('action', 'user.role_changed')->count())->toBe(1);

    $t->call('openRole', $this->admin->id)->set('newRole', 'customer')->call('saveRole')->assertHasErrors('newRole');
    expect($this->admin->fresh()->role)->toBe('admin');

    // admin kedua boleh diturunkan hanya selama masih ada admin lain
    $other = User::factory()->create(['role' => 'admin']);
    $t->call('openRole', $other->id)->set('newRole', 'customer')->call('saveRole')->assertHasNoErrors();
    expect($other->fresh()->role)->toBe('customer');

    $solo = User::factory()->create(['role' => 'admin']);
    $this->actingAs($solo);
    User::where('id', $this->admin->id)->update(['role' => 'customer']);
    $t2 = Livewire::actingAs($solo)->test(Users::class);
    $t2->call('openRole', $solo->id)->set('newRole', 'customer')->call('saveRole')->assertHasErrors('newRole');
});

it('creates staff accounts with validation', function () {
    $t = Livewire::actingAs($this->admin)->test(Users::class)->call('openCreate')
        ->set('name', '')->set('email', 'bukan-email')->set('password', '123')->call('createStaff')->assertHasErrors(['name', 'email', 'password']);
    $t->set('name', 'Dimas')->set('email', $this->customer->email)->set('password', 'rahasia123')->call('createStaff')->assertHasErrors('email');
    $t->set('email', 'Dimas@Brule.ID')->set('staffRole', 'roaster')->call('createStaff')->assertHasNoErrors();

    $u = User::where('email', 'dimas@brule.id')->first();
    expect($u)->not->toBeNull()->and($u->role)->toBe('roaster')->and($u->isRoaster())->toBeTrue();
    expect(\Illuminate\Support\Facades\Auth::validate(['email' => 'dimas@brule.id', 'password' => 'rahasia123']))->toBeTrue();
});

it('cannot create a staff account with the customer role through a tampered request', function () {
    Livewire::actingAs($this->admin)->test(Users::class)->call('openCreate')
        ->set('name', 'X')->set('email', 'x@brule.id')->set('password', 'rahasia123')->set('staffRole', 'superuser')->call('createStaff')->assertHasErrors('staffRole');
    expect(User::where('email', 'x@brule.id')->exists())->toBeFalse();
});

it('records activity from coffees, vouchers and stock and filters the log', function () {
    $c = Coffee::first();
    Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\CoffeeIndex::class)->call('toggle', $c->id);
    Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\Vouchers::class)->call('openForm')
        ->set('code', 'logtest')->set('value', '10')->call('save');
    Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\Stock::class)->call('openAdjust', $c->id, 'restock')->set('amount', '1000')->call('save');

    expect(ActivityLog::whereIn('action', ['coffee.toggled', 'voucher.created', 'stock.adjusted'])->count())->toBe(3);

    $t = Livewire::actingAs($this->admin)->test(ActivityLogs::class)->assertSee('LOGTEST');
    $t->call('setGroup', 'voucher')->assertSee('LOGTEST')->assertDontSee($c->name);
    $t->call('setGroup', 'coffee')->assertSee($c->name)->assertDontSee('LOGTEST');
    $t->call('setGroup', 'zzz')->assertSet('group', '')->set('by', $this->customer->id)->assertSee('Belum ada aktivitas yang cocok');
    $t->set('by', null)->set('from', now()->addDay()->toDateString())->assertSee('Belum ada aktivitas yang cocok');
});
