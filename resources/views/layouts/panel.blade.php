@php
    $u = auth()->user();
    $queueCount = \App\Models\Order::inProduction()->count();
    $lowStock = $u->isAdmin() ? \App\Models\Coffee::active()->where('stock_green_bean_gram', '<', \App\Livewire\Admin\Dashboard::LOW_STOCK_GRAM)->count() : 0;
    $unpaid = $u->isAdmin() ? \App\Models\Order::where('status', 'pending_payment')->count() : 0;
    $groups = [
        __('Utama') => [
            ['admin.dashboard', route('admin.dashboard'), 'Dashboard', 'M4 13h6V4H4v9zm0 7h6v-5H4v5zm10 0h6v-9h-6v9zm0-16v5h6V4h-6z', true, null],
        ],
        __('Penjualan') => [
            ['admin.orders', route('admin.orders'), __('Pesanan'), 'M6 6h15l-1.5 9h-12zM6 6L5 3H2M9 20a1 1 0 100-2 1 1 0 000 2zM18 20a1 1 0 100-2 1 1 0 000 2z', true, $unpaid ?: null],
            ['admin.customers', route('admin.customers'), __('Pelanggan'), 'M16 20v-1a4 4 0 00-4-4H8a4 4 0 00-4 4v1M10 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM20 20v-1a4 4 0 00-3-3.9M16 4.2a3.5 3.5 0 010 6.6', true, null],
        ],
        __('Katalog') => [
            ['admin.coffees*', route('admin.coffees'), __('Kelola kopi'), 'M5 8h11v6a5 5 0 01-5 5H10a5 5 0 01-5-5V8zM16 9h2a2 2 0 010 4h-2M8 3v2M12 3v2', true, null],
            ['admin.stock', route('admin.stock'), __('Stok'), 'M3 8l9-5 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8', true, $lowStock ?: null],
        ],
        __('Operasional') => [
            ['roastery', route('roastery'), __('Antrian roastery'), 'M4 6h16M4 12h16M4 18h10', false, $queueCount ?: null],
        ],
    ];
    $crumbs = match (true) {
        request()->routeIs('admin.dashboard') => ['Dashboard'],
        request()->routeIs('admin.coffees') => [__('Kelola kopi')],
        request()->routeIs('admin.orders') => [__('Pesanan')],
        request()->routeIs('admin.customers') => [__('Pelanggan')],
        request()->routeIs('admin.stock') => [__('Stok')],
        request()->routeIs('roastery') => [__('Antrian roastery')],
        default => [],
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'KROMATIK' }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <script>document.documentElement.classList.add('js');try{if(localStorage.getItem('krm-mini')==='1')document.documentElement.classList.add('mini')}catch(e){}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-charcoal text-labwhite" x-data="{ nav: false }" @keydown.escape.window="nav = false">

    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-[60] focus:bg-bluelab focus:px-4 focus:py-2">{{ __('Lewati ke isi') }}</a>

    {{-- Backdrop drawer (HP) --}}
    <div x-show="nav" x-cloak x-transition.opacity @click="nav = false" class="fixed inset-0 z-40 bg-black/60 lg:hidden"></div>

    <aside :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 flex w-[248px] lg:[.mini_&]:w-[72px] flex-col border-r border-kraft/15 bg-[#0d0d0d] transition-[transform,width] duration-300 ease-out">
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-kraft/15 px-5 lg:[.mini_&]:justify-center lg:[.mini_&]:px-0">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5" title="KROMATIK">
                <img src="{{ asset('images/mark-kromatik-white.png') }}" alt="" width="30" height="30" class="h-[30px] w-[30px]">
                <span class="font-display text-[0.95rem] font-bold tracking-[0.2em] lg:[.mini_&]:hidden">KROMATIK</span>
            </a>
            <button type="button" @click="nav = false" class="-mr-2 grid h-9 w-9 place-items-center text-kraft/70 lg:hidden" aria-label="{{ __('Tutup menu') }}">
                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 lg:[.mini_&]:px-2" aria-label="{{ __('Menu panel') }}">
            @foreach ($groups as $group => $items)
                @php $items = array_filter($items, fn ($i) => ! $i[4] || $u->isAdmin()); @endphp
                @continue(! count($items))
                <p class="krm-readout px-3 pb-1.5 pt-3 text-[10.5px] uppercase tracking-[0.18em] text-kraft/40 lg:[.mini_&]:hidden">{{ $group }}</p>
                <div class="mx-3 my-2 hidden border-t border-kraft/10 lg:[.mini_&]:block"></div>
                @foreach ($items as [$route, $url, $label, $icon, , $badge])
                    @php $on = request()->routeIs($route); @endphp
                    <a href="{{ $url }}" wire:navigate title="{{ $label }}" @if ($on) aria-current="page" @endif
                       class="relative mb-0.5 flex items-center gap-3 rounded px-3 py-2.5 lg:[.mini_&]:justify-center lg:[.mini_&]:px-0 text-[13.5px] transition-colors {{ $on ? 'bg-bluelab/15 text-labwhite' : 'text-kraft/70 hover:bg-white/[.04] hover:text-labwhite' }}">
                        @if ($on)<span class="absolute inset-y-1.5 left-0 w-[3px] rounded-full bg-bluelab"></span>@endif
                        <svg viewBox="0 0 24 24" class="h-[17px] w-[17px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                        <span class="flex-1 lg:[.mini_&]:hidden">{{ $label }}</span>
                        @if ($badge)<span class="krm-readout rounded-[6px] bg-white/[.09] px-1.5 py-0.5 text-[11px] lg:[.mini_&]:hidden">{{ $badge }}</span><span class="absolute right-2.5 top-2 hidden h-2 w-2 rounded-full bg-[#E8782A] lg:[.mini_&]:block"></span>@endif
                    </a>
                @endforeach
            @endforeach

            <p class="krm-readout px-3 pb-1.5 pt-6 text-[10.5px] uppercase tracking-[0.18em] text-kraft/40 lg:[.mini_&]:hidden">{{ __('Pintasan') }}</p>
            <div class="mx-3 my-2 hidden border-t border-kraft/10 lg:[.mini_&]:block"></div>
            <a href="{{ route('landing') }}" title="{{ __('Lihat toko') }}" class="flex items-center gap-3 rounded px-3 py-2.5 lg:[.mini_&]:justify-center lg:[.mini_&]:px-0 text-[13.5px] text-kraft/70 transition-colors hover:bg-white/[.04] hover:text-labwhite">
                <svg viewBox="0 0 24 24" class="h-[17px] w-[17px]" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8"/></svg><span class="lg:[.mini_&]:hidden">{{ __('Lihat toko') }}</span>
            </a>
        </nav>

        <div class="shrink-0 border-t border-kraft/15 p-3 lg:[.mini_&]:px-2">
            <div class="flex items-center gap-3 px-2 py-2 lg:[.mini_&]:justify-center lg:[.mini_&]:px-0" title="{{ $u->name }}">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-bluelab font-display text-[14px] font-semibold">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                <div class="min-w-0 flex-1 lg:[.mini_&]:hidden">
                    <p class="truncate text-[13.5px] font-medium">{{ $u->name }}</p>
                    <p class="krm-readout text-[10.5px] uppercase tracking-wider text-kraft/50">{{ $u->role }}</p>
                </div>
            </div>
            <div class="mt-1 flex items-center justify-between gap-2 px-2 lg:[.mini_&]:hidden">
                <a href="{{ route('profile') }}" class="text-[12.5px] text-kraft/65 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Profil') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="text-[12.5px] text-kraft/65 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Keluar') }}</button>
                </form>
            </div>
            <div class="mt-1 hidden flex-col items-center gap-1 lg:[.mini_&]:flex">
                <a href="{{ route('profile') }}" title="{{ __('Profil') }}" class="grid h-9 w-9 place-items-center rounded text-kraft/65 hover:bg-white/[.05] hover:text-labwhite"><svg viewBox="0 0 24 24" class="h-[17px] w-[17px]" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6"/></svg></a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button title="{{ __('Keluar') }}" aria-label="{{ __('Keluar') }}" class="grid h-9 w-9 place-items-center rounded text-kraft/65 hover:bg-white/[.05] hover:text-labwhite"><svg viewBox="0 0 24 24" class="h-[17px] w-[17px]" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/></svg></button></form>
            </div>
            <button type="button" x-data @click="const m = document.documentElement.classList.toggle('mini'); try { localStorage.setItem('krm-mini', m ? '1' : '0') } catch (e) {}"
                    class="mt-2 hidden h-9 w-full items-center justify-center gap-2 rounded border border-kraft/15 text-[12px] text-kraft/60 transition-colors hover:border-kraft/40 hover:text-labwhite lg:flex" aria-label="{{ __('Ciutkan menu') }}" title="{{ __('Ciutkan menu') }}">
                <svg viewBox="0 0 24 24" class="h-4 w-4 transition-transform duration-300 lg:[.mini_&]:rotate-180" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg><span class="lg:[.mini_&]:hidden">{{ __('Ciutkan') }}</span>
            </button>
        </div>
    </aside>

    <div class="transition-[padding] duration-300 ease-out lg:pl-[248px] lg:[.mini_&]:pl-[72px]">
        <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-kraft/15 bg-charcoal/90 px-4 backdrop-blur-md sm:px-6 lg:px-8">
            <button type="button" @click="nav = true" class="-ml-2 grid h-10 w-10 place-items-center lg:hidden" :aria-expanded="nav ? 'true' : 'false'" aria-label="{{ __('Buka menu') }}">
                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M2 5h16M2 10h16M2 15h16"/></svg>
            </button>

            <nav aria-label="breadcrumb" class="flex min-w-0 items-center gap-2 text-[13.5px]">
                <span class="hidden text-kraft/45 sm:inline">{{ $u->isAdmin() ? 'Admin' : 'Roastery' }}</span>
                @foreach ($crumbs as $i => $c)
                    <span class="hidden text-kraft/30 sm:inline" aria-hidden="true">/</span>
                    <span class="truncate {{ $loop->last ? 'font-medium text-labwhite' : 'hidden text-kraft/55 sm:inline' }}">{{ $c }}</span>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                <span class="krm-readout hidden text-[12px] text-kraft/50 md:block">{{ now()->translatedFormat('D, j M Y') }}</span>
                <x-lang-switch class="flex" />
                @if ($u->isAdmin())
                    <a href="{{ route('admin.orders', ['tab' => 'unpaid']) }}" wire:navigate class="relative grid h-9 w-9 place-items-center rounded border border-kraft/20 text-kraft/75 transition-colors hover:border-kraft/50 hover:text-labwhite" aria-label="{{ __('Menunggu bayar') }}: {{ $unpaid }}" title="{{ __('Menunggu bayar') }}">
                        <svg viewBox="0 0 24 24" class="h-[17px] w-[17px]" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9a6 6 0 1112 0c0 5 2 6 2 6H4s2-1 2-6zM10 19a2 2 0 004 0"/></svg>
                        @if ($unpaid)<span class="krm-readout absolute -right-1.5 -top-1.5 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-[#E8782A] px-1 text-[10.5px] font-semibold text-charcoal">{{ $unpaid }}</span>@endif
                    </a>
                @endif
            </div>
        </header>

        <main id="isi" class="mx-auto max-w-[1500px] px-4 pb-16 pt-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
