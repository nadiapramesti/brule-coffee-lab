<?php

use App\Livewire\Notifications;
use App\Models\Coffee;
use App\Models\Notice;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@kromatik.test')->first();
    $this->roaster = User::where('email', 'roaster@kromatik.test')->first();
    $this->customer = User::where('email', 'customer@kromatik.test')->first();
    $this->profile = RoastProfile::first();
});

function noticeOrder($t, int $g = 200): Order
{
    return Order::place($t->customer, $t->profile, $g, 'v60', ['name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka 10, Bandung']);
}

it('tells admins about a new order but not the customer', function () {
    $o = noticeOrder($this);

    expect(Notice::where('user_id', $this->admin->id)->where('title', 'Pesanan baru masuk')->count())->toBe(1)
        ->and(Notice::where('user_id', $this->customer->id)->count())->toBe(0)
        ->and(Notice::where('user_id', $this->admin->id)->first()->url)->toContain($o->order_code);
});

it('notifies customer and staff through the order lifecycle', function () {
    $o = noticeOrder($this);
    $this->actingAs($this->roaster);

    $o->markPaid();
    expect(Notice::where('user_id', $this->customer->id)->where('title', 'Pembayaran diterima')->exists())->toBeTrue()
        ->and(Notice::where('user_id', $this->roaster->id)->where('type', 'queue')->exists())->toBeFalse() // pelaku tidak diberi tahu soal aksinya sendiri
        ->and(Notice::where('user_id', $this->admin->id)->where('type', 'queue')->exists())->toBeTrue();

    $o->advance(); $o->advance(); $o->advance();
    $o->ship('JNE123');
    $o->complete();

    $titles = Notice::where('user_id', $this->customer->id)->pluck('title')->all();
    expect($titles)->toContain('Kopimu mulai disangrai', 'Kopimu sedang digiling', 'Kopimu sedang dikemas', 'Pesananmu dikirim', 'Pesanan selesai');
});

it('notifies the customer when an order is cancelled', function () {
    $o = noticeOrder($this);
    $o->cancelAndRestock('Stok bermasalah');

    $n = Notice::where('user_id', $this->customer->id)->where('type', 'cancel')->firstOrFail();
    expect($n->bodyText())->toContain('Stok bermasalah');
});

it('warns admins once when stock crosses the low threshold', function () {
    $coffee = $this->profile->coffee;
    $coffee->update(['stock_green_bean_gram' => 5100]); // batas bawaan 5000

    $coffee->moveStock('sale', -200);
    $coffee->moveStock('sale', -200);

    expect(Notice::where('user_id', $this->admin->id)->where('type', 'stock')->count())->toBe(1);
});

it('shows only own notices, marks read and opens target', function () {
    $o = noticeOrder($this);
    $o->markPaid();
    $mine = Notice::where('user_id', $this->customer->id)->latest('id')->first();

    $t = Livewire::actingAs($this->customer)->test(Notifications::class)->assertSee('Pembayaran diterima')->assertDontSee('Pesanan baru masuk');
    expect($t->get('unread'))->toBe(1);

    $t->call('open', $mine->id)->assertRedirect($mine->url);
    expect($mine->fresh()->read_at)->not->toBeNull();

    // tidak bisa membuka notifikasi orang lain
    $other = Notice::where('user_id', $this->admin->id)->first();
    Livewire::actingAs($this->customer)->test(Notifications::class)->call('open', $other->id);
    expect($other->fresh()->read_at)->toBeNull();
});

it('marks all as read and follows the viewer locale', function () {
    Notice::send($this->customer, 'status', 'Pesanan selesai', 'Pesanan :code selesai. Selamat menyeduh!', ['code' => 'KRM-1']);
    Notice::send($this->customer, 'status', 'Pesanan selesai', null);

    $t = Livewire::actingAs($this->customer)->test(Notifications::class);
    expect($t->get('unread'))->toBe(2);
    $t->call('markAllRead');
    expect($t->get('unread'))->toBe(0);

    app()->setLocale('en');
    expect(Notice::first()->titleText())->toBe('Order completed');
});

it('renders the bell in the panel and public layouts', function () {
    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Notifikasi');
    $this->actingAs($this->customer)->get(route('landing'))->assertOk()->assertSee('Notifikasi');
});
