<?php

use App\Livewire\Admin\Reports;
use App\Models\Order;
use App\Models\RoastProfile;
use App\Models\User;
use App\Models\Voucher;
use App\Support\SalesReport;
use App\Support\SimplePdf;
use Database\Seeders\CoffeeSeeder;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([UserSeeder::class, CoffeeSeeder::class]);
    $this->admin = User::where('email', 'admin@brule.test')->first();
    $this->customer = User::where('email', 'customer@brule.test')->first();
    $this->profile = RoastProfile::first();
    $this->ship = ['name' => 'N', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung'];
});

function paidOrder($t, int $daysAgo, int $grams = 200, ?string $voucher = null): Order
{
    $o = Order::place($t->customer, $t->profile, $grams, 'v60', $t->ship, $voucher);
    $o->markPaid();
    $o->update(['paid_at' => now()->subDays($daysAgo)->setTime(10, 0)]);

    return $o->fresh();
}

it('keeps the report pages admin-only', function () {
    $this->get(route('admin.reports'))->assertRedirect(route('login'));
    foreach (['customer', 'roaster'] as $r) {
        $u = User::where('email', "$r@brule.test")->first();
        $this->actingAs($u)->get(route('admin.reports'))->assertForbidden();
        $this->actingAs($u)->get(route('admin.reports.export'))->assertForbidden();
    }
    $this->actingAs($this->admin)->get(route('admin.reports'))->assertOk()->assertSee('Laporan penjualan');
});

it('counts only paid, non-cancelled orders inside the range', function () {
    $a = paidOrder($this, 2);
    $b = paidOrder($this, 3, 500);
    $old = paidOrder($this, 40);
    $cancelled = paidOrder($this, 1); // belum dibatalkan, dibatalkan di bawah
    Order::where('id', $cancelled->id)->update(['status' => 'cancelled']);
    $unpaid = Order::place($this->customer, $this->profile, 200, 'v60', $this->ship);

    $s = SalesReport::forPreset('7d')->summary();
    expect($s['orders'])->toBe(2)->and($s['revenue'])->toBe($a->total + $b->total)->and($s['grams'])->toBe(700)
        ->and($s['aov'])->toBe((int) round(($a->total + $b->total) / 2))
        ->and($s['revenue'])->toBe($s['goods'] + $s['shipping'] - $s['discount']);
    expect(SalesReport::forPreset('90d')->summary()['orders'])->toBe(3);
});

it('compares with the previous period and fills empty days in the series', function () {
    paidOrder($this, 2);
    paidOrder($this, 10);
    $r = SalesReport::forPreset('7d');
    $series = $r->series();
    expect($series)->toHaveCount(7)->and(collect($series)->sum('count'))->toBe(1)
        ->and($r->previous()->summary()['orders'])->toBe(1)
        ->and(SalesReport::delta(2, 1))->toBe(100)->and(SalesReport::delta(5, 0))->toBeNull()
        ->and(SalesReport::forPreset('custom', now()->subDays(100)->toDateString(), now()->toDateString())->granularity())->toBe('month');
});

it('groups by coffee, roast level and voucher', function () {
    Voucher::create(['code' => 'HEMAT', 'type' => 'fixed', 'value' => 10000, 'is_active' => true]);
    $o = paidOrder($this, 1, 200, 'HEMAT');
    paidOrder($this, 2, 500)->update(['user_id' => User::where('email', 'raka@brule.test')->first()->id]);

    $r = SalesReport::forPreset('7d');
    expect($r->byCoffee()->sum('grams'))->toBe(700)->and($r->byLevel()->sum('grams'))->toBe(700)
        ->and($r->byVoucher()->first())->toMatchArray(['code' => 'HEMAT', 'uses' => 1, 'discount' => 10000])
        ->and($o->discount)->toBe(10000);
});

it('switches presets and handles custom ranges safely', function () {
    paidOrder($this, 2);
    $t = Livewire::actingAs($this->admin)->test(Reports::class)->assertSet('preset', '30d')
        ->call('setPreset', '7d')->assertSet('preset', '7d')
        ->call('setPreset', 'nonsense')->assertSet('preset', '7d')
        ->call('setPreset', 'custom')->assertSet('preset', 'custom')
        ->set('from', 'bukan-tanggal')->set('to', 'x')->assertOk();
    expect(SalesReport::forPreset('custom', '2026-10-10', '2026-10-01')->days())->toBe(10)
        ->and(SalesReport::forPreset('custom', '2020-01-01', '2026-10-01')->days())->toBe(366);
    $t->set('from', now()->subDays(3)->toDateString())->set('to', now()->toDateString())->assertSee('4 hari');
});

it('exports a multi-sheet xlsx and a multi-section pdf', function () {
    $o = paidOrder($this, 1);

    $r = $this->actingAs($this->admin)->get(route('admin.reports.export', ['preset' => '7d', 'format' => 'xlsx']));
    $r->assertOk();
    expect($r->headers->get('content-disposition'))->toContain('.xlsx');
    $tmp = tempnam(sys_get_temp_dir(), 't'); file_put_contents($tmp, $r->getContent());
    $zip = new ZipArchive; expect($zip->open($tmp))->toBeTrue();
    $wb = $zip->getFromName('xl/workbook.xml');
    expect(substr_count($wb, '<sheet '))->toBe(7)->and($zip->getFromName('xl/worksheets/sheet7.xml'))->toContain($o->order_code);
    foreach (range(1, 7) as $i) {
        expect(simplexml_load_string($zip->getFromName("xl/worksheets/sheet$i.xml")))->not->toBeFalse();
    }

    $pdf = $this->actingAs($this->admin)->get(route('admin.reports.export', ['preset' => '7d', 'format' => 'pdf']))->getContent();
    expect($pdf)->toStartWith('%PDF-1.4')->toContain('Laporan penjualan')->toContain('Per kopi')->toContain('%%EOF');
    preg_match('/startxref\n(\d+)/', $pdf, $m);
    expect(substr($pdf, (int) $m[1], 4))->toBe('xref');
});

it('flows long pdf tables across pages', function () {
    $rows = array_map(fn ($i) => ["Baris $i", 'Rp' . $i], range(1, 120));
    $pdf = SimplePdf::report('T', 's', [['heading' => 'A', 'headers' => ['x', 'y'], 'rows' => $rows, 'weights' => [1, 1]], ['heading' => 'B', 'headers' => ['x', 'y'], 'rows' => $rows, 'weights' => [1, 1]]]);
    preg_match('/\/Count (\d+)/', $pdf, $m);
    expect((int) $m[1])->toBeGreaterThan(5)->and($pdf)->toContain('Halaman 1/' . $m[1])->toContain('Baris 120');
});
