<?php

use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed();
    Mail::fake();
    $this->user = User::where('email', 'customer@kromatik.test')->first();
    $this->profile = RoastProfile::first();
});

function mailOrder($t, string $payment = 'online', int $qty = 2): Order
{
    $lines = array_fill(0, $qty, ['profile' => $t->profile, 'grams' => 200, 'grind' => 'whole_bean']);

    return Order::placeMany($t->user, $lines, ['name' => 'Nadia Pramesti', 'phone' => '081234567890', 'address' => 'Jl. Dago No. 25, Bandung 40135'], null, $payment);
}

it('emails the buyer when an order is placed', function () {
    $o = mailOrder($this);

    Mail::assertSent(OrderMail::class, fn ($m) => $m->hasTo($this->user->email) && $m->kind === 'placed' && $m->order->is($o));
});

it('uses a payment prompt for online orders and a cash reminder for COD', function () {
    $online = mailOrder($this, 'online');
    $cod = mailOrder($this, 'cod');

    $html = (new OrderMail($online, 'placed'))->render();
    expect($html)->toContain('Bayar pesanan')->toContain($online->order_code)->toContain('× 2');
    expect((new OrderMail($cod, 'placed'))->subjectLine())->toContain('COD');
    expect((new OrderMail($cod, 'placed'))->render())->toContain('Siapkan uang tunai');
});

it('emails on payment, shipping with the tracking number, and cancellation', function () {
    $o = mailOrder($this);
    $o->markPaid();
    Mail::assertSent(OrderMail::class, fn ($m) => $m->kind === 'paid');

    $o->update(['status' => 'packing']);
    $o->ship('JNE123456');
    Mail::assertSent(OrderMail::class, fn ($m) => $m->kind === 'shipped' && str_contains($m->render(), 'JNE123456'));

    $c = mailOrder($this);
    $c->cancelAndRestock();
    Mail::assertSent(OrderMail::class, fn ($m) => $m->kind === 'cancelled' && $m->order->is($c));
});

it('never breaks the order when the mail transport fails', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    $o = mailOrder($this);

    expect($o->exists)->toBeTrue()->and(Order::count())->toBeGreaterThan(0);
});

it('renders a plain-text alternative', function () {
    $o = mailOrder($this, 'online', 1);
    $m = new OrderMail($o, 'paid');

    expect(view('emails.order-text', $m->content()->with)->render())->toContain($o->order_code)->toContain('Total');
});
