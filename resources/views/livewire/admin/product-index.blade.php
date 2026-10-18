@php
    $rp = fn ($n) => number_format($n, 0, ',', '.');
    $cols = 'grid-cols-[minmax(0,1fr)_auto] md:grid-cols-[minmax(0,2.4fr)_minmax(0,1.2fr)_minmax(0,1.4fr)_minmax(0,1.2fr)_64px_92px]';
@endphp
<div>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Kelola perlengkapan') }}</h1>
            <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Tambah, ubah, atur stok, dan tampilkan/sembunyikan alat seduh dan merchandise di toko.') }}</p>
        </div>
        <button type="button" wire:click="openForm" class="rounded bg-bluelab px-4 py-2.5 font-display text-[13.5px] font-semibold transition-colors hover:bg-[#234E94]">+ {{ __('Produk baru') }}</button>
    </div>

    @php $flash = $notice; @endphp
    @if ($flash)
        <p role="status" x-data x-init="setTimeout(() => $el.remove(), 4500)"
           class="mt-4 rounded border border-bluelab/50 bg-bluelab/10 px-4 py-2.5 text-[13px]">{{ $flash }}</p>
    @endif

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        <div class="flex flex-wrap items-center justify-between gap-3 px-3 py-2.5">
            <div class="flex gap-1" role="tablist">
                @foreach (['' => __('Semua'), 'active' => __('Tampil'), 'hidden' => __('Tersembunyi'), 'low' => __('Stok tipis')] as $k => $label)
                    <button type="button" role="tab" aria-selected="{{ $show === $k ? 'true' : 'false' }}" wire:click="$set('show', '{{ $k }}')"
                            class="flex items-center gap-1.5 rounded px-3 py-1.5 text-[12.5px] transition-colors {{ $show === $k ? 'bg-bluelab/20 text-labwhite' : 'text-kraft/60 hover:text-labwhite' }}">
                        {{ $label }}<span class="krm-readout text-[11px] text-kraft/50">{{ $this->counts[$k] }}</span>
                    </button>
                @endforeach
            </div>
            <div class="relative w-full sm:w-72">
                <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-kraft/45" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4-4"/></svg>
                <label class="sr-only" for="q">{{ __('Cari produk') }}</label>
                <input id="q" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Cari nama, cth: V60') }}"
                       class="w-full rounded border border-kraft/25 bg-transparent py-2 pl-9 pr-3 text-[13px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
            </div>
        </div>

        <div class="hidden gap-3 border-y border-kraft/10 px-4 py-2 text-[10.5px] uppercase tracking-[0.12em] text-kraft/45 md:grid {{ $cols }} krm-readout">
            <span>{{ __('Produk') }}</span><span>{{ __('Kategori') }}</span><span>{{ __('Stok (unit)') }}</span><span>{{ __('Harga') }}</span><span class="text-center">{{ __('Tampil') }}</span><span class="text-right">{{ __('Aksi') }}</span>
        </div>

        <ul class="divide-y divide-kraft/10 border-t border-kraft/10 md:border-t-0" wire:loading.class="opacity-60">
            @forelse ($this->products as $c)
                @php $low = $c->stock <= \App\Models\Product::LOW_STOCK; $pct = min(100, $c->stock * 4); @endphp
                <li wire:key="p-{{ $c->id }}" class="grid items-center gap-x-3 gap-y-2 px-4 py-3 transition-colors hover:bg-labwhite/[.02] {{ $cols }} {{ $c->is_active ? '' : 'opacity-60' }}">
                    <div class="flex min-w-0 items-start justify-between gap-3 max-md:col-span-2 md:block">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-product-thumb :product="$c" class="h-[42px] w-[42px]" />
                            <div class="min-w-0">
                                <p class="truncate text-[13.5px] font-semibold">{{ $c->name }}</p>
                                <p class="krm-readout truncate text-[10.5px] text-kraft/45">{{ $c->slug }}@if ($c->hasOptions()) · {{ count($c->optionList()) }} {{ __('pilihan') }}@endif</p>
                            </div>
                        </div>
                        <button type="button" role="switch" aria-checked="{{ $c->is_active ? 'true' : 'false' }}" aria-label="{{ __('Tampilkan di toko') }}" wire:click="toggle({{ $c->id }})"
                                class="relative h-[22px] w-[38px] shrink-0 rounded-full transition-colors md:hidden {{ $c->is_active ? 'bg-bluelab' : 'bg-labwhite/25' }}">
                            <span class="absolute top-[3px] h-4 w-4 rounded-full bg-paper shadow transition-all {{ $c->is_active ? 'left-[19px]' : 'left-[3px]' }}"></span>
                        </button>
                    </div>

                    <p class="truncate text-[12.5px] text-kraft/70 max-md:col-span-2">{{ $c->categoryLabel() }}</p>

                    <div class="relative max-md:col-span-2" x-data="{ add: false }" @click.outside="add = false" @keydown.escape="add = false">
                        <div class="flex items-center gap-2">
                            <span class="krm-readout w-[2.6rem] shrink-0 text-[12.5px] {{ $low ? 'text-ambertext' : '' }}">{{ $c->stock }}</span>
                            <div class="h-[3px] flex-1 rounded-full bg-labwhite/10"><div class="h-[3px] rounded-full {{ $low ? 'bg-[#E8782A]' : 'bg-bluelab' }}" style="width: {{ $pct }}%"></div></div>
                            <button type="button" @click="add = !add; $nextTick(() => add && $refs.g.focus())" class="grid h-6 w-6 shrink-0 place-items-center rounded border border-kraft/25 text-[14px] leading-none transition-colors hover:border-bluelab hover:bg-bluelab" title="{{ __('Tambah stok') }}" aria-label="{{ __('Tambah stok') }}" :aria-expanded="add ? 'true' : 'false'">+</button>
                        </div>
                        <form x-show="add" x-cloak x-transition.opacity.duration.120ms wire:submit="addStock({{ $c->id }})"
                              class="absolute right-0 top-full z-20 mt-1.5 w-56 rounded border border-kraft/30 bg-surface p-2.5 shadow-[0_14px_30px_-10px_rgb(var(--shadow)/.9)]">
                            <label for="rs-{{ $c->id }}" class="text-[11.5px] text-kraft/60">{{ __('Tambah stok (unit)') }}</label>
                            <div class="mt-1.5 flex gap-1.5">
                                <input x-ref="g" id="rs-{{ $c->id }}" type="number" inputmode="numeric" min="1" wire:model="restock.{{ $c->id }}" placeholder="{{ __('Contoh: 10') }}"
                                       class="krm-readout min-w-0 flex-1 rounded border border-kraft/25 bg-transparent px-2.5 py-1.5 text-[12.5px] placeholder:text-kraft/30 focus:border-bluelab focus:outline-none focus:ring-1 focus:ring-bluelab/50">
                                <button class="rounded bg-bluelab px-3 text-[12.5px] font-semibold hover:bg-[#234E94]">{{ __('Tambah') }}</button>
                            </div>
                            @error("restock.$c->id")<p class="mt-1 text-[11.5px] text-redtext">{{ $message }}</p>@enderror
                        </form>
                    </div>

                    <p class="krm-readout text-[12.5px] text-kraft/70">Rp{{ $rp($c->price) }}</p>

                    <div class="hidden justify-center md:flex">
                        <button type="button" role="switch" aria-checked="{{ $c->is_active ? 'true' : 'false' }}" aria-label="{{ __('Tampilkan di toko') }}" wire:click="toggle({{ $c->id }})"
                                class="relative h-[22px] w-[38px] rounded-full transition-colors {{ $c->is_active ? 'bg-bluelab' : 'bg-labwhite/25' }}">
                            <span class="absolute top-[3px] h-4 w-4 rounded-full bg-paper shadow transition-all {{ $c->is_active ? 'left-[19px]' : 'left-[3px]' }}"></span>
                        </button>
                    </div>

                    <div class="flex justify-end gap-1.5">
                        <button type="button" wire:click="openForm('{{ $c->slug }}')" title="{{ __('Ubah') }}" aria-label="{{ __('Ubah') }} {{ $c->name }}"
                                class="flex h-8 w-8 items-center justify-center rounded border border-kraft/25 transition-colors hover:border-kraft/60">
                            <svg viewBox="0 0 24 24" class="h-[15px] w-[15px]" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4zM13.5 6.5l4 4"/></svg>
                        </button>
                        <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="{{ __('Hapus produk ini?') }}" title="{{ __('Hapus') }}" aria-label="{{ __('Hapus') }} {{ $c->name }}"
                                class="flex h-8 w-8 items-center justify-center rounded border border-redtext/30 text-redtext transition-colors hover:bg-redtext/10">
                            <svg viewBox="0 0 24 24" class="h-[15px] w-[15px]" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2M7 7l1 12h8l1-12M10 11v5M14 11v5"/></svg>
                        </button>
                    </div>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-[13px] text-kraft/45">{{ __('Tidak ada produk yang cocok.') }}</li>
            @endforelse
        </ul>
    </section>

    {{-- Form melayang (tambah / ubah) --}}
    @if ($this->formOpen)
        <div class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
             x-data="{ init() { document.body.style.overflow = 'hidden'; this.$nextTick(() => this.$el.querySelector('input:not([disabled])')?.focus()); }, destroy() { document.body.style.overflow = ''; } }"
             @keydown.escape.window="$wire.closeForm()">
            <div class="krm-fade absolute inset-0 bg-black/70 backdrop-blur-[2px]" wire:click="closeForm"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="form-title"
                 class="krm-pop relative flex max-h-[96dvh] w-full flex-col overflow-hidden rounded-t-[14px] border border-kraft/25 bg-surface shadow-[0_30px_80px_-20px_rgb(var(--shadow)/.95)] sm:max-h-[90vh] sm:max-w-[780px] sm:rounded-[14px]">
                <livewire:admin.product-form :product="$this->editing" :key="'form-' . ($form ?? '')" />
            </div>
        </div>
    @endif
</div>
