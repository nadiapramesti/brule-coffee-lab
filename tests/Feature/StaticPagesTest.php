<?php

use App\Models\Setting;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed([UserSeeder::class]);
});

it('serves the help and policy pages in both languages with no leftover placeholders', function () {
    foreach (['id', 'en'] as $loc) {
        app()->setLocale($loc);
        foreach (['help', 'policy.shipping', 'policy.terms', 'policy.privacy'] as $r) {
            $html = $this->withSession(['locale' => $loc])->get(route($r))->assertOk()->getContent();
            foreach ([':flat', ':free', ':cod', ':wa', ':email', ':address'] as $ph) {
                expect($html)->not->toContain($ph);
            }
        }
    }
});

it('fills in shop numbers from settings and exposes FAQ structured data', function () {
    Setting::put(['shipping_flat' => 22000, 'free_shipping_from' => 750000, 'cod_enabled' => 0]);
    app()->forgetInstance('krm.settings');

    $this->get(route('help'))->assertOk()
        ->assertSee('Rp22.000', false)->assertSee('Rp750.000', false)
        ->assertSee('COD sedang tidak tersedia.')
        ->assertSee('application/ld+json', false)->assertSee('FAQPage', false);
});

it('mentions the COD limit when one is set', function () {
    Setting::put(['cod_enabled' => 1, 'cod_max' => 400000]);
    app()->forgetInstance('krm.settings');

    $this->get(route('policy.terms'))->assertOk()->assertSee('Rp400.000', false);
});

it('links the footer to the policies and the real FAQ page', function () {
    $this->get(route('landing'))->assertOk()
        ->assertSee(route('help'), false)->assertSee(route('policy.terms'), false)
        ->assertSee(route('policy.privacy'), false)->assertSee(route('policy.shipping'), false);
});
