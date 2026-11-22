<?php

use App\Models\Coffee;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Laravel\Sanctum\Sanctum;

/** Favorit lewat API: GET daftar id kopi, POST toggle (slug atau id), hanya kopi aktif. */
beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->coffee = Coffee::active()->orderBy('id')->first();
});

test('butuh login', function () {
    $this->getJson('/api/favorites')->assertUnauthorized();
    $this->postJson("/api/favorites/{$this->coffee->slug}")->assertUnauthorized();
});

test('awalnya kosong, toggle menambah lalu menghapus', function () {
    Sanctum::actingAs($this->customer);

    $this->getJson('/api/favorites')->assertOk()->assertExactJson(['data' => []]);

    $this->postJson("/api/favorites/{$this->coffee->slug}")->assertOk()
        ->assertJsonPath('favorited', true)
        ->assertJsonPath('data', [$this->coffee->id]);
    $this->getJson('/api/favorites')->assertJsonPath('data', [$this->coffee->id]);
    expect($this->customer->favorites()->count())->toBe(1);

    $this->postJson("/api/favorites/{$this->coffee->slug}")->assertOk()
        ->assertJsonPath('favorited', false)
        ->assertJsonPath('data', []);
    expect($this->customer->favorites()->count())->toBe(0);
});

test('toggle juga menerima id numerik', function () {
    Sanctum::actingAs($this->customer);

    $this->postJson("/api/favorites/{$this->coffee->id}")->assertOk()->assertJsonPath('favorited', true);
    expect($this->customer->favorites()->pluck('coffees.id')->all())->toBe([$this->coffee->id]);
});

test('kopi nonaktif atau tidak ada: 404 dan tidak tersimpan', function () {
    Sanctum::actingAs($this->customer);
    $this->coffee->update(['is_active' => false]);

    $this->postJson("/api/favorites/{$this->coffee->slug}")->assertNotFound();
    $this->postJson('/api/favorites/kopi-tidak-ada')->assertNotFound();
    $this->postJson('/api/favorites/999999')->assertNotFound();
    expect($this->customer->favorites()->count())->toBe(0);
});

test('favorit terpisah per pengguna', function () {
    Sanctum::actingAs($this->customer);
    $this->postJson("/api/favorites/{$this->coffee->slug}")->assertOk();

    Sanctum::actingAs(User::where('email', 'admin@brule.test')->first());
    $this->getJson('/api/favorites')->assertOk()->assertJsonPath('data', []);
});
