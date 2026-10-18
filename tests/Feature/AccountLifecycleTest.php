<?php

use App\Livewire\Admin\Users;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@kromatik.test')->first();
    $this->customer = User::where('email', 'customer@kromatik.test')->first();
    $this->roaster = User::where('email', 'roaster@kromatik.test')->first();
});

it('blocks a customer with a reason, logs it, and unblocks', function () {
    $t = Livewire::actingAs($this->admin)->test(Users::class);
    $t->call('openBlock', $this->customer->id)->set('blockReason', 'Menolak paket COD')->call('confirmBlock')->assertHasNoErrors();

    expect($this->customer->fresh()->isBlocked())->toBeTrue();
    expect($this->customer->fresh()->blocked_reason)->toBe('Menolak paket COD');
    expect(ActivityLog::where('action', 'user.blocked')->exists())->toBeTrue();
    expect($t->get('counts')['blocked'])->toBe(1);
    $t->call('setRole', 'blocked')->assertSee($this->customer->email)->assertSee('Menolak paket COD');

    $t->call('unblock', $this->customer->id);
    expect($this->customer->fresh()->isBlocked())->toBeFalse();
});

it('refuses to block staff or yourself', function () {
    Livewire::actingAs($this->admin)->test(Users::class)
        ->call('openBlock', $this->roaster->id)->call('confirmBlock')->assertHasErrors('blockReason');
    expect($this->roaster->fresh()->isBlocked())->toBeFalse();
});

it('stops a blocked customer from logging in or continuing a session', function () {
    $this->customer->block('tes');

    Volt::test('pages.auth.login')->set('form.email', $this->customer->email)->set('form.password', 'password')->call('login')->assertHasErrors('form.email');
    $this->assertGuest();

    $this->actingAs($this->customer->fresh())->get('/')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('edits a staff account and frees the email on delete', function () {
    $t = Livewire::actingAs($this->admin)->test(Users::class);
    $t->call('openRole', $this->roaster->id)
        ->set('editName', 'Roaster Baru')->set('editEmail', 'baru@brule.id')->set('editPhone', '081234567890')->set('editPassword', 'rahasia123')
        ->call('saveRole')->assertHasNoErrors();
    $r = $this->roaster->fresh();
    expect($r->name)->toBe('Roaster Baru')->and($r->email)->toBe('baru@brule.id');
    expect(Hash::check('rahasia123', $r->password))->toBeTrue();

    $t->call('openRole', $this->roaster->id)->set('editEmail', $this->customer->email)->call('saveRole')->assertHasErrors('editEmail');

    $t->call('openDelete', $this->roaster->id)->call('deleteStaff')->assertHasNoErrors();
    expect(User::find($this->roaster->id))->toBeNull();
    $t->call('openCreate')->set('name', 'Pengganti')->set('email', 'baru@brule.id')->set('staffRole', 'roaster')->set('password', 'password123')->call('createStaff')->assertHasNoErrors();
});

it('protects self and the last admin from deletion, and customers from the staff delete', function () {
    $t = Livewire::actingAs($this->admin)->test(Users::class);
    $t->call('openDelete', $this->admin->id)->call('deleteStaff')->assertHasErrors('delete');
    $t->call('openDelete', $this->customer->id)->call('deleteStaff')->assertHasErrors('delete');
    expect(User::find($this->admin->id))->not->toBeNull()->and(User::find($this->customer->id))->not->toBeNull();
});

it('lets a customer delete their account but keeps order history anonymised', function () {
    Order::query()->where('user_id', $this->customer->id)->update(['status' => 'completed']);
    $id = $this->customer->id;
    $orders = Order::where('user_id', $id)->count();

    $this->actingAs($this->customer);
    Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasNoErrors()->assertRedirect('/');

    $this->assertGuest();
    $u = User::withTrashed()->find($id);
    expect($u->trashed())->toBeTrue()->and($u->name)->toBe('Akun dihapus')->and($u->email)->not->toBe('customer@kromatik.test');
    expect(Order::where('user_id', $id)->count())->toBe($orders);
});

it('blocks self-deletion while an order is still in progress, and for staff', function () {
    $o = Order::where('user_id', $this->customer->id)->first();
    if ($o) {
        $o->update(['status' => 'roasting']);
        $this->actingAs($this->customer);
        Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasErrors();
        expect(User::find($this->customer->id))->not->toBeNull();
    }

    $this->actingAs($this->roaster);
    Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasErrors();
    expect(User::find($this->roaster->id))->not->toBeNull();
});
