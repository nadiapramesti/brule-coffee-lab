<?php

use App\Livewire\Admin\StoreSettings;
use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
    Mail::fake();
    $this->user = User::where('email', 'customer@brule.test')->first();
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->profile = RoastProfile::first();
});

function bbOrder($t): Order
{
    $o = Order::placeMany($t->user, [['profile' => $t->profile, 'grams' => 200, 'grind' => 'whole_bean']], ['name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung 40111'], null, 'online');
    $o->markPaid();

    return $o->fresh('items');
}

it('has no best-before date until the order is roasted, then adds the freshness window to the roast date', function () {
    $o = bbOrder($this);
    expect($o->bestBefore())->toBeNull();

    $o->update(['roasted_at' => '2026-10-10 09:00:00']);
    expect(Order::bestBeforeDays())->toBe(30)
        ->and($o->fresh('items')->bestBefore()->format('Y-m-d'))->toBe('2026-11-09');
});

it('follows the freshness window set in settings and validates it', function () {
    Livewire::actingAs($this->admin)->test(StoreSettings::class)
        ->set('bestBefore', '0')->call('save')->assertHasErrors(['bestBefore'])
        ->set('bestBefore', '45')->call('save')->assertHasNoErrors();

    $o = bbOrder($this);
    $o->update(['roasted_at' => '2026-10-10 09:00:00']);
    expect(Order::bestBeforeDays())->toBe(45)->and($o->fresh('items')->bestBefore()->format('Y-m-d'))->toBe('2026-11-24');
});

it('prints roast date and best-before on the label, with blanks before roasting', function () {
    $o = bbOrder($this);
    $this->actingAs($this->admin)->get(route('admin.orders.print', $o))->assertOk()->assertSee('Terbaik sebelum')->assertSee('+30');

    $o->update(['roasted_at' => '2026-10-10 09:00:00']);
    $this->actingAs($this->admin)->get(route('admin.orders.print', $o))->assertOk()->assertSee('10 Okt 2026')->assertSee('9 Nov 2026');
});

it('shows the best-before date on the tracking page once roasting has started', function () {
    $o = bbOrder($this);
    $o->update(['status' => 'roasting', 'roasted_at' => '2026-10-10 09:00:00']);

    $this->actingAs($this->user)->get(route('track', $o->order_code))->assertOk()->assertSee('Terbaik dinikmati sebelum')->assertSee('09 Nov 2026');
});

it('mentions the best-before date in the shipping email only', function () {
    $o = bbOrder($this);
    $o->update(['roasted_at' => '2026-10-10 09:00:00', 'tracking_number' => 'JNE123456']);
    $o = $o->fresh('items');

    expect((new OrderMail($o, 'shipped'))->render())->toContain('9 Nov 2026')
        ->and((new OrderMail($o, 'paid'))->render())->not->toContain('9 Nov 2026');
});

it('skips the date for orders without coffee', function () {
    $o = bbOrder($this);
    $o->items()->update(['roast_profile_id' => null]);
    $o->update(['roasted_at' => '2026-10-10 09:00:00']);

    expect($o->fresh('items')->hasCoffee())->toBeFalse()->and($o->fresh('items')->bestBefore())->toBeNull();
});
