<div wire:poll.15s>
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
        <div>
            <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Antrian roastery') }}</h1>
            <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Diperbarui otomatis tiap 15 detik') }}</p>
        </div>
        <dl class="flex flex-wrap gap-2 text-[12px]">
            @foreach ([
                [__('Menunggu bayar'), $this->stats['waiting'], ''],
                [__('Produksi'), $this->stats['inProduction'], ''],
                [__('Berat antrian'), number_format($this->stats['grams']), ' g'],
                [__('Dikirim hari ini'), $this->stats['shippedToday'], ''],
            ] as [$l, $v, $u])
                <div class="flex items-baseline gap-2 rounded border border-kraft/20 bg-charcoal px-3 py-1.5">
                    <dt class="text-kraft/55">{{ $l }}</dt>
                    <dd class="krm-readout text-[14px]">{{ $v }}<span class="text-[11px] text-kraft/50">{{ $u }}</span></dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Rencana sangrai: apa yang perlu disangrai dari antrian masuk, dikelompokkan biji + level --}}
    @if ($this->batches->isNotEmpty())
        <section class="mt-4 rounded border border-kraft/20 bg-charcoal px-4 py-3">
            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <h2 class="text-[12.5px] font-semibold">{{ __('Rencana sangrai') }}</h2>
                <p class="text-[11.5px] text-kraft/50">{{ __('Dari antrian masuk, dikelompokkan supaya satu biji dan level disangrai sekali jalan.') }}</p>
            </div>
            <ul class="mt-2.5 flex max-h-[7.5rem] flex-wrap gap-2 overflow-y-auto">
                @foreach ($this->batches as $bt)
                    <li wire:key="bt-{{ $loop->index }}">
                        <button type="button" wire:click="filterBy(@js($bt['name']))" title="{{ __('Tampilkan pesanan biji ini') }}"
                                class="flex items-baseline gap-2 rounded border bg-surface px-2.5 py-1.5 text-left text-[12px] transition-colors hover:border-bluelab {{ trim($search) === $bt['name'] ? 'border-bluelab' : 'border-kraft/20' }}">
                            <span class="font-semibold">{{ $bt['name'] }}</span>
                            <span class="text-kraft/60">{{ $bt['level'] }}</span>
                            <span class="krm-readout">{{ number_format($bt['grams']) }} g</span>
                            <span class="text-[11px] text-kraft/45">{{ $bt['orders'] }} {{ __('pesanan') }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <label class="sr-only" for="q-orders">{{ __('Cari pesanan') }}</label>
        <input id="q-orders" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Contoh: BRL-20261018-0003, Sinta, atau Gayo') }}"
               class="min-w-0 flex-1 rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-1 focus:ring-bluelab/50 sm:max-w-[400px]">
        <label class="flex cursor-pointer items-center gap-2 rounded border px-3 py-2 text-[12.5px] transition-colors {{ $onlyLate ? 'border-[#C0392B] bg-[#C0392B]/10' : 'border-kraft/25 hover:border-kraft/50' }}">
            <input type="checkbox" wire:model.live="onlyLate" class="h-3.5 w-3.5 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
            {{ __('Hanya yang terlambat') }}
        </label>
        @if (trim($search) !== '' || $onlyLate)
            <button type="button" wire:click="$set('search', ''); $set('onlyLate', false)" class="text-[12.5px] text-kraft/65 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Reset') }}</button>
        @endif
    </div>
    @if ($notice)
        <p wire:key="notice" class="mt-3 rounded border border-bluelab/40 bg-bluelab/10 px-3 py-2 text-[12.5px]" x-data x-init="setTimeout(() => $wire.set('notice', ''), 6000)">{{ $notice }}</p>
    @endif

    <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($this->columns as $status => $col)
            <section class="flex min-w-0 flex-col rounded border border-kraft/20 bg-charcoal">
                <header class="border-b border-kraft/15 px-3 py-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-[12.5px] font-semibold">{{ __(\App\Models\Order::STATUS_LABELS[$status]) }}</h2>
                        <div class="flex items-center gap-2">
                            @if ($col['total'] > 0)<button type="button" wire:click="selectColumn('{{ $status }}')" class="text-[11px] text-kraft/55 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Pilih semua') }}</button>@endif
                            <span class="krm-readout rounded-[6px] bg-labwhite/[.07] px-1.5 text-[11px]">{{ $col['total'] }}</span>
                        </div>
                    </div>
                    <p class="krm-readout mt-0.5 flex items-center gap-2 text-[11px] text-kraft/50">
                        {{ number_format($col['grams']) }} g
                        @if ($col['late'])<span class="rounded-[6px] border border-[#C0392B]/50 px-1.5 text-[10.5px] text-[#C0392B]">{{ $col['late'] }} {{ __('terlambat') }}</span>@endif
                    </p>
                </header>
                <div class="max-h-[34rem] space-y-2 overflow-y-auto overscroll-contain p-2">
                    @forelse ($col['orders'] as $o)
                        @php $late = $this->isLate($o); $since = ($o->paid_at ?? $o->created_at); @endphp
                        <article wire:key="o-{{ $o->id }}" class="rounded border bg-surface px-2.5 py-2 {{ $late ? 'border-[#C0392B]/45' : 'border-kraft/15' }}">
                            <div class="flex items-center justify-between gap-2">
                                <label class="flex min-w-0 cursor-pointer items-center gap-2">
                                    <input type="checkbox" wire:model.live="selected" value="{{ $o->id }}" aria-label="{{ __('Pilih :code', ['code' => $o->order_code]) }}" class="h-3.5 w-3.5 shrink-0 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                                    <span class="krm-readout truncate text-[10.5px] text-kraft/50">{{ $o->order_code }}</span>
                                </label>
                                @php $roastG = (int) $o->items->sum('grams'); @endphp
                                <p class="krm-readout shrink-0 text-[12.5px]">{{ $roastG > 0 ? number_format($roastG).' g' : __('Barang') }}</p>
                            </div>
                            @foreach ($o->groupedItems() as $it)
                                <p class="mt-1 truncate text-[12.5px] font-semibold leading-snug">{{ $it->coffee_name_snapshot }}</p>
                                <p class="truncate text-[11.5px] text-kraft/60">{{ $it->descr }}{{ $it->qty > 1 ? ($it->descr ? ' × ' : '× ').$it->qty : '' }}</p>
                            @endforeach
                            <p class="mt-0.5 flex items-center justify-between gap-2 text-[11px] text-kraft/40">
                                <span class="truncate">{{ $o->recipient_name }}</span>
                                <span class="krm-readout shrink-0 {{ $late ? 'text-[#C0392B]' : '' }}" title="{{ $since->translatedFormat('j M Y, H:i') }}">{{ $since->diffForHumans(null, true, true) }}</span>
                            </p>
                            @if ($o->hasAddons())
                                <div class="mt-1.5 rounded border border-bluelab/40 bg-bluelab/10 px-2 py-1.5 text-[11.5px] leading-snug">
                                    @if ($o->gift_wrap)<p class="font-semibold">{{ __('Kemasan hadiah') }}</p>@endif
                                    @if (filled($o->card_message))<p class="{{ $o->gift_wrap ? 'mt-0.5' : '' }}"><span class="text-kraft/60">{{ __('Kartu') }}:</span> <span class="italic">&ldquo;{{ $o->card_message }}&rdquo;</span></p>@endif
                                </div>
                            @endif

                            @if ($status === 'packing')
                                <label class="sr-only" for="resi-{{ $o->id }}">{{ __('Nomor resi') }}</label>
                                <div class="mt-2 flex gap-1.5">
                                    <input id="resi-{{ $o->id }}" wire:model="tracking.{{ $o->id }}" placeholder="{{ __('Contoh: JNE1234567890') }}"
                                           class="krm-readout min-w-0 flex-1 rounded border border-kraft/25 bg-transparent px-2 py-1.5 text-[12px] placeholder:text-kraft/30 focus:border-bluelab focus:outline-none focus:ring-1 focus:ring-bluelab/50">
                                    <button wire:click="ship({{ $o->id }})" class="shrink-0 rounded bg-bluelab px-3 text-[12.5px] font-semibold transition-colors hover:bg-[#234E94]">{{ __('Kirim') }}</button>
                                </div>
                                @error("tracking.$o->id")<p class="mt-1 text-[11.5px] text-redtext">{{ $message }}</p>@enderror
                            @else
                                <button wire:click="advance({{ $o->id }})" class="mt-2 w-full rounded border border-bluelab/50 py-1.5 text-[12px] font-semibold transition-colors hover:bg-bluelab">
                                    {{ __(\App\Models\Order::STATUS_LABELS[$o->nextStatus()]) }} &rarr;
                                </button>
                            @endif
                        </article>
                    @empty
                        <p class="py-4 text-center text-[12px] text-kraft/35">{{ trim($search) !== '' ? __('Tidak ada yang cocok') : __('Kosong') }}</p>
                    @endforelse
                    @if ($col['hidden'] > 0)
                        <button type="button" wire:click="showMore('{{ $status }}')" class="w-full rounded border border-dashed border-kraft/30 py-2 text-[12px] text-kraft/70 transition-colors hover:border-kraft/60 hover:text-labwhite">
                            {{ __('Tampilkan :n lagi', ['n' => $col['hidden']]) }}
                        </button>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    @php $selInfo = $this->selection; @endphp
    @if ($selInfo['count'] > 0)
        <div class="sticky bottom-3 z-30 mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 rounded border border-bluelab/60 bg-surface px-4 py-3 shadow-lg" role="region" aria-label="{{ __('Aksi untuk pesanan terpilih') }}">
            <p class="text-[13px] font-semibold">{{ __(':n dipilih', ['n' => $selInfo['count']]) }}</p>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                @if ($selInfo['advance'] > 0)
                    <button type="button" wire:click="advanceSelected" wire:loading.attr="disabled" wire:confirm="{{ __('Lanjutkan :n pesanan ke tahap berikutnya?', ['n' => $selInfo['advance']]) }}"
                            class="rounded bg-bluelab px-4 py-2 text-[12.5px] font-semibold transition-colors hover:bg-[#234E94] disabled:opacity-60">{{ __('Lanjutkan :n ke tahap berikutnya', ['n' => $selInfo['advance']]) }}</button>
                @endif
                @if ($selInfo['print'] > 0)
                    <a href="{{ route('roastery.labels') }}?ids={{ implode(',', $selInfo['ids']) }}" target="_blank" rel="noopener"
                       class="rounded border border-bluelab/60 px-4 py-2 text-[12.5px] font-semibold transition-colors hover:bg-bluelab">{{ __('Cetak label :n', ['n' => $selInfo['print']]) }}</a>
                @endif
                <button type="button" wire:click="clearSelection" class="px-2 py-2 text-[12.5px] text-kraft/70 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Batal pilih') }}</button>
            </div>
        </div>
    @endif

    @php $sum = $this->stockSummary; $res = $this->stockRows; @endphp
    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        <header class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-kraft/15 px-4 py-3">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <h2 class="text-[12.5px] font-semibold">{{ __('Stok biji mentah') }}</h2>
                <p class="krm-readout text-[11.5px] text-kraft/55">{{ $sum['total'] }} {{ __('biji') }} · {{ number_format($sum['kg'], 1, ',', '.') }} kg</p>
            </div>
            <div class="flex flex-wrap gap-1.5 text-[11.5px]">
                @if ($sum['soldout'])<span class="rounded-[6px] border border-[#C0392B]/50 px-2 py-0.5 text-[#C0392B]">{{ $sum['soldout'] }} {{ __('habis') }}</span>@endif
                @if ($sum['low'])<span class="rounded-[6px] border border-[#E8782A]/60 px-2 py-0.5 text-[#E8782A]">{{ $sum['low'] }} {{ __('menipis') }}</span>@endif
                @if (! $sum['soldout'] && ! $sum['low'])<span class="rounded-[6px] border border-kraft/25 px-2 py-0.5 text-kraft/60">{{ __('Semua aman') }}</span>@endif
            </div>
        </header>

        <div class="flex flex-wrap items-center gap-2 px-4 pt-3">
            <label class="sr-only" for="stock-q">{{ __('Cari biji') }}</label>
            <input id="stock-q" type="search" wire:model.live.debounce.300ms="stockSearch" placeholder="{{ __('Contoh: Gayo, Natural, atau Flores') }}"
                   class="min-w-0 flex-1 rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-1 focus:ring-bluelab/50 sm:max-w-[320px]">
            <label class="flex cursor-pointer items-center gap-2 rounded border px-3 py-2 text-[12.5px] transition-colors {{ $stockLowOnly ? 'border-bluelab bg-bluelab/10' : 'border-kraft/25 hover:border-kraft/50' }}">
                <input type="checkbox" wire:model.live="stockLowOnly" class="h-3.5 w-3.5 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                {{ __('Hanya yang menipis') }}
            </label>
        </div>

        <ul class="mt-3 divide-y divide-kraft/10 px-4 {{ $stockAll || $res['filtered'] ? 'max-h-[26rem] overflow-y-auto' : '' }}">
            @forelse ($res['rows'] as $c)
                @php
                    $st = $c->stockState();
                    $pct = min(100, round($c->stock_green_bean_gram / $sum['max'] * 100));
                    $bar = $st === 'soldout' ? 'bg-[#C0392B]' : ($st === 'low' ? 'bg-[#E8782A]' : 'bg-bluelab');
                @endphp
                <li wire:key="st-{{ $c->id }}" class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 py-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,220px)_112px]">
                    <div class="min-w-0">
                        <p class="truncate text-[12.5px]">{{ $c->name }}</p>
                        <p class="truncate text-[11px] text-kraft/45">{{ $c->traceability?->process }}</p>
                    </div>
                    <div class="order-3 col-span-2 h-[3px] rounded-full bg-labwhite/10 sm:order-none sm:col-span-1"><div class="h-[3px] rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div></div>
                    <p class="krm-readout shrink-0 text-right text-[12px] {{ $st === 'ok' ? 'text-kraft/65' : ($st === 'low' ? 'text-[#E8782A]' : 'text-[#C0392B]') }}">
                        {{ number_format($c->stock_green_bean_gram) }} g
                        @if ($st !== 'ok')<span class="ml-1 text-[10.5px]">{{ $st === 'soldout' ? __('Habis') : __('Menipis') }}</span>@endif
                    </p>
                </li>
            @empty
                <li class="py-6 text-center text-[12.5px] text-kraft/45">{{ __('Tidak ada biji yang cocok') }}</li>
            @endforelse
        </ul>

        <div class="flex items-center justify-between gap-3 border-t border-kraft/15 px-4 py-2.5 text-[12px]">
            <p class="krm-readout text-kraft/50">{{ __('Menampilkan :a dari :b', ['a' => count($res['rows']), 'b' => $res['matched']]) }}</p>
            @if (! $res['filtered'] && $sum['total'] > \App\Livewire\Roastery::STOCK_PREVIEW)
                <button type="button" wire:click="$toggle('stockAll')" class="text-kraft/75 underline-offset-4 hover:text-labwhite hover:underline">
                    {{ $stockAll ? __('Ringkas') : __('Tampilkan semua :n biji', ['n' => $sum['total']]) }}
                </button>
            @endif
        </div>
    </section>
</div>
