<?php

use App\Livewire\Landing;
use App\Models\Coffee;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    $this->user = User::where('email', 'customer@brule.test')->first();
    $this->coffee = Coffee::active()->first();
});

it('toggles a favorite for a logged-in customer', function () {
    $c = Livewire::actingAs($this->user)->test(Landing::class)->call('toggleFavorite', $this->coffee->id);
    expect($this->user->favorites()->pluck('coffees.id')->all())->toBe([$this->coffee->id]);

    $c->call('toggleFavorite', $this->coffee->id);
    expect($this->user->favorites()->count())->toBe(0);
});

it('sends guests to the login page instead of saving', function () {
    Livewire::test(Landing::class)->call('toggleFavorite', $this->coffee->id)->assertRedirect(route('login'));
    expect(\DB::table('favorites')->count())->toBe(0);
});

it('ignores unknown or inactive coffees and shows the heart and filter on the landing page', function () {
    Livewire::actingAs($this->user)->test(Landing::class)->call('toggleFavorite', 999999);
    expect($this->user->favorites()->count())->toBe(0);

    $this->actingAs($this->user)->get(route('landing'))->assertOk()->assertSee('Favorit saya')->assertSee('Simpan favorit');
});
