<?php

use App\Livewire\Admin\CoffeeForm;
use App\Models\Coffee;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->coffee = Coffee::first();
});

it('uploads a photo and stores its path', function () {
    Livewire::actingAs($this->admin)->test(CoffeeForm::class, ['coffee' => $this->coffee])
        ->set('photo', UploadedFile::fake()->image('foto.jpg', 800, 1000))
        ->call('save')
        ->assertHasNoErrors();

    $path = $this->coffee->fresh()->image;
    expect($path)->toStartWith('coffees/');
    Storage::disk('public')->assertExists($path);
});

it('rejects non-images and files over 3 MB', function () {
    Livewire::actingAs($this->admin)->test(CoffeeForm::class, ['coffee' => $this->coffee])
        ->set('photo', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
        ->assertHasErrors(['photo']);

    Livewire::actingAs($this->admin)->test(CoffeeForm::class, ['coffee' => $this->coffee])
        ->set('photo', UploadedFile::fake()->image('besar.jpg')->size(4000))
        ->assertHasErrors(['photo']);
});

it('replaces the old photo file', function () {
    Storage::disk('public')->put('coffees/lama.jpg', 'x');
    $this->coffee->update(['image' => 'coffees/lama.jpg']);

    Livewire::actingAs($this->admin)->test(CoffeeForm::class, ['coffee' => $this->coffee->fresh()])
        ->set('photo', UploadedFile::fake()->image('baru.png'))
        ->call('save')->assertHasNoErrors();

    Storage::disk('public')->assertMissing('coffees/lama.jpg');
    Storage::disk('public')->assertExists($this->coffee->fresh()->image);
});

it('removes the photo and falls back to the SVG pouch', function () {
    Storage::disk('public')->put('coffees/lama.jpg', 'x');
    $this->coffee->update(['image' => 'coffees/lama.jpg']);

    Livewire::actingAs($this->admin)->test(CoffeeForm::class, ['coffee' => $this->coffee->fresh()])
        ->call('clearPhoto')
        ->call('save')->assertHasNoErrors();

    expect($this->coffee->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing('coffees/lama.jpg');
});

it('does not delete the file when removal is not saved', function () {
    Storage::disk('public')->put('coffees/lama.jpg', 'x');
    $this->coffee->update(['image' => 'coffees/lama.jpg']);

    Livewire::actingAs($this->admin)->test(CoffeeForm::class, ['coffee' => $this->coffee->fresh()])
        ->call('clearPhoto');

    Storage::disk('public')->assertExists('coffees/lama.jpg');
    expect($this->coffee->fresh()->image)->toBe('coffees/lama.jpg');
});

it('keeps an external image URL untouched when saving without changes', function () {
    $this->coffee->update(['image' => 'https://example.com/x.jpg']);

    Livewire::actingAs($this->admin)->test(CoffeeForm::class, ['coffee' => $this->coffee->fresh()])
        ->call('save')->assertHasNoErrors();

    expect($this->coffee->fresh()->image)->toBe('https://example.com/x.jpg');
});
