<?php

use App\Livewire\Admin\Stock;
use App\Models\Coffee;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@kromatik.test')->first();
    $this->customer = User::where('email', 'customer@kromatik.test')->first();
    $this->profile = RoastProfile::first();
    $this->coffee = $this->profile->coffee;
});

it('keeps the stock page admin-only', function () {
    $this->get(route('admin.stock'))->assertRedirect(route('login'));
    $this->actingAs($this->customer)->get(route('admin.stock'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.stock'))->assertOk()->assertSee($this->coffee->name);
});

it('logs a sale and a cancel with a consistent running balance', function () {
    $start = $this->coffee->fresh()->stock_green_bean_gram;
    $o = Order::place($this->customer, $this->profile, 500, 'v60', ['name' => 'N', 'phone' => '08', 'address' => 'Jl. Uji']);
    $need = $this->coffee->greenBeanNeeded(500);

    $sale = StockMovement::where('order_id', $o->id)->where('type', 'sale')->first();
    expect($sale->grams)->toBe(-$need)->and($sale->balance_after)->toBe($start - $need);

    $o->cancelAndRestock();
    $cancel = StockMovement::where('order_id', $o->id)->where('type', 'cancel')->first();
    expect($cancel->grams)->toBe($need)->and($cancel->balance_after)->toBe($start)
        ->and($this->coffee->fresh()->stock_green_bean_gram)->toBe($start);
});

it('restocks, recounts and removes waste through the modal', function () {
    $s = $this->coffee->fresh()->stock_green_bean_gram;
    $t = Livewire::actingAs($this->admin)->test(Stock::class)->call('openAdjust', $this->coffee->id, 'restock')
        ->set('amount', '3000')->set('reason', 'Supplier Gayo')->call('save')->assertHasNoErrors();
    expect($this->coffee->fresh()->stock_green_bean_gram)->toBe($s + 3000);

    $t->call('openAdjust', $this->coffee->id, 'count')->set('amount', '10000')->call('save');
    expect($this->coffee->fresh()->stock_green_bean_gram)->toBe(10000);
    expect(StockMovement::where('type', 'adjustment')->first()->grams)->toBe(10000 - ($s + 3000));

    $t->call('openAdjust', $this->coffee->id, 'waste')->set('amount', '500')->call('save')->assertHasErrors('reason');
    $t->set('reason', 'Berjamur')->call('save')->assertHasNoErrors();
    expect($this->coffee->fresh()->stock_green_bean_gram)->toBe(9500);

    $t->call('openAdjust', $this->coffee->id, 'waste')->set('amount', '99999')->set('reason', 'x')->call('save')->assertHasErrors('amount');
    expect($this->coffee->fresh()->stock_green_bean_gram)->toBe(9500);
});

it('rejects invalid amounts', function () {
    Livewire::actingAs($this->admin)->test(Stock::class)->call('openAdjust', $this->coffee->id)
        ->set('amount', '')->call('save')->assertHasErrors('amount')
        ->set('amount', '-5')->call('save')->assertHasErrors('amount')
        ->set('amount', '1.5')->call('save')->assertHasErrors('amount');
});

it('logs stock edits made through the coffee form and the quick add', function () {
    Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\CoffeeForm::class, ['coffee' => $this->coffee])
        ->set('stock', '4321')->call('save')->assertHasNoErrors();
    expect(StockMovement::where('coffee_id', $this->coffee->id)->where('type', 'adjustment')->latest('id')->first()->balance_after)->toBe(4321);

    Livewire::actingAs($this->admin)->test(\App\Livewire\Admin\CoffeeIndex::class)
        ->set("restock.{$this->coffee->id}", 100)->call('addStock', $this->coffee->id);
    expect($this->coffee->fresh()->stock_green_bean_gram)->toBe(4421)
        ->and(StockMovement::where('type', 'restock')->count())->toBe(1);
});

it('filters the history and rebuilds it from orders in the seeder', function () {
    Order::place($this->customer, $this->profile, 200, 'v60', ['name' => 'N', 'phone' => '08', 'address' => 'Jl. Uji']);
    Livewire::actingAs($this->admin)->test(Stock::class)->set('tab', 'history')
        ->set('typeFilter', 'sale')->assertSee('Terjual')->set('typeFilter', 'restock')->assertSee('Belum ada pergerakan stok');

    $final = $this->coffee->fresh()->stock_green_bean_gram;
    $this->seed(\Database\Seeders\StockMovementSeeder::class);
    expect(StockMovement::where('coffee_id', $this->coffee->id)->latest('id')->first()->balance_after)->toBe($final);
});
