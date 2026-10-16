@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $k = $this->kpis;
    $daily = $this->daily;
    $max = max(1, collect($daily)->max('total'));
    $statusTone = fn ($s) => match ($s) {
        'pending_payment' => 'border-[#E8782A]/50 text-[#F2A266]',
        'completed', 'shipped' => 'border-bluelab/60 text-labwhite',
        'cancelled' => 'border-kraft/20 text-kraft/50',
        default => 'border-kraft/35 text-kraft',
    };
@endphp
<x-admin-shell active="dashboard">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="krm-readout text-[12px] tracking-[0.18em] text-kraft/60">{{ __('RINGKASAN') }}</p>
            <h1 class="krm-h2 mt-2 font-bold">{{ __('Dashboard admin') }}</h1>
        </div>
        <div role="group" aria-label="{{ __('Rentang waktu') }}" class="flex rounded border border-kraft/25 p-0.5">
            @foreach ([7, 14, 30] as $d)
                <button type="button" wire:click="$set('days', {{ $d }})" aria-pressed="{{ $days === $d ? 'true' : 'false' }}"
                        class="krm-readout rounded-[8px] px-3.5 py-1.5 text-[12.5px] transition-colors {{ $days === $d ? 'bg-bluelab text-labwhite' : 'text-kraft/70 hover:text-labwhite' }}">{{ $d }} {{ __('hari') }}</button>
            @endforeach
        </div>
    </div>

    {{-- Perlu perhatian --}}
    <ul class="mt-7 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            [__('Menunggu bayar'), $this->attention['unpaid'], route('roastery'), $this->attention['unpaid'] > 0],
            [__('Dalam produksi'), $this->attention['queue'], route('roastery'), false],
            [__('Siap dikirim'), $this->attention['toShip'], route('roastery'), $this->attention['toShip'] > 0],
            [__('Stok menipis'), $this->attention['low'], route('admin.coffees'), $this->attention['low'] > 0],
        ] as [$l, $n, $url, $warn])
            <li>
                <a href="{{ $url }}" class="group block rounded border px-4 py-3.5 transition-colors hover:border-kraft/50 {{ $warn ? 'border-[#E8782A]/45 bg-[#E8782A]/[.06]' : 'border-kraft/20' }}">
                    <span class="flex items-center justify-between text-[12.5px] text-kraft/65">{{ $l }}<span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">&rarr;</span></span>
                    <span class="krm-readout mt-1 block text-[1.6rem] leading-none {{ $warn ? 'text-[#F2A266]' : '' }}">{{ $n }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- KPI --}}
    <dl class="mt-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            ['revenue', __('Omzet'), $rp($k['revenue']['value']), $k['revenue']['delta']],
            ['orders', __('Pesanan dibayar'), number_format($k['orders']['value']), $k['orders']['delta']],
            ['aov', __('Rata-rata per pesanan'), $rp($k['aov']['value']), null],
            ['grams', __('Kopi terjual'), number_format($k['grams']) . ' g', null],
        ] as [$key, $label, $val, $delta])
            <div class="rounded border border-kraft/20 bg-[#121212] px-4 py-4 {{ in_array($key, ['revenue', 'grams']) ? 'col-span-2 xl:col-span-1' : '' }}">
                <dt class="text-[12.5px] text-kraft/60">{{ $label }}</dt>
                <dd class="krm-readout mt-2 text-[1.25rem] leading-none sm:text-[1.6rem]">{{ $val }}</dd>
                <p class="mt-2 min-h-[1.1rem] text-[12px] {{ $delta === null ? 'text-kraft/40' : ($delta >= 0 ? 'text-[#7FA4E6]' : 'text-[#F2A266]') }}">
                    @if ($delta !== null)
                        {{ $delta >= 0 ? '▲' : '▼' }} {{ abs($delta) }}% <span class="text-kraft/45">{{ __('vs :n hari sebelumnya', ['n' => $days]) }}</span>
                    @elseif (in_array($key, ['revenue', 'orders']))
                        <span>{{ __('Belum ada pembanding') }}</span>
                    @endif
                </p>
            </div>
        @endforeach
    </dl>

    <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]">
        {{-- Omzet harian --}}
        <section class="flex flex-col rounded border border-kraft/20 bg-[#121212] p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="font-display text-[1rem] font-semibold">{{ __('Omzet harian') }}</h2>
                <span class="krm-readout text-[11.5px] text-kraft/50">{{ __('puncak') }} {{ $rp($max > 1 ? $max : 0) }}</span>
            </div>
            @if (collect($daily)->sum('total') === 0)
                <p class="grid h-[190px] place-items-center text-[13px] text-kraft/45">{{ __('Belum ada pesanan dibayar di rentang ini.') }}</p>
            @else
                <div class="mt-5 flex min-h-[190px] flex-1 items-end gap-[3px] border-b border-kraft/20" role="img" aria-label="{{ __('Grafik batang omzet harian') }}">
                    @foreach ($daily as $d)
                        @php $h = $d['total'] > 0 ? max(3, round($d['total'] / $max * 100)) : 0; @endphp
                        <div class="group relative flex h-full min-w-0 flex-1 items-end" title="{{ $d['date']->translatedFormat('j M') }} · {{ $rp($d['total']) }}">
                            <div class="w-full rounded-t-[3px] bg-bluelab transition-all duration-500 group-hover:bg-[#4A7BD0]" style="height: {{ $h }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="krm-readout mt-2 flex justify-between text-[11px] text-kraft/45">
                    <span>{{ $daily[0]['date']->translatedFormat('j M') }}</span>
                    <span>{{ end($daily)['date']->translatedFormat('j M') }}</span>
                </div>
            @endif
        </section>

        {{-- Status pesanan --}}
        <section class="rounded border border-kraft/20 bg-[#121212] p-5">
            <h2 class="font-display text-[1rem] font-semibold">{{ __('Semua pesanan per status') }}</h2>
            @php $smax = max(1, collect($this->statuses)->max('n')); @endphp
            <ul class="mt-4 space-y-2.5">
                @foreach ($this->statuses as $s)
                    <li>
                        <div class="flex justify-between text-[12.5px]"><span class="text-kraft/80">{{ __($s['label']) }}</span><span class="krm-readout">{{ $s['n'] }}</span></div>
                        <div class="mt-1 h-1 rounded-full bg-white/10"><div class="h-1 rounded-full transition-all duration-500 {{ $s['status'] === 'cancelled' ? 'bg-kraft/40' : ($s['status'] === 'pending_payment' ? 'bg-[#E8782A]' : 'bg-bluelab') }}" style="width: {{ round($s['n'] / $smax * 100) }}%"></div></div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        {{-- Kopi terlaris --}}
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

        {{-- Stok menipis --}}
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

    {{-- Pesanan terbaru --}}
    <section class="mt-4 rounded border border-kraft/20 bg-[#121212]">
        <div class="flex items-center justify-between border-b border-kraft/15 px-5 py-4">
            <h2 class="font-display text-[1rem] font-semibold">{{ __('Pesanan terbaru') }}</h2>
            <a href="{{ route('roastery') }}" class="text-[13px] text-kraft/70 underline-offset-4 hover:underline">{{ __('Antrian roastery') }} &rarr;</a>
        </div>
        <ul class="divide-y divide-kraft/10">
            @forelse ($this->recent as $o)
                <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-5 py-3.5">
                    <div class="min-w-0">
                        <a href="{{ route('track', $o->order_code) }}" class="krm-readout text-[12.5px] underline-offset-4 hover:underline">{{ $o->order_code }}</a>
                        <p class="truncate text-[12.5px] text-kraft/55">{{ $o->user?->name }} · {{ $o->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="krm-readout text-[13px]">{{ $rp($o->total) }}</span>
                        <span class="rounded-[6px] border px-2 py-0.5 text-[11.5px] {{ $statusTone($o->status) }}">{{ $o->status_label }}</span>
                    </div>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-[13px] text-kraft/45">{{ __('Belum ada pesanan.') }}</li>
            @endforelse
        </ul>
    </section>
</x-admin-shell>
