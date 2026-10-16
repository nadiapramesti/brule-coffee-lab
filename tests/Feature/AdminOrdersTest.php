<?php

use App\Livewire\Admin\Orders;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@kromatik.test')->first();
    $this->customer = User::where('email', 'customer@kromatik.test')->first();
    $this->profile = RoastProfile::first();
});

function newOrder($test, int $grams = 200): Order
{
    return Order::place($test->customer, $test->profile, $grams, 'v60', [
        'name' => 'Nadia', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung',
    ]);
}

it('keeps order pages admin-only', function () {
    $o = newOrder($this);
    $this->get(route('admin.orders'))->assertRedirect(route('login'));
    foreach (['customer', 'roaster'] as $r) {
        $u = User::where('email', "$r@kromatik.test")->first();
        $this->actingAs($u)->get(route('admin.orders'))->assertForbidden();
        $this->actingAs($u)->get(route('admin.orders.export'))->assertForbidden();
        $this->actingAs($u)->get(route('admin.orders.print', $o))->assertForbidden();
    }
    $this->actingAs($this->admin)->get(route('admin.orders'))->assertOk()->assertSee($o->order_code);
});

it('walks an order through the whole flow with a log trail', function () {
    $o = newOrder($this);

    $c = Livewire::actingAs($this->admin)->test(Orders::class)->call('show', $o->order_code)
        ->assertSee('Tandai sudah dibayar')->call('markPaid');
    expect($o->fresh()->status)->toBe('queued')->and($o->fresh()->paid_at)->not->toBeNull();

    $c->call('advance')->call('advance')->call('advance');
    expect($o->fresh()->status)->toBe('packing');

    // resi wajib diisi
    $c->set('tracking', '')->call('ship')->assertHasErrors('tracking');
    $c->set('tracking', 'JNE1234567890')->call('ship')->assertHasNoErrors();
    expect($o->fresh()->status)->toBe('shipped')->and($o->fresh()->tracking_number)->toBe('JNE1234567890');

    $c->call('complete');
    expect($o->fresh()->status)->toBe('completed')->and($o->fresh()->completed_at)->not->toBeNull();

    $actions = ActivityLog::where('subject_id', $o->id)->pluck('action')->all();
    expect($actions)->toContain('order.created', 'order.paid', 'order.status', 'order.shipped', 'order.completed');
});

it('cancels and restores stock, but not once roasting started', function () {
    $coffee = $this->profile->coffee;
    $before = $coffee->fresh()->stock_green_bean_gram;
    $o = newOrder($this, 500);
    expect($coffee->fresh()->stock_green_bean_gram)->toBeLessThan($before);

    Livewire::actingAs($this->admin)->test(Orders::class)->call('show', $o->order_code)
        ->set('cancelReason', 'Stok biji habis')->call('cancel');
    expect($o->fresh()->status)->toBe('cancelled')->and($o->fresh()->cancelled_at)->not->toBeNull()
        ->and($coffee->fresh()->stock_green_bean_gram)->toBe($before);
    expect(ActivityLog::where('subject_id', $o->id)->where('action', 'order.cancelled')->first()->text())->toContain('Stok biji habis');

    $o2 = newOrder($this);
    $o2->markPaid(); $o2->advance();
    expect($o2->fresh()->status)->toBe('roasting')->and($o2->fresh()->cancelAndRestock())->toBeFalse();
});

it('ignores actions that do not fit the current status', function () {
    $o = newOrder($this);
    Livewire::actingAs($this->admin)->test(Orders::class)->call('show', $o->order_code)
        ->call('advance')->call('complete')->set('tracking', 'ABCDEF123')->call('ship');
    expect($o->fresh()->status)->toBe('pending_payment');
});

it('saves an internal note', function () {
    $o = newOrder($this);
    Livewire::actingAs($this->admin)->test(Orders::class)->call('show', $o->order_code)
        ->set('note', 'Hubungi dulu sebelum kirim')->call('saveNote');
    expect($o->fresh()->admin_note)->toBe('Hubungi dulu sebelum kirim');
});

it('filters by tab, search and date', function () {
    $a = newOrder($this); $b = newOrder($this); $b->markPaid();
    $old = newOrder($this); $old->forceFill(['created_at' => now()->subDays(20)])->save();

    $t = Livewire::actingAs($this->admin)->test(Orders::class);
    $t->set('tab', 'unpaid')->assertSee($a->order_code)->assertDontSee($b->order_code);
    $t->set('tab', '')->set('search', $b->order_code)->assertSee($b->order_code)->assertDontSee($a->order_code);
    $t->set('search', '')->call('preset', '7')->assertDontSee($old->order_code)->assertSee($a->order_code);
    $t->call('preset', 'all')->assertSee($old->order_code);
});

it('opens an order from a deep link', function () {
    $o = newOrder($this);
    $this->actingAs($this->admin)->get(route('admin.orders', ['order' => $o->order_code]))
        ->assertOk()->assertSee('Catatan internal')->assertSee('Invoice bisa dicetak setelah pembayaran lunas')->assertDontSee('Cetak invoice');
});

it('exports xlsx following the filters, with formulas kept as plain text', function () {
    $a = newOrder($this);
    $b = newOrder($this); $b->update(['recipient_name' => '=HYPERLINK("x")']); $b->markPaid();

    $r = $this->actingAs($this->admin)->get(route('admin.orders.export', ['tab' => 'production', 'format' => 'xlsx']));
    $r->assertOk();
    expect($r->headers->get('content-disposition'))->toContain('.xlsx');

    $tmp = tempnam(sys_get_temp_dir(), 't'); file_put_contents($tmp, $r->getContent());
    $zip = new ZipArchive; expect($zip->open($tmp))->toBeTrue();
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    expect($sheet)->toContain($b->order_code)->not->toContain($a->order_code)
        ->and($sheet)->toContain('=HYPERLINK')->not->toContain('<f>')
        ->and(simplexml_load_string($sheet))->not->toBeFalse();
});

it('exports a valid pdf', function () {
    $o = newOrder($this);
    $r = $this->actingAs($this->admin)->get(route('admin.orders.export', ['format' => 'pdf']));
    $r->assertOk();
    $pdf = $r->getContent();
    expect($r->headers->get('content-type'))->toContain('application/pdf')
        ->and($pdf)->toStartWith('%PDF-1.4')->toContain($o->order_code)->toContain('%%EOF');
    preg_match('/startxref\n(\d+)/', $pdf, $m);
    expect(substr($pdf, (int) $m[1], 4))->toBe('xref');
});

it('renders a printable invoice for a paid order', function () {
    $o = newOrder($this);
    $o->markPaid();
    $this->actingAs($this->admin)->get(route('admin.orders.print', $o))
        ->assertOk()->assertSee($o->order_code)->assertSee('Jl. Merdeka No. 10');
});
