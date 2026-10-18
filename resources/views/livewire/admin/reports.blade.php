@php
    $r = $this->report;
    $s = $r->summary();
    $p = $this->previous;
    $series = $r->series();
    $coffee = $r->byCoffee();
    $level = $r->byLevel();
    $gear = $r->byProduct();
    $vouchers = $r->byVoucher();
    $rp = fn ($v) => 'Rp' . number_format($v, 0, ',', '.');
    $n = fn ($v) => number_format($v, 0, ',', '.');
    $short = fn ($v) => $v >= 1000000 ? rtrim(rtrim(number_format($v / 1000000, 1, ',', '.'), '0'), ',') . 'jt' : ($v >= 1000 ? round($v / 1000) . 'rb' : (string) round($v));
    $max = max(1, collect($series)->max('total'));
    $step = 10 ** max(0, strlen((string) $max) - 2);
    $niceMax = (int) (ceil($max / $step) * $step);
    $inp = 'rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13px] focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40 [color-scheme:dark]';
    $q = $this->exportQuery();
    $totalRev = max(1, $coffee->sum('revenue'));
    $totalLvl = max(1, $level->sum('revenue'));
    $dl = '<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>';
@endphp
<div>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Laporan penjualan') }}</h1>
            <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ $r->from->translatedFormat('j M Y') }} – {{ $r->to->translatedFormat('j M Y') }} · {{ $r->days() }} {{ __('hari') }} · {{ __('hanya pesanan yang sudah dibayar dan tidak dibatalkan') }}</p>
        </div>
        <x-download-menu :xlsx="route('admin.reports.export', $q + ['format' => 'xlsx'])" :pdf="route('admin.reports.export', $q + ['format' => 'pdf'])" />
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <div class="inline-flex flex-wrap rounded border border-kraft/25 p-0.5 text-[12.5px]" role="radiogroup" aria-label="{{ __('Periode') }}">
            @foreach (\App\Support\SalesReport::PRESETS as $k => $l)
                <button type="button" role="radio" aria-checked="{{ $preset === $k ? 'true' : 'false' }}" wire:click="setPreset('{{ $k }}')"
                        class="krm-readout rounded-[6px] px-3 py-1.5 transition-colors {{ $preset === $k ? 'bg-bluelab text-paper' : 'text-kraft/65 hover:text-labwhite' }}">{{ __($l) }}</button>
            @endforeach
        </div>
        @if ($preset === 'custom')
            <div class="flex items-center gap-2">
                <label class="sr-only" for="rf">{{ __('Dari tanggal') }}</label>
                <input id="rf" type="date" wire:model.live="from" placeholder="2026-10-01" class="{{ $inp }} krm-readout">
                <span class="text-kraft/45">–</span>
                <label class="sr-only" for="rt">{{ __('Sampai tanggal') }}</label>
                <input id="rt" type="date" wire:model.live="to" placeholder="2026-10-16" class="{{ $inp }} krm-readout">
            </div>
        @endif
    </div>

    <div wire:loading.class="opacity-60" class="transition-opacity">
        <dl class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach ([
                [__('Omzet'), $rp($s['revenue']), \App\Support\SalesReport::delta($s['revenue'], $p['revenue'])],
                [__('Pesanan dibayar'), $n($s['orders']), \App\Support\SalesReport::delta($s['orders'], $p['orders'])],
                [__('Rata-rata per pesanan'), $rp($s['aov']), null],
                [__('Diskon voucher'), $rp($s['discount']), null],
                [__('Berat terjual'), $n($s['grams']) . ' g', null],
            ] as [$l, $v, $d])
                <div class="rounded border border-kraft/20 bg-charcoal px-4 py-3">
                    <dt class="text-[12px] text-kraft/60">{{ $l }}</dt>
                    <dd class="krm-readout mt-1 truncate text-[1.25rem] leading-tight">{{ $v }}</dd>
                    <p class="mt-1.5 min-h-[1rem] text-[11.5px] {{ $d === null ? 'text-kraft/40' : ($d >= 0 ? 'text-bluetext' : 'text-ambertext') }}">
                        @if ($d !== null){{ $d >= 0 ? '▲' : '▼' }} {{ abs($d) }}% {{ __('vs periode sebelumnya') }}@else&nbsp;@endif
                    </p>
                </div>
            @endforeach
        </dl>
        <p class="mt-2 text-[12px] text-kraft/50">{{ __('Omzet') }} {{ $rp($s['revenue']) }} = {{ __('kopi') }} {{ $rp($s['goods']) }} + {{ __('ongkir') }} {{ $rp($s['shipping']) }} + {{ __('tambahan') }} {{ $rp($s['addons']) }} − {{ __('diskon') }} {{ $rp($s['discount']) }}</p>

        <section class="mt-4 rounded border border-kraft/20 bg-charcoal p-5">
            <h2 class="font-display text-[1rem] font-semibold">{{ $r->granularity() === 'month' ? __('Omzet per bulan') : __('Omzet per hari') }}</h2>
            @if ($s['orders'] === 0)
                <p class="grid min-h-[200px] place-items-center text-[13px] text-kraft/45">{{ __('Belum ada pesanan dibayar di rentang ini.') }}</p>
            @else
                <div class="mt-5 flex min-h-[220px] gap-3">
                    <div class="krm-readout flex flex-col justify-between text-right text-[10.5px] text-kraft/45" aria-hidden="true"><span>{{ $short($niceMax) }}</span><span>{{ $short($niceMax / 2) }}</span><span>0</span></div>
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true"><span class="border-t border-dashed border-kraft/15"></span><span class="border-t border-dashed border-kraft/15"></span><span class="border-t border-kraft/25"></span></div>
                        <div class="absolute inset-0 flex items-end gap-[3px]" role="img" aria-label="{{ __('Grafik batang omzet') }}">
                            @foreach ($series as $i => $d)
                                @php $h = $d['total'] > 0 ? max(2, round($d['total'] / $niceMax * 100)) : 0; $edge = $i > count($series) * 0.7; @endphp
                                <div class="group relative flex h-full min-w-0 flex-1 items-end">
                                    <div class="w-full rounded-t-[3px] bg-bluelab transition-colors group-hover:bg-[#4A7BD0]" style="height: {{ $h }}%"></div>
                                    <div class="pointer-events-none absolute bottom-full {{ $edge ? 'right-0' : 'left-1/2 -translate-x-1/2' }} z-10 mb-2 hidden whitespace-nowrap rounded border border-kraft/30 bg-surface px-2.5 py-1.5 text-[11.5px] shadow-lg group-hover:block">
                                        <span class="text-kraft/60">{{ $d['label'] }}</span><br><span class="krm-readout">{{ $rp($d['total']) }}</span> · {{ $d['count'] }} {{ __('pesanan') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="krm-readout mt-2 flex justify-between pl-[calc(2.4rem+0.75rem)] text-[11px] text-kraft/45"><span>{{ $series[0]['label'] }}</span><span>{{ end($series)['label'] }}</span></div>
            @endif
        </section>

        <div class="mt-4 grid gap-4 xl:grid-cols-2">
            @foreach ([[__('Per kopi'), $coffee, 'name', $totalRev, __('Kopi')], [__('Per level roast'), $level, 'level', $totalLvl, __('Level')]] as [$title, $rows, $field, $total, $col])
                <section class="min-w-0 rounded border border-kraft/20 bg-charcoal">
                    <header class="border-b border-kraft/15 px-5 py-3.5"><h2 class="font-display text-[1rem] font-semibold">{{ $title }}</h2></header>
                    @if ($rows->isEmpty())
                        <p class="px-5 py-10 text-center text-[13px] text-kraft/45">{{ __('Belum ada data.') }}</p>
                    @else
                        <table class="w-full text-left text-[13px]">
                            <thead class="krm-readout text-[10.5px] uppercase tracking-[0.12em] text-kraft/45"><tr class="border-b border-kraft/10"><th class="px-3 py-2 font-normal sm:px-5">{{ $col }}</th><th class="hidden px-2 py-2 text-right font-normal sm:table-cell">{{ __('Pesanan') }}</th><th class="px-2 py-2 text-right font-normal">{{ __('Berat') }}</th><th class="px-3 py-2 text-right font-normal sm:px-5">{{ __('Penjualan') }}</th></tr></thead>
                            <tbody class="divide-y divide-kraft/10">
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="max-w-[140px] px-3 py-2.5 sm:max-w-[220px] sm:px-5"><p class="truncate">{{ $row[$field] }}</p><div class="mt-1.5 h-[3px] rounded-full bg-labwhite/10"><div class="h-[3px] rounded-full bg-bluelab" style="width: {{ round($row['revenue'] / $total * 100) }}%"></div></div></td>
                                        <td class="krm-readout hidden px-2 py-2.5 text-right text-kraft/70 sm:table-cell">{{ $row['orders'] }}</td>
                                        <td class="krm-readout whitespace-nowrap px-2 py-2.5 text-right text-kraft/70">{{ $n($row['grams']) }} g</td>
                                        <td class="krm-readout whitespace-nowrap px-3 py-2.5 text-right sm:px-5">{{ $rp($row['revenue']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
            @endforeach
        </div>

        @if ($gear->isNotEmpty())
            <section class="mt-4 min-w-0 rounded border border-kraft/20 bg-charcoal">
                <header class="border-b border-kraft/15 px-5 py-3.5"><h2 class="font-display text-[1rem] font-semibold">{{ __('Per perlengkapan') }}</h2></header>
                <table class="w-full text-left text-[13px]">
                    <thead class="krm-readout text-[10.5px] uppercase tracking-[0.12em] text-kraft/45"><tr class="border-b border-kraft/10"><th class="px-3 py-2 font-normal sm:px-5">{{ __('Produk') }}</th><th class="hidden px-2 py-2 text-right font-normal sm:table-cell">{{ __('Pesanan') }}</th><th class="px-2 py-2 text-right font-normal">{{ __('Unit') }}</th><th class="px-3 py-2 text-right font-normal sm:px-5">{{ __('Penjualan') }}</th></tr></thead>
                    <tbody class="divide-y divide-kraft/10">
                        @foreach ($gear as $row)
                            <tr>
                                <td class="max-w-[200px] truncate px-3 py-2.5 sm:px-5">{{ $row['name'] }}</td>
                                <td class="krm-readout hidden px-2 py-2.5 text-right text-kraft/70 sm:table-cell">{{ $row['orders'] }}</td>
                                <td class="krm-readout px-2 py-2.5 text-right text-kraft/70">{{ $n($row['units']) }}</td>
                                <td class="krm-readout whitespace-nowrap px-3 py-2.5 text-right sm:px-5">{{ $rp($row['revenue']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
            <header class="border-b border-kraft/15 px-5 py-3.5"><h2 class="font-display text-[1rem] font-semibold">{{ __('Pemakaian voucher') }}</h2></header>
            @if ($vouchers->isEmpty())
                <p class="px-5 py-8 text-center text-[13px] text-kraft/45">{{ __('Tidak ada voucher yang dipakai di rentang ini.') }}</p>
            @else
                <ul class="divide-y divide-kraft/10">
                    @foreach ($vouchers as $v)
                        <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-[13px]"><span class="krm-readout">{{ $v['code'] }}</span><span class="text-kraft/60">{{ $v['uses'] }}× {{ __('dipakai') }}</span><span class="krm-readout text-bluetext">− {{ $rp($v['discount']) }}</span></li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
