@php
    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $list = $this->customers;
    $c = $this->current;
    $src = fn ($u) => match ($u->provider) { 'google' => 'Google', 'facebook' => 'Facebook', default => 'Email' };
    $inp = 'rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $st = $this->stats;
@endphp
<div>
    <div>
        <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Pelanggan') }}</h1>
        <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Akun pelanggan, total belanja, dan riwayat pesanannya.') }}</p>
    </div>

    <dl class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            [__('Total pelanggan'), $st['total'], null],
            [__('Pernah memesan'), $st['ordered'], $st['conversion'] . '% ' . __('dari akun')],
            [__('Pelanggan langganan'), $st['repeat'], __('pesan 2× atau lebih')],
            [__('Baru 30 hari'), $st['new'], null],
        ] as [$l, $v, $sub])
            <div class="rounded border border-kraft/20 bg-charcoal px-4 py-3">
                <dt class="text-[12px] text-kraft/60">{{ $l }}</dt>
                <dd class="krm-readout mt-1 text-[1.4rem] leading-none">{{ $v }}</dd>
                <p class="mt-1.5 min-h-[1rem] text-[11.5px] text-kraft/45">{{ $sub }}</p>
            </div>
        @endforeach
    </dl>

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        <div class="flex flex-wrap items-center gap-3 px-4 pt-4">
            <div class="relative min-w-[220px] flex-1 sm:max-w-sm">
                <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-kraft/45" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4-4"/></svg>
                <label class="sr-only" for="q">{{ __('Cari pelanggan') }}</label>
                <input id="q" type="search" wire:model.live.debounce.350ms="search" placeholder="{{ __('Nama, email, atau telepon, cth: Sinta') }}" class="{{ $inp }} w-full pl-9">
            </div>
            <div class="ml-auto flex items-center gap-2 text-[12.5px] text-kraft/60">
                <label for="sort">{{ __('Urutkan') }}</label>
                <select id="sort" wire:model.live="sort" class="{{ $inp }} py-1.5">
                    @foreach (\App\Livewire\Admin\Customers::SORTS as $k => $l)<option class="bg-charcoal" value="{{ $k }}">{{ __($l) }}</option>@endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap gap-x-1 border-b border-kraft/15 px-3" role="tablist">
            @foreach (\App\Livewire\Admin\Customers::TABS as $key => $label)
                <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="setTab('{{ $key }}')"
                        class="-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-[13px] transition-colors {{ $tab === $key ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">
                    {{ __($label) }}<span class="krm-readout rounded-[6px] bg-labwhite/[.07] px-1.5 text-[11px]">{{ $this->tabCounts[$key] }}</span>
                </button>
            @endforeach
        </div>

        <div wire:loading.class="opacity-60" class="transition-opacity">
            <table class="hidden w-full text-left text-[13.5px] md:table">
                <thead class="krm-readout text-[11px] uppercase tracking-[0.12em] text-kraft/45">
                    <tr class="border-b border-kraft/10"><th class="px-4 py-2.5 font-normal">{{ __('Pelanggan') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Masuk via') }}</th><th class="px-3 py-2.5 text-right font-normal">{{ __('Pesanan') }}</th><th class="px-3 py-2.5 text-right font-normal">{{ __('Total belanja') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Terakhir memesan') }}</th><th class="px-4 py-2.5 font-normal">{{ __('Bergabung') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-kraft/10">
                    @foreach ($list as $u)
                        <tr wire:key="u-{{ $u->id }}" wire:click="show({{ $u->id }})" class="cursor-pointer transition-colors hover:bg-labwhite/[.03] {{ $open === $u->id ? 'bg-bluelab/10' : '' }}">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-labwhite/[.08] font-display text-[13px] font-semibold">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                                    <div class="min-w-0"><p class="truncate font-medium">{{ $u->name }}</p><p class="truncate text-[12px] text-kraft/55">{{ $u->email }}</p></div>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-[12.5px] text-kraft/70">{{ $src($u) }}</td>
                            <td class="krm-readout px-3 py-2.5 text-right">{{ $u->orders_count }}</td>
                            <td class="krm-readout whitespace-nowrap px-3 py-2.5 text-right">{{ $u->spent ? $rp($u->spent) : '—' }}</td>
                            <td class="krm-readout whitespace-nowrap px-3 py-2.5 text-[12px] text-kraft/60">{{ $u->last_order_at ? \Carbon\Carbon::parse($u->last_order_at)->translatedFormat('j M Y') : '—' }}</td>
                            <td class="krm-readout whitespace-nowrap px-4 py-2.5 text-[12px] text-kraft/60">{{ $u->created_at->translatedFormat('j M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="divide-y divide-kraft/10 md:hidden">
                @foreach ($list as $u)
                    <li wire:key="um-{{ $u->id }}">
                        <button type="button" wire:click="show({{ $u->id }})" class="flex w-full items-center gap-3 px-4 py-3 text-left">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-labwhite/[.08] font-display text-[14px] font-semibold">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                            <div class="min-w-0 flex-1"><p class="truncate text-[13.5px] font-medium">{{ $u->name }}</p><p class="truncate text-[12px] text-kraft/55">{{ $u->orders_count }} {{ __('pesanan') }} · {{ $src($u) }}</p></div>
                            <span class="krm-readout shrink-0 text-[12.5px]">{{ $u->spent ? $rp($u->spent) : '—' }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            @if ($list->isEmpty())
                <p class="px-5 py-12 text-center text-[13px] text-kraft/45">{{ __('Tidak ada pelanggan yang cocok.') }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-kraft/15 px-4 py-3 text-[12.5px] text-kraft/60">
            <span>{{ $list->total() ? __('Menampilkan :a–:b dari :t', ['a' => $list->firstItem(), 'b' => $list->lastItem(), 't' => $list->total()]) : '' }}</span>
            <div class="flex items-center gap-2">
                <button wire:click="previousPage" @disabled($list->onFirstPage()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Sebelumnya') }}">&larr;</button>
                <span class="krm-readout">{{ $list->currentPage() }}/{{ $list->lastPage() }}</span>
                <button wire:click="nextPage" @disabled(! $list->hasMorePages()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Berikutnya') }}">&rarr;</button>
            </div>
        </div>
    </section>

    @if ($c)
        <div class="fixed inset-0 z-[70] flex justify-end"
             x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             @keydown.escape.window="$wire.close()">
            <div class="krm-fade absolute inset-0 bg-black/60 backdrop-blur-[2px]" wire:click="close"></div>
            <aside role="dialog" aria-modal="true" aria-labelledby="cd-title" class="krm-slide relative flex h-full w-full max-w-[500px] flex-col border-l border-kraft/25 bg-surface shadow-[-30px_0_80px_-20px_rgb(var(--shadow)/.9)]">
                <header class="flex shrink-0 items-center justify-between gap-3 border-b border-kraft/15 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-bluelab font-display text-[1.1rem] font-semibold">{{ mb_strtoupper(mb_substr($c->name, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <h2 id="cd-title" class="truncate font-display text-[1.1rem] font-semibold leading-tight">{{ $c->name }}</h2>
                            <p class="truncate text-[12.5px] text-kraft/60">{{ $c->email }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="close" class="-mr-2 grid h-9 w-9 shrink-0 place-items-center rounded text-kraft/70 hover:bg-labwhite/[.06] hover:text-labwhite" aria-label="{{ __('Tutup') }}">
                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg>
                    </button>
                </header>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain px-5 py-5">
                    <dl class="grid grid-cols-3 gap-2">
                        @foreach ([
                            [__('Pesanan'), $c->orders_count],
                            [__('Total belanja'), $c->spent ? $rp($c->spent) : '—'],
                            [__('Rata-rata'), $c->spent && $c->orders_count ? $rp(round($c->spent / max(1, $c->orders_count))) : '—'],
                        ] as [$l, $v])
                            <div class="rounded border border-kraft/20 px-3 py-2.5"><dt class="text-[11px] text-kraft/55">{{ $l }}</dt><dd class="krm-readout mt-1 whitespace-nowrap text-[0.82rem] leading-tight min-[430px]:text-[0.95rem]">{{ $v }}</dd></div>
                        @endforeach
                    </dl>

                    <section class="rounded border border-kraft/20 p-4 text-[13.5px]">
                        <h3 class="text-[12.5px] font-semibold">{{ __('Profil') }}</h3>
                        <dl class="mt-3 space-y-2">
                            <div class="flex justify-between gap-4"><dt class="text-kraft/55">{{ __('Telepon') }}</dt><dd class="krm-readout text-[12.5px]">
                                @if ($c->phone){{ $c->phone }} <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $c->phone)) }}" target="_blank" rel="noopener" class="ml-1 font-sans text-[12px] text-bluetext underline-offset-4 hover:underline">WhatsApp ↗</a>@else <span class="text-kraft/40">—</span>@endif</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-kraft/55">{{ __('Masuk via') }}</dt><dd>{{ $src($c) }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-kraft/55">{{ __('Bergabung') }}</dt><dd>{{ $c->created_at->translatedFormat('j F Y') }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-kraft/55">{{ __('Terakhir memesan') }}</dt><dd>{{ $c->last_order_at ? \Carbon\Carbon::parse($c->last_order_at)->diffForHumans() : '—' }}</dd></div>
                        </dl>
                    </section>

                    @if ($this->favorites['coffee'])
                        <section class="rounded border border-kraft/20 p-4 text-[13.5px]">
                            <h3 class="text-[12.5px] font-semibold">{{ __('Selera') }}</h3>
                            <dl class="mt-3 space-y-2">
                                <div class="flex justify-between gap-4"><dt class="text-kraft/55">{{ __('Kopi favorit') }}</dt><dd class="text-right">{{ $this->favorites['coffee'] }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-kraft/55">{{ __('Level favorit') }}</dt><dd>{{ $this->favorites['level'] }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-kraft/55">{{ __('Total dibeli') }}</dt><dd class="krm-readout">{{ number_format($this->favorites['grams']) }} g</dd></div>
                            </dl>
                        </section>
                    @endif

                    @if ($this->addresses->isNotEmpty())
                        <section class="rounded border border-kraft/20 p-4">
                            <h3 class="text-[12.5px] font-semibold">{{ __('Alamat pengiriman') }}</h3>
                            <ul class="mt-2.5 space-y-2 text-[13px] text-kraft/80">@foreach ($this->addresses as $a)<li class="border-l-2 border-kraft/25 pl-3">{{ $a }}</li>@endforeach</ul>
                        </section>
                    @endif

                    <section class="rounded border border-kraft/20">
                        <h3 class="px-4 pt-4 text-[12.5px] font-semibold">{{ __('Riwayat pesanan') }}</h3>
                        <ul class="divide-y divide-kraft/10 px-4 pb-1">
                            @forelse ($c->orders as $o)
                                <li class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2.5">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.orders', ['order' => $o->order_code]) }}" wire:navigate class="krm-readout whitespace-nowrap text-[12px] underline-offset-4 hover:underline">{{ $o->order_code }}</a>
                                        <p class="text-[11.5px] text-kraft/50">{{ $o->created_at->translatedFormat('j M Y') }}</p>
                                    </div>
                                    <div class="flex items-center gap-2.5"><span class="krm-readout text-[12.5px]">{{ $rp($o->total) }}</span><span class="whitespace-nowrap rounded-[6px] border px-1.5 py-0.5 text-[11px] {{ $o->statusTone() }}">{{ $o->status_label }}</span></div>
                                </li>
                            @empty
                                <li class="py-4 text-[13px] text-kraft/45">{{ __('Belum pernah memesan.') }}</li>
                            @endforelse
                        </ul>
                    </section>
                </div>
            </aside>
        </div>
    @endif
</div>
