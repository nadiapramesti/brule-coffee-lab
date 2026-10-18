<?php

use App\Models\Coffee;
use App\Models\Setting;

beforeEach(fn () => $this->seed());

it('shows a branded 404 page', function () {
    $this->get('/halaman-yang-tidak-ada')
        ->assertNotFound()
        ->assertSee('Halaman ini tidak ketemu')
        ->assertSee('BRÛLÉ');
});

it('shows the 404 page in English when the session locale is en', function () {
    $this->withSession(['locale' => 'en'])->get('/nope')->assertNotFound();
    $this->get('/bahasa/en')->assertRedirect();
});

it('exposes seo meta tags and a canonical url', function () {
    $this->get('/')->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('name="twitter:card"', false)
        ->assertSee('rel="canonical"', false);
});

it('keeps cart and tracking pages out of search results', function () {
    $this->get('/keranjang')->assertSee('name="robots" content="noindex"', false);
});

it('serves a sitemap listing every active coffee', function () {
    $res = $this->get('/sitemap.xml')->assertOk();
    expect($res->headers->get('Content-Type'))->toContain('xml');
    foreach (Coffee::active()->pluck('slug') as $slug) {
        $res->assertSee('kopi='.$slug, false);
    }
});

it('shows the free shipping promo bar from settings and hides it at zero', function () {
    $this->get('/')->assertSee('Gratis ongkir untuk belanja mulai Rp500.000');

    Setting::put(['free_shipping_from' => '0']);
    app()->forgetInstance('krm.settings');
    $this->get('/')->assertDontSee('data-promo-bar', false);
});

it('renders catalog cards with starting price, stock badge and CTA', function () {
    $coffee = Coffee::active()->first();
    $this->get('/')->assertOk()
        ->assertSee($coffee->name)
        ->assertSee('Mulai dari')
        ->assertSee('Tersedia')
        ->assertSee('Racik kopi ini');
});

it('marks a coffee sold out in the catalog and removes its CTA link', function () {
    $coffee = Coffee::active()->first();
    $coffee->update(['stock_green_bean_gram' => 0]);

    expect($coffee->fresh()->load('roastProfiles')->stockState())->toBe('soldout');
    $this->get('/')->assertSee('Habis');
});

it('renders the catalog search box and sort options', function () {
    $this->get('/')->assertOk()
        ->assertSee('id="cari-biji"', false)
        ->assertSee('Harga terendah')
        ->assertSee('Rating tertinggi')
        ->assertSee('Tidak ada biji yang cocok');
});

it('shows the catalog in steps with a load-more button instead of one endless page', function () {
    $this->seed();
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('more()')->toContain('remaining > 0')->toContain('innerWidth < 640');
});

it('lays out the post-payment stages as a vertical list on phones, not a hidden swipe row', function () {
    $this->seed();
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('snap-x')->not->toContain('overflow-x-auto px-6');
});

it('lists the builder beans as phone rows and collapses long lists behind a show-all button', function () {
    $this->seed();
    $html = $this->get('/builder')->assertOk()->getContent();

    expect($html)->not->toContain('snap-x')->toContain('sm:hidden')->toContain('innerWidth < 640');
});
