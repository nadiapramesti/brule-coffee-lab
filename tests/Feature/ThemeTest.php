<?php

use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;

beforeEach(fn () => $this->seed([UserSeeder::class, CoffeeSeeder::class]));

it('ships the theme boot script and toggle on public, auth and panel layouts', function () {
    $admin = User::where('email', 'admin@brule.test')->first();

    foreach ([route('landing'), route('login')] as $url) {
        $this->get($url)->assertOk()->assertSee("localStorage.getItem('krm-theme')", false)->assertSee('Ganti tema');
    }
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
        ->assertSee("localStorage.getItem('krm-theme')", false)->assertSee('Ganti tema');
});
