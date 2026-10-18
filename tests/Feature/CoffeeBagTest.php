<?php

use App\Models\Coffee;
use Database\Seeders\CoffeeSeeder;
use Illuminate\Support\Facades\Blade;

beforeEach(fn () => $this->seed([CoffeeSeeder::class]));

it('renders a branded pouch svg for a coffee without a photo', function () {
    $c = Coffee::with('traceability')->first();
    $html = Blade::render('<x-coffee-bag :coffee="$c" level="medium_dark" />', ['c' => $c]);

    expect($html)->toContain('<svg')->toContain('logo-brule-light.svg')->not->toContain('KROMATIK')->toContain('MEDIUM DARK')
        ->and(strtoupper($html))->toContain(strtoupper(explode(' ', $c->name)[0]));
});

it('uses the uploaded photo instead of the pouch when the coffee has one', function () {
    $c = Coffee::with('traceability')->first();
    $c->image = 'coffees/foto.jpg';
    $html = Blade::render('<x-coffee-bag :coffee="$c" />', ['c' => $c]);

    expect($html)->toContain('<img')->toContain('storage/coffees/foto.jpg')->not->toContain('<svg');
});

it('draws the same chromatogram for the same coffee every time', function () {
    $c = Coffee::with('traceability')->first();
    $pick = fn () => preg_match('/points="([^"]+)"/', Blade::render('<x-coffee-bag :coffee="$c" />', ['c' => $c]), $m) ? $m[1] : '';

    expect($pick())->not->toBe('')->toBe($pick());
});
