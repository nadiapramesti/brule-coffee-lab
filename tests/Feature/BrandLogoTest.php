<?php

beforeEach(fn () => $this->seed());

it('ships monochrome logo files with pure black and pure white only', function () {
    foreach (['logo-brule-dark' => '#FFFFFF', 'logo-brule-light' => '#000000',
              'logo-brule-stacked-dark' => '#FFFFFF', 'logo-brule-stacked-light' => '#000000',
              'logo-brule-mark-dark' => '#FFFFFF', 'logo-brule-mark-light' => '#000000'] as $file => $color) {
        $svg = file_get_contents(public_path($file.'.svg'));
        preg_match_all('/(?:fill|stroke)="(#[0-9A-Fa-f]{3,6})"/', $svg, $m);

        expect(array_unique($m[1]))->toBe([$color], $file.' harus hanya memakai '.$color);
    }
});

it('serves both logo variants in the header and footer so CSS can pick by theme', function () {
    $this->get('/')->assertOk()
        ->assertSee('logo-brule-dark.svg', false)
        ->assertSee('logo-brule-light.svg', false)
        ->assertSee('krm-logo-dark', false)
        ->assertSee('krm-logo-light', false);
});

it('declares the B-circle favicon in every layout', function () {
    $this->get('/')->assertSee('favicon.svg', false)->assertSee('favicon.ico', false);
    $this->get('/login')->assertSee('favicon.svg', false);
    $this->get('/halaman-tidak-ada')->assertNotFound()->assertSee('favicon.svg', false);

    expect(file_get_contents(public_path('favicon.svg')))->toContain('<circle')->not->toContain('lab');
});

it('keeps the logo CSS rules that swap by theme and by dark zones', function () {
    $css = collect(glob(public_path('build/assets/*.css')))->map(fn ($f) => file_get_contents($f))->implode('');

    expect($css)->toContain('.krm-logo-dark')->toContain('.krm-logo-light');
})->skip(fn () => glob(public_path('build/assets/*.css')) === [], 'butuh npm run build');
