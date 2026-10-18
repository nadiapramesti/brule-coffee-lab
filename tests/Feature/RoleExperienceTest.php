<?php

use App\Livewire\Account;
use App\Livewire\Builder;
use App\Livewire\Cart as CartPage;
use App\Livewire\Checkout;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('shows customers the shopping header: cart and the brew CTA', function () {
    $html = $this->actingAs(User::where('email', 'customer@kromatik.test')->first())->get('/')->assertOk()->getContent();

    expect($html)->toContain('Racik kopimu')->toContain(route('cart'))->not->toContain('Buka panel');
});

it('gives admin and roaster a panel button instead of cart and shopping CTA', function (string $email, string $home) {
    $html = $this->actingAs(User::where('email', $email)->first())->get('/')->assertOk()->getContent();

    expect($html)->toContain('Buka panel')->toContain(route($home))
        ->not->toContain('aria-label="Keranjang"')->not->toContain('Racik kopimu');
})->with([['admin@kromatik.test', 'admin.dashboard'], ['roaster@kromatik.test', 'roastery']]);

it('sends staff away from cart, checkout and the customer account page', function (string $email, string $home) {
    $this->actingAs(User::where('email', $email)->first());

    foreach ([CartPage::class, Checkout::class, Account::class] as $page) {
        Livewire::test($page)->assertRedirect(route($home));
    }
})->with([['admin@kromatik.test', 'admin.dashboard'], ['roaster@kromatik.test', 'roastery']]);

it('keeps the builder view-only for staff and refuses adding to cart', function () {
    $this->actingAs(User::where('email', 'admin@kromatik.test')->first());

    $html = $this->get('/builder')->assertOk()->getContent();
    expect($html)->toContain('mode lihat saja')->not->toContain('wire:click="addToCart"');

    Livewire::test(Builder::class)->call('addToCart')->assertForbidden();
    expect(RoastProfile::count())->toBeGreaterThan(0);
});

it('still lets customers add to the cart', function () {
    $this->actingAs(User::where('email', 'customer@kromatik.test')->first());

    Livewire::test(Builder::class)->call('addToCart')->assertHasNoErrors();
});
