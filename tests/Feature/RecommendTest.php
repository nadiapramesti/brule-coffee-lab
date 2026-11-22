<?php

use App\Livewire\Builder;
use App\Livewire\Recommend;
use App\Models\Coffee;
use Livewire\Livewire;

beforeEach(fn () => $this->seed());

it('opens the quiz page and links it from the header', function () {
    $this->get(route('recommend'))->assertOk()->assertSee('Biasanya kamu minum kopi seperti apa?');
    $this->get(route('landing'))->assertSee(route('recommend'), false);
});

it('walks three questions and shows up to three matches', function () {
    $c = Livewire::test(Recommend::class)
        ->call('answer', 'hitam')->assertSet('step', 2)
        ->call('answer', 'buah')->assertSet('step', 3)
        ->call('answer', 'suka')->assertSet('step', 4);

    $results = $c->instance()->results;
    expect($results)->toHaveCount(min(3, Coffee::active()->count()));
    $c->assertSee('PALING COCOK')->assertSee('Racik kopi ini');
});

it('ignores answers that are not valid options and can go back or restart', function () {
    Livewire::test(Recommend::class)
        ->call('answer', 'tidak-ada')->assertSet('step', 1)
        ->call('answer', 'susu')->call('back')->assertSet('step', 1)
        ->call('answer', 'susu')->call('answer', 'cokelat')->call('answer', 'tidak')
        ->call('restart')->assertSet('step', 1)->assertSet('answers', []);
});

it('prefers a lighter roast for acid lovers and a darker one for milk drinkers', function () {
    $level = fn (string $drink, string $taste, string $acid) => Livewire::test(Recommend::class)
        ->call('answer', $drink)->call('answer', $taste)->call('answer', $acid)
        ->instance()->results->first()['profile']->level;

    $order = array_keys(\App\Models\RoastProfile::LEVELS);
    $light = array_search($level('hitam', 'buah', 'suka'), $order, true);
    $dark = array_search($level('susu', 'kacang', 'tidak'), $order, true);

    expect($light)->toBeLessThanOrEqual($dark);
});

it('preselects the recommended coffee and level in the roast builder', function () {
    $coffee = Coffee::active()->with('roastProfiles')->first();
    $profile = $coffee->roastProfiles->sortBy('agtron')->first();

    $this->get(route('builder', ['kopi' => $coffee->slug, 'level' => $profile->level]));
    $c = Livewire::withQueryParams(['kopi' => $coffee->slug, 'level' => $profile->level])->test(Builder::class);

    expect($c->instance()->profile->level)->toBe($profile->level);
});
