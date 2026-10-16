@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $short = fn ($n) => $n >= 1000000 ? rtrim(rtrim(number_format($n / 1000000, 1, ',', '.'), '0'), ',') . 'jt' : ($n >= 1000 ? round($n / 1000) . 'rb' : (string) $n);
    $k = $this->kpis;
    $daily = $this->daily;
    $rawMax = max(1, collect($daily)->max('total'));
    $pow = 10 ** floor(log10($rawMax));
    $niceMax = ceil($rawMax / $pow) * $pow;
    $spark = function (array $vals) {
        $n = count($vals); $max = max(1, max($vals));
        return collect($vals)->map(fn ($v, $i) => round($i / max(1, $n - 1) * 100, 1) . ',' . round(30 - ($v / $max) * 26, 1))->implode(' ');
    };
    $statusTone = fn ($s) => match ($s) {
        'pending_payment' => 'border-[#E8782A]/50 text-[#F2A266]',
        'completed', 'shipped' => 'border-bluelab/60 text-[#9DBBEF]',
        'cancelled' => 'border-kraft/20 text-kraft/45',
        default => 'border-kraft/35 text-kraft',
    };
    $att = $this->attention;
@endphp
<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-[1.6rem] font-bold leading-tight">Dashboard</h1>
            <p class="mt-1 text-[13.5px] text-kraft/60">{{ __('Ringkasan toko :n hari terakhir', ['n' => $days]) }}</p>
        </div>
        <div role="group" aria-label="{{ __('Rentang waktu') }}" class="flex rounded border border-kraft/25 p-0.5">
            @foreach ([7, 14, 30] as $d)
                <button type="button" wire:click="$set('days', {{ $d }})" aria-pressed="{{ $days === $d ? 'true' : 'false' }}"
                        class="krm-readout rounded-[8px] px-3.5 py-1.5 text-[12.5px] transition-colors {{ $days === $d ? 'bg-bluelab text-labwhite' : 'text-kraft/70 hover:text-labwhite' }}">{{ $d }} {{ __('hari') }}</button>
            @endforeach
        </div>
    </div>

    {{-- Perlu tindakan --}}
    @php $todo = array_filter([
        [$att['unpaid'], __('menunggu pembayaran'), route('admin.dashboard', ['tab' => 'unpaid']) . '#pesanan'],
        [$att['toShip'], __('siap dikirim (isi resi)'), route('roastery')],
        [$att['low'], __('kopi stok menipis'), route('admin.coffees')],
    ], fn ($t) => $t[0] > 0); @endphp
    <div class="mt-5 flex flex-wrap items-center gap-2 rounded border border-kraft/20 bg-[#121212] px-4 py-3 text-[13px]">
        <span class="krm-readout mr-1 text-[11px] uppercase tracking-[0.16em] text-kraft/50">{{ __('Perlu tindakan') }}</span>
        @forelse ($todo as [$n, $label, $url])
            <a href="{{ $url }}" class="inline-flex items-center gap-2 rounded border border-[#E8782A]/45 bg-[#E8782A]/[.07] px-3 py-1.5 transition-colors hover:bg-[#E8782A]/15">
                <span class="krm-readout font-semibold text-[#F2A266]">{{ $n }}</span> {{ $label }} <span aria-hidden="true">&rarr;</span>
            </a>
        @empty
            <span class="text-kraft/60">{{ __('Semua beres, tidak ada yang perlu ditindak.') }}</span>
        @endforelse
        <span class="ml-auto hidden text-kraft/50 sm:inline">{{ $att['queue'] }} {{ __('pesanan sedang diproduksi') }}</span>
    </div>

    {{-- KPI --}}
    <dl class="mt-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            ['revenue', __('Omzet'), $rp($k['revenue']['value']), $k['revenue']['delta'], collect($daily)->pluck('total')->all()],
            ['orders', __('Pesanan dibayar'), number_format($k['orders']['value']), $k['orders']['delta'], collect($daily)->pluck('count')->all()],
            ['aov', __('Rata-rata per pesanan'), $rp($k['aov']['value']), null, null],
            ['customers', __('Pelanggan baru'), number_format($k['customers']['value']), null, null],
        ] as [$key, $label, $val, $delta, $series])
            <div class="rounded border border-kraft/20 bg-[#121212] p-4">
                <dt class="flex items-center justify-between text-[12.5px] text-kraft/60">
                    {{ $label }}
                    @if ($delta !== null)
                        <span class="krm-readout rounded-[6px] px-1.5 py-0.5 text-[11px] {{ $delta >= 0 ? 'bg-bluelab/20 text-[#9DBBEF]' : 'bg-[#E8782A]/15 text-[#F2A266]' }}">{{ $delta >= 0 ? '▲' : '▼' }} {{ abs($delta) }}%</span>
                    @endif
                </dt>
                <dd class="krm-readout mt-2 whitespace-nowrap text-[1.05rem] leading-none min-[420px]:text-[1.3rem] sm:text-[1.55rem]">{{ $val }}</dd>
                @if ($series && array_sum($series) > 0)
                    <svg viewBox="0 0 100 32" preserveAspectRatio="none" class="mt-3 h-8 w-full" aria-hidden="true">
                        <polyline points="{{ $spark($series) }}" fill="none" stroke="#2A5DB0" stroke-width="1.6" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                @else
                    <p class="mt-3 text-[11.5px] text-kraft/40">{{ $delta === null && $series !== null ? __('Belum ada pembanding') : ($key === 'customers' ? __('akun customer baru') : __('per pesanan dibayar')) }}</p>
                @endif
            </div>
        @endforeach
    </dl>

    {{-- Grafik + status --}}
    <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.8fr)_minmax(0,1fr)]">
        <section class="flex flex-col rounded border border-kraft/20 bg-[#121212] p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-display text-[1rem] font-semibold">{{ __('Omzet harian') }}</h2>
                <span class="krm-readout text-[11.5px] text-kraft/50">{{ number_format($k['grams']) }} g {{ __('terjual') }}</span>
            </div>
            @if (collect($daily)->sum('total') === 0)
                <p class="grid min-h-[210px] flex-1 place-items-center text-[13px] text-kraft/45">{{ __('Belum ada pesanan dibayar di rentang ini.') }}</p>
            @else
                <div class="mt-5 flex min-h-[210px] flex-1 gap-3">
                    <div class="krm-readout flex flex-col justify-between pb-0 text-right text-[10.5px] text-kraft/45" aria-hidden="true">
                        <span>{{ $short($niceMax) }}</span><span>{{ $short($niceMax / 2) }}</span><span>0</span>
                    </div>
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-0 flex flex-col justify-between" aria-hidden="true">
                            <span class="border-t border-dashed border-kraft/15"></span><span class="border-t border-dashed border-kraft/15"></span><span class="border-t border-kraft/25"></span>
                        </div>
                        <div class="absolute inset-0 flex items-end gap-[3px]" role="img" aria-label="{{ __('Grafik batang omzet harian') }}">
                            @foreach ($daily as $i => $d)
                                @php $h = $d['total'] > 0 ? max(2, round($d['total'] / $niceMax * 100)) : 0; @endphp
                                <div class="group relative flex h-full min-w-0 flex-1 items-end">
                                    <div class="w-full rounded-t-[3px] bg-bluelab transition-all duration-500 group-hover:bg-[#4A7BD0]" style="height: {{ $h }}%"></div>
                                    <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded border border-kraft/30 bg-[#1b1b1b] px-2.5 py-1.5 text-[11.5px] shadow-lg group-hover:block {{ $i < 3 ? 'translate-x-0 left-0' : '' }}">
                                        <span class="text-kraft/60">{{ $d['date']->translatedFormat('D, j M') }}</span><br><span class="krm-readout">{{ $rp($d['total']) }}</span> · {{ $d['count'] }} {{ __('pesanan') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="krm-readout mt-2 flex justify-between pl-[calc(2.4rem+0.75rem)] text-[11px] text-kraft/45">
                    <span>{{ $daily[0]['date']->translatedFormat('j M') }}</span>
                    <span>{{ $daily[intdiv(count($daily), 2)]['date']->translatedFormat('j M') }}</span>
                    <span>{{ end($daily)['date']->translatedFormat('j M') }}</span>
                </div>
            @endif
        </section>

        <section class="rounded border border-kraft/20 bg-[#121212] p-5">
            <h2 class="font-display text-[1rem] font-semibold">{{ __('Semua pesanan per status') }}</h2>
            @php $smax = max(1, collect($this->statuses)->max('n')); @endphp
            <ul class="mt-4 space-y-3">
                @foreach ($this->statuses as $s)
                    <li>
                        <div class="flex justify-between text-[12.5px]"><span class="text-kraft/80">{{ __($s['label']) }}</span><span class="krm-readout">{{ $s['n'] }}</span></div>
                        <div class="mt-1 h-1 rounded-full bg-white/10"><div class="h-1 rounded-full transition-all duration-500 {{ $s['status'] === 'cancelled' ? 'bg-kraft/40' : ($s['status'] === 'pending_payment' ? 'bg-[#E8782A]' : 'bg-bluelab') }}" style="width: {{ round($s['n'] / $smax * 100) }}%"></div></div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- Tabel pesanan --}}
    <section id="pesanan" class="mt-4 scroll-mt-20 rounded border border-kraft/20 bg-[#121212]">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-4">
            <h2 class="font-display text-[1rem] font-semibold">{{ __('Pesanan') }}</h2>
            <div class="relative w-full sm:w-80">
                <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-kraft/45" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4-4"/></svg>
                <label class="sr-only" for="q">{{ __('Cari pesanan') }}</label>
                <input id="q" type="search" wire:model.live.debounce.350ms="search" placeholder="{{ __('Cari kode / nama, cth: Sinta') }}"
                       class="w-full rounded border border-kraft/25 bg-transparent py-2 pl-9 pr-3 text-[13.5px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
            </div>
        </div>

        <div class="mt-3 flex gap-1 overflow-x-auto border-b border-kraft/15 px-3" role="tablist">
            @foreach (\App\Livewire\Admin\Dashboard::TABS as $key => [$label])
                <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="setTab('{{ $key }}')"
                        class="-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-[13px] transition-colors {{ $tab === $key ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">
                    {{ __($label) }}<span class="krm-readout rounded-[6px] bg-white/[.07] px-1.5 text-[11px]">{{ $this->tabCounts[$key] }}</span>
                </button>
            @endforeach
        </div>

        @php $orders = $this->orders; @endphp
        <div wire:loading.class="opacity-60" class="transition-opacity">
            <table class="hidden w-full text-left text-[13.5px] md:table">
                <thead class="krm-readout text-[11px] uppercase tracking-[0.12em] text-kraft/45">
                    <tr class="border-b border-kraft/10"><th class="px-5 py-2.5 font-normal">{{ __('Kode') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Pelanggan') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Item') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Waktu') }}</th><th class="px-3 py-2.5 text-right font-normal">{{ __('Total') }}</th><th class="px-5 py-2.5 font-normal">{{ __('Status') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-kraft/10">
                    @foreach ($orders as $o)
                        <tr wire:key="o-{{ $o->id }}" class="transition-colors hover:bg-white/[.025]">
                            <td class="px-5 py-3"><a href="{{ route('track', $o->order_code) }}" class="krm-readout text-[12.5px] underline-offset-4 hover:underline">{{ $o->order_code }}</a></td>
                            <td class="max-w-[170px] truncate px-3 py-3">{{ $o->user?->name ?? $o->recipient_name }}</td>
                            <td class="max-w-[240px] truncate px-3 py-3 text-kraft/65">{{ $o->items->map(fn ($i) => $i->coffee_name_snapshot . ' ' . $i->grams . 'g')->implode(', ') }}</td>
                            <td class="krm-readout whitespace-nowrap px-3 py-3 text-[12px] text-kraft/55">{{ $o->created_at->translatedFormat('j M, H:i') }}</td>
                            <td class="krm-readout whitespace-nowrap px-3 py-3 text-right">{{ $rp($o->total) }}</td>
                            <td class="px-5 py-3"><span class="whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11.5px] {{ $statusTone($o->status) }}">{{ $o->status_label }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="divide-y divide-kraft/10 md:hidden">
                @foreach ($orders as $o)
                    <li wire:key="m-{{ $o->id }}" class="px-5 py-3.5">
                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ route('track', $o->order_code) }}" class="krm-readout text-[12.5px]">{{ $o->order_code }}</a>
                            <span class="rounded-[6px] border px-2 py-0.5 text-[11.5px] {{ $statusTone($o->status) }}">{{ $o->status_label }}</span>
                        </div>
                        <p class="mt-1 truncate text-[13.5px]">{{ $o->user?->name ?? $o->recipient_name }}</p>
                        <div class="mt-0.5 flex justify-between text-[12px] text-kraft/55"><span>{{ $o->created_at->translatedFormat('j M, H:i') }}</span><span class="krm-readout text-labwhite">{{ $rp($o->total) }}</span></div>
                    </li>
                @endforeach
            </ul>

            @if ($orders->isEmpty())
                <p class="px-5 py-10 text-center text-[13px] text-kraft/45">{{ $search !== '' ? __('Tidak ada pesanan yang cocok dengan pencarian.') : __('Belum ada pesanan.') }}</p>
            @endif
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-kraft/15 px-5 py-3 text-[12.5px] text-kraft/60">
            <span>{{ $orders->total() ? __('Menampilkan :a–:b dari :t', ['a' => $orders->firstItem(), 'b' => $orders->lastItem(), 't' => $orders->total()]) : '' }}</span>
            <div class="flex items-center gap-2">
                <button wire:click="previousPage" @disabled($orders->onFirstPage()) class="whitespace-nowrap rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Sebelumnya') }}">&larr;<span class="hidden sm:inline"> {{ __('Sebelumnya') }}</span></button>
                <span class="krm-readout">{{ $orders->currentPage() }}/{{ $orders->lastPage() }}</span>
                <button wire:click="nextPage" @disabled(! $orders->hasMorePages()) class="whitespace-nowrap rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Berikutnya') }}"><span class="hidden sm:inline">{{ __('Berikutnya') }} </span>&rarr;</button>
            </div>
        </div>
    </section>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        <section class="rounded border border-kraft/20 bg-[#121212] p-5">
            <h2 class="font-display text-[1rem] font-semibold">{{ __('Kopi terlaris') }}</h2>
            @php $tmax = max(1, (int) $this->topCoffees->max('grams')); @endphp
            @forelse ($this->topCoffees as $i => $c)
                <div class="mt-4">
                    <div class="flex items-baseline justify-between gap-3 text-[13.5px]">
                        <span class="min-w-0 truncate"><span class="krm-readout mr-2 text-kraft/40">{{ $i + 1 }}</span>{{ $c->name }}</span>
                        <span class="krm-readout shrink-0 text-[12.5px] text-kraft/70">{{ number_format($c->grams) }} g · {{ $rp($c->revenue) }}</span>
                    </div>
                    <div class="mt-1.5 h-1 rounded-full bg-white/10"><div class="h-1 rounded-full bg-bluelab transition-all duration-500" style="width: {{ round($c->grams / $tmax * 100) }}%"></div></div>
                </div>
            @empty
                <p class="mt-6 text-[13px] text-kraft/45">{{ __('Belum ada penjualan di rentang ini.') }}</p>
            @endforelse

            @if ($this->levels->isNotEmpty())
                @php $lsum = max(1, (int) $this->levels->sum('grams')); @endphp
                <h3 class="mt-7 text-[12.5px] text-kraft/60">{{ __('Level sangrai favorit') }}</h3>
                <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-white/10">
                    @foreach ($this->levels as $i => $lv)
                        <div style="width: {{ round($lv->grams / $lsum * 100, 1) }}%; opacity: {{ 1 - $i * 0.17 }}" class="bg-bluelab" title="{{ \App\Models\RoastProfile::LEVELS[$lv->level] ?? $lv->level }}"></div>
                    @endforeach
                </div>
                <ul class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-[12px] text-kraft/70">
                    @foreach ($this->levels as $lv)<li><span class="krm-readout">{{ \App\Models\RoastProfile::LEVELS[$lv->level] ?? $lv->level }}</span> {{ round($lv->grams / $lsum * 100) }}%</li>@endforeach
                </ul>
            @endif
        </section>

        <section class="rounded border border-kraft/20 bg-[#121212] p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-display text-[1rem] font-semibold">{{ __('Stok menipis') }}</h2>
                <span class="krm-readout text-[11.5px] text-kraft/50">&lt; {{ number_format(\App\Livewire\Admin\Dashboard::LOW_STOCK_GRAM) }} g</span>
            </div>
            @forelse ($this->lowStock as $c)
                <div class="mt-4 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-[13.5px]">{{ $c->name }}</p>
                        <p class="krm-readout text-[12px] text-[#F2A266]">{{ number_format($c->stock_green_bean_gram) }} g</p>
                    </div>
                    <a href="{{ route('admin.coffees.edit', $c) }}" wire:navigate class="shrink-0 rounded border border-kraft/30 px-3 py-1.5 text-[12.5px] transition-colors hover:border-kraft/70">{{ __('Isi stok') }}</a>
                </div>
            @empty
                <p class="mt-6 text-[13px] text-kraft/55">{{ __('Semua stok aman.') }}</p>
            @endforelse
        </section>
    </div>
</div>
