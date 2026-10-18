<?php

use App\Livewire\Admin\ProductForm;
use App\Livewire\Admin\ProductIndex;
use App\Livewire\Cart as CartPage;
use App\Livewire\Checkout;
use App\Livewire\Gear;
use App\Models\Coffee;
use App\Models\Order;
use App\Models\Product;
use App\Models\RoastProfile;
use App\Models\User;
use App\Support\Cart as Store;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class, ProductSeeder::class]);
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->profile = RoastProfile::first();
    $this->v60 = Product::where('slug', 'hario-v60-dripper-02')->first();
    $this->tee = Product::where('slug', 'kaos-brule')->first();
    $this->ship = ['name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung 40111'];
    $this->actingAs($this->customer);
});

it('seeds products and the gear page lists them by category', function () {
    expect(Product::count())->toBe(10);

    $this->get('/perlengkapan')->assertOk()->assertSee('Hario V60 Dripper 02')->assertSee('Kaos BRÛLÉ');

    Livewire::test(Gear::class)->call('setCategory', 'merch')
        ->assertSee('Kaos BRÛLÉ')->assertDontSee('Hario V60 Dripper 02');
});

it('requires an option before adding a product that has options', function () {
    Livewire::test(Gear::class)->call('add', $this->tee->id)->assertSet('error', __('Pilih :l dulu.', ['l' => 'ukuran']));
    expect(Store::raw())->toBe([]);

    Livewire::test(Gear::class)->set("choice.{$this->tee->id}", 'M')->call('add', $this->tee->id)->assertSet('addedId', $this->tee->id);
    expect(Store::raw())->toHaveCount(1);
    expect(Store::raw()[0]['option'])->toBe('M');
});

it('rejects invalid options, sold-out and hidden products', function () {
    expect(Store::addProduct($this->tee->id, 'XXL'))->toBeFalse()
        ->and(Store::addProduct(Product::where('slug', 'gooseneck-kettle')->value('id')))->toBeTrue(); // stok 0 ditolak di Gear, bukan di Cart

    Livewire::test(Gear::class)->call('add', Product::where('slug', 'gooseneck-kettle')->value('id'))->assertSet('error', __('Stok :n habis.', ['n' => 'Gooseneck Kettle 600 ml']));

    $this->v60->update(['is_active' => false]);
    expect(Store::addProduct($this->v60->id, 'Putih'))->toBeFalse();
});

it('merges identical product lines and keeps coffee and product lines apart', function () {
    Store::add($this->profile->id, 200, 'v60');
    Store::addProduct($this->v60->id, 'Putih');
    Store::addProduct($this->v60->id, 'Putih');
    Store::addProduct($this->v60->id, 'Hitam');

    $lines = Store::lines();
    expect($lines)->toHaveCount(3)
        ->and($lines->where('kind', 'product')->firstWhere('option', 'Putih')->qty)->toBe(2)
        ->and($lines->sum('price'))->toBe($this->profile->priceFor(200) + 185000 * 3);
});

it('flags a cart whose product quantity exceeds stock', function () {
    $kettleless = Product::where('slug', 'french-press-600')->first(); // stok 3
    $this->customer->refresh();
    for ($i = 0; $i < 4; $i++) {
        Store::addProduct($kettleless->id);
    }
    expect(Store::stockProblem(Store::lines()))->toContain('French Press 600 ml');
});

it('places a product-only order, decrements stock and skips roasting', function () {
    $stock = $this->v60->stock;
    $order = Order::placeMany($this->customer, [
        ['product' => $this->v60, 'option' => 'Putih'],
        ['product' => $this->v60, 'option' => 'Putih'],
    ], $this->ship);

    expect($order->subtotal)->toBe(370000)
        ->and($order->items)->toHaveCount(2)
        ->and($order->items->first()->isProduct())->toBeTrue()
        ->and($order->items->first()->roast_profile_id)->toBeNull()
        ->and($this->v60->fresh()->stock)->toBe($stock - 2)
        ->and($order->hasCoffee())->toBeFalse()
        ->and($order->itemSummary())->toBe('Hario V60 Dripper 02 Putih × 2');

    $order->markPaid();
    $order->refresh();
    expect($order->status)->toBe('queued')->and($order->nextStatus())->toBe('packing');

    expect($order->advance())->toBeTrue();
    expect($order->fresh()->status)->toBe('packing')->and($order->fresh()->roasted_at)->toBeNull();
    expect($order->fresh()->ship('JNE123'))->toBeTrue();
});

it('keeps the full roasting flow for mixed orders and charges both kinds', function () {
    $coffeeStock = Coffee::find($this->profile->coffee_id)->stock_green_bean_gram;
    $order = Order::placeMany($this->customer, [
        ['profile' => $this->profile, 'grams' => 200, 'grind' => 'v60'],
        ['product' => $this->tee, 'option' => 'L'],
    ], $this->ship);

    expect($order->hasCoffee())->toBeTrue()
        ->and($order->subtotal)->toBe($this->profile->priceFor(200) + 175000)
        ->and($order->flow())->toBe(Order::FLOW)
        ->and(Coffee::find($this->profile->coffee_id)->stock_green_bean_gram)->toBeLessThan($coffeeStock);

    $order->markPaid();
    expect($order->fresh()->nextStatus())->toBe('roasting');
});

it('refuses to oversell product stock and rolls everything back', function () {
    $kettle = Product::where('slug', 'french-press-600')->first(); // stok 3
    $lines = array_fill(0, 4, ['product' => $kettle, 'option' => null]);
    $before = Order::count();

    expect(fn () => Order::placeMany($this->customer, $lines, $this->ship))->toThrow(RuntimeException::class);
    expect($kettle->fresh()->stock)->toBe(3)->and(Order::count())->toBe($before);
});

it('restocks products when an order is cancelled', function () {
    $order = Order::placeMany($this->customer, [['product' => $this->tee, 'option' => 'M']], $this->ship);
    expect($this->tee->fresh()->stock)->toBe(29);

    $order->cancelAndRestock('tes');
    expect($this->tee->fresh()->stock)->toBe(30);
});

it('checks out a cart with products through the Checkout component', function () {
    Store::add($this->profile->id, 200, 'v60');
    Store::addProduct($this->tee->id, 'S');

    Livewire::test(Checkout::class)
        ->set('name', $this->ship['name'])->set('phone', $this->ship['phone'])->set('address', $this->ship['address'])
        ->call('submit')->assertHasNoErrors();

    $o = Order::latest('id')->first();
    expect($o->items)->toHaveCount(2)->and($o->items->whereNotNull('product_id'))->toHaveCount(1);
    expect(Store::raw())->toBe([]);
});

it('shows products in the cart and tracking page', function () {
    Store::addProduct($this->tee->id, 'XL');
    Livewire::test(CartPage::class)->assertSee('Kaos BRÛLÉ')->assertSee('XL');

    $order = Order::placeMany($this->customer, [['product' => $this->tee, 'option' => 'XL']], $this->ship);
    $this->get('/lacak/'.$order->order_code)->assertOk()->assertSee('Kaos BRÛLÉ')->assertSee('XL');
});

it('keeps products out of coffee reports and shows them in their own table', function () {
    $order = Order::placeMany($this->customer, [
        ['profile' => $this->profile, 'grams' => 200, 'grind' => 'v60'],
        ['product' => $this->tee, 'option' => 'M'],
    ], $this->ship);
    $order->markPaid();

    $r = new \App\Support\SalesReport(now()->subDay()->startOfDay(), now()->endOfDay());
    expect($r->byCoffee()->pluck('name')->all())->not->toContain('Kaos BRÛLÉ')
        ->and($r->byProduct()->pluck('name')->all())->toContain('Kaos BRÛLÉ')
        ->and($r->byLevel()->pluck('level')->all())->not->toContain('M');
});

it('buys again a product order by re-adding product lines', function () {
    $order = Order::placeMany($this->customer, [['product' => $this->tee, 'option' => 'M']], $this->ship);

    Livewire::test(\App\Livewire\Account::class)->call('reorder', $order->order_code);
    expect(Store::raw()[0])->toMatchArray(['kind' => 'product', 'product_id' => $this->tee->id, 'option' => 'M']);
});

it('lets an admin create, edit, restock, hide and delete products', function () {
    $this->actingAs($this->admin);

    Livewire::test(ProductForm::class)
        ->set('name', 'Gelas Takar')->set('description', 'Gelas ukur kecil untuk takaran kopi.')
        ->set('category', 'accessory')->set('price', '45000')->set('stock', '9')
        ->set('option_label', 'Ukuran')->set('options', 'Kecil, Besar')
        ->call('save')->assertHasNoErrors();

    $p = Product::where('slug', 'gelas-takar')->first();
    expect($p->options)->toBe(['Kecil', 'Besar'])->and($p->stock)->toBe(9);

    Livewire::test(ProductIndex::class)
        ->set("restock.{$p->id}", '5')->call('addStock', $p->id)
        ->call('toggle', $p->id)
        ->call('delete', $p->id);
    expect(Product::where('slug', 'gelas-takar')->exists())->toBeFalse();
});

it('does not delete a product that was already ordered and validates the form', function () {
    Order::placeMany($this->customer, [['product' => $this->tee, 'option' => 'M']], $this->ship);
    $this->actingAs($this->admin);

    Livewire::test(ProductIndex::class)->call('delete', $this->tee->id);
    expect(Product::find($this->tee->id))->not->toBeNull();

    Livewire::test(ProductForm::class)->set('name', '')->set('price', '5')->call('save')
        ->assertHasErrors(['name', 'description', 'price']);
});

it('is only reachable by admins on the admin side', function () {
    $this->get('/admin/produk')->assertForbidden();
    $this->actingAs($this->admin)->get('/admin/produk')->assertOk()->assertSee('Hario V60 Dripper 02');
});

it('opens a product detail dialog and adds from it', function () {
    Livewire::test(Gear::class)->call('openDetail', 'kaos-brule')
        ->assertSee('Potongan unisex')->assertSee('Tambah ke keranjang')
        ->set("choice.{$this->tee->id}", 'L')->call('add', $this->tee->id)->assertSet('addedId', $this->tee->id)
        ->call('closeDetail')->assertSet('detail', null);

    Livewire::withQueryParams(['produk' => 'tote-bag-brule'])->test(Gear::class)->assertSee('Kanvas tebal');
});

it('draws an illustration for every seeded product when there is no photo', function () {
    foreach (Product::all() as $p) {
        $html = (string) view('components.product-art', ['product' => $p]);
        expect($html)->toContain('<svg')->toContain($p->name);
    }
});

it('uses the final product photos, per-option photos and merch alt text', function () {
    expect(Product::whereNull('image')->count())->toBe(0)
        ->and(Product::where('slug', 'mug-matte-black-brule')->value('name'))->toBe('Mug Matte Black Ceramic 300 ml Model C')
        ->and(Product::where('slug', 'mug-enamel-brule')->exists())->toBeFalse();

    foreach (Product::all() as $p) {
        expect(file_exists(public_path(ltrim($p->image, '/'))))->toBeTrue("foto {$p->slug} tidak ada");
    }

    expect($this->v60->imageUrl('Hitam'))->toContain('v60-hitam-final.jpg')
        ->and($this->v60->imageUrl('Putih'))->toContain('v60-putih-final.jpg')
        ->and($this->v60->imageUrl())->toContain('v60-putih-final.jpg');

    $html = (string) view('components.product-thumb', ['product' => $this->tee]);
    expect($html)->toContain('alt="BRÛLÉ - Kaos BRÛLÉ - cream lab aesthetic - B chromatogram high contrast"')->toContain('bg-[#F5F1E8]');
});

it('re-seeding keeps a single mug even from the old enamel slug', function () {
    Product::where('slug', 'mug-matte-black-brule')->update(['slug' => 'mug-enamel-brule', 'name' => 'Mug Enamel 300 ml']);
    $this->seed(ProductSeeder::class);

    expect(Product::where('slug', 'like', 'mug-%')->count())->toBe(1)
        ->and(Product::where('slug', 'mug-matte-black-brule')->value('name'))->toContain('Model C');
});
