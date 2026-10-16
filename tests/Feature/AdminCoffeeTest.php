<?php

use App\Livewire\Admin\CoffeeForm;
use App\Livewire\Admin\CoffeeIndex;
use App\Models\Coffee;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@kromatik.test')->first();
});

it('blocks non-admins from the admin area', function () {
    $this->get(route('admin.coffees'))->assertRedirect(route('login'));
    foreach (['customer', 'roaster'] as $r) {
        $this->actingAs(User::where('email', "$r@kromatik.test")->first())
            ->get(route('admin.coffees'))->assertForbidden();
    }
    $this->actingAs($this->admin)->get(route('admin.coffees'))->assertOk()->assertSee(Coffee::first()->name);
});

it('adds stock and toggles visibility', function () {
    $c = Coffee::first();
    $before = $c->stock_green_bean_gram;

    Livewire::actingAs($this->admin)->test(CoffeeIndex::class)
        ->set("restock.$c->id", 2500)->call('addStock', $c->id)
        ->call('toggle', $c->id);

    expect($c->fresh()->stock_green_bean_gram)->toBe($before + 2500)
        ->and($c->fresh()->is_active)->toBeFalse();
});

it('rejects invalid stock amounts', function () {
    $c = Coffee::first();
    Livewire::actingAs($this->admin)->test(CoffeeIndex::class)
        ->set("restock.$c->id", -5)->call('addStock', $c->id)
        ->assertHasErrors(["restock.$c->id"]);
});

it('creates a coffee with traceability and profiles', function () {
    Livewire::actingAs($this->admin)->test(CoffeeForm::class)
        ->set('name', 'Gayo Honey Test')
        ->set('story', 'Cerita uji.')
        ->set('stock', '9000')
        ->set('farmer', 'Koperasi Uji')->set('region', 'Takengon')->set('altitude', '1450')
        ->set('varietal', 'Ateng')->set('process', 'Honey')
        ->set('profiles.light.on', true)->set('profiles.light.price', '42000')->set('profiles.light.notes', 'Honey, Peach')
        ->call('save')->assertHasNoErrors()->assertRedirect(route('admin.coffees'));

    $c = Coffee::where('slug', 'gayo-honey-test')->first();
    expect($c)->not->toBeNull()
        ->and($c->traceability->region)->toBe('Takengon')
        ->and($c->roastProfiles)->toHaveCount(1)
        ->and($c->roastProfiles->first()->tasting_notes)->toBe(['Honey', 'Peach'])
        ->and($c->roastProfiles->first()->roast_curve)->not->toBeEmpty();
});

it('requires at least one roast level and a price for enabled levels', function () {
    $base = fn () => Livewire::actingAs($this->admin)->test(CoffeeForm::class)
        ->set('name', 'Tanpa Level')->set('story', 'x')->set('stock', '1')
        ->set('farmer', 'a')->set('region', 'b')->set('altitude', '1000')->set('varietal', 'c');

    $base()->call('save')->assertHasErrors('profiles');
    $base()->set('profiles.dark.on', true)->call('save')->assertHasErrors(['profiles.dark.price', 'profiles.dark.notes']);
});

it('edits an existing coffee and keeps its slug', function () {
    $c = Coffee::first();
    Livewire::actingAs($this->admin)->test(CoffeeForm::class, ['coffee' => $c])
        ->assertSet('name', $c->name)
        ->set('name', 'Nama Baru')->set('stock', '777')
        ->call('save')->assertHasNoErrors();

    expect($c->fresh()->name)->toBe('Nama Baru')
        ->and($c->fresh()->slug)->toBe($c->slug)
        ->and($c->fresh()->stock_green_bean_gram)->toBe(777)
        ->and($c->fresh()->roastProfiles)->toHaveCount($c->roastProfiles()->count());
});

it('refuses to delete a coffee that has orders', function () {
    $this->seed([\Database\Seeders\MoreCoffeeSeeder::class, \Database\Seeders\OrderSeeder::class]);
    $used = \App\Models\OrderItem::first()->roastProfile->coffee_id;

    Livewire::actingAs($this->admin)->test(CoffeeIndex::class)->call('delete', $used);
    expect(Coffee::find($used))->not->toBeNull();

    $free = Coffee::create(['slug' => 'tanpa-order', 'name' => 'Tanpa Order', 'stock_green_bean_gram' => 0]);
    Livewire::actingAs($this->admin)->test(CoffeeIndex::class)->call('delete', $free->id);
    expect(Coffee::find($free->id))->toBeNull();
});
