<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'KROMATIK — Specialty Coffee Lab' }}</title>
    <meta name="description" content="{{ __('Kopi specialty yang disangrai setelah pesanan masuk. Pilih bijinya, level sangrainya, dan ukuran gilingannya.') }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">


    @include('layouts.partials.theme-boot')
    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="krm-grid min-h-screen bg-charcoal text-labwhite">

    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-6 focus:top-4 focus:z-50 focus:bg-bluelab focus:px-4 focus:py-2">
        {{ __('Lewati ke isi') }}
    </a>

    <header data-site-header x-data="{ open: false, top: true, hero: {{ request()->routeIs('landing', 'builder') ? 'true' : 'false' }}, land: {{ request()->routeIs('landing') ? 'true' : 'false' }}, build: {{ request()->routeIs('builder') ? 'true' : 'false' }} }"
            x-init="top = window.scrollY < 24"
            @scroll.window.passive="top = window.scrollY < 24"
            :class="top && !open && hero ? 'krm-dark border-transparent bg-gradient-to-b from-charcoal/90 to-transparent' : 'border-kraft/12 bg-charcoal/90 backdrop-blur-md'"
            class="sticky top-0 z-40 border-b transition-colors duration-300 [background-origin:border-box]">
        <div class="mx-auto flex max-w-[1180px] items-center justify-between gap-6 px-6 py-4">

            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                {{-- Tanda ringkas (K + kromatogram). Stempel penuhnya terlalu
                     rapat untuk dibaca di ukuran header; dipakai di footer. --}}
                <img src="{{ asset('images/mark-kromatik-white.png') }}"
                     alt="" width="40" height="40" class="krm-mark h-10 w-10 shrink-0">
                <span class="font-display text-[1.05rem] font-bold tracking-[0.2em]">KROMATIK</span>
                <span class="sr-only">Specialty Coffee Lab</span>
            </a>

            <nav class="hidden items-center gap-8 text-[14px] md:flex">
                <a href="{{ route('landing') }}#biji" class="text-kraft/80 transition-colors hover:text-labwhite">{{ __('Biji') }}</a>
                <a href="{{ route('builder') }}" class="text-kraft/80 transition-colors hover:text-labwhite">Roast builder</a>
            </nav>

            <div class="hidden items-center gap-3 md:flex"><x-lang-switch class="flex" /><x-theme-toggle /></div>

            <div class="hidden items-center gap-3 sm:flex">
                <livewire:cart-badge />
                @auth
                    <livewire:notifications />
                    <div class="relative" x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape.window="menu = false">
                        <button type="button" @click="menu = !menu" :aria-expanded="menu ? 'true' : 'false'" aria-haspopup="true"
                                class="flex items-center gap-2.5 rounded border border-kraft/30 py-1.5 pl-1.5 pr-3 transition-colors hover:border-kraft/60">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-bluelab font-display text-[13px] font-semibold">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="max-w-[8rem] truncate text-[13.5px] text-kraft">{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</span>
                            <svg viewBox="0 0 12 12" class="h-3 w-3 text-kraft/60 transition-transform" :class="menu && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M2.5 4.5L6 8l3.5-3.5"/></svg>
                        </button>
                        <div x-show="menu" x-cloak x-transition.opacity.duration.150ms
                             class="absolute right-0 top-full z-50 mt-2 w-48 overflow-hidden rounded border border-kraft/25 bg-surface py-1.5 shadow-[0_18px_40px_-12px_rgb(var(--shadow)/.8)]">
                            <a href="{{ route('dashboard') }}" class="block px-4 py-2.5 text-[14px] text-kraft transition-colors hover:bg-labwhite/[.05] hover:text-labwhite">{{ auth()->user()->isAdmin() ? 'Dashboard' : (auth()->user()->isRoaster() ? __('Antrian') : __('Pesanan')) }}</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('roastery') }}" class="block px-4 py-2.5 text-[14px] text-kraft transition-colors hover:bg-labwhite/[.05] hover:text-labwhite">{{ __('Antrian') }}</a>
                                <a href="{{ route('admin.coffees') }}" class="block px-4 py-2.5 text-[14px] text-kraft transition-colors hover:bg-labwhite/[.05] hover:text-labwhite">{{ __('Kelola kopi') }}</a>
                            @endif
                            <a href="{{ route('profile') }}" class="block px-4 py-2.5 text-[14px] text-kraft transition-colors hover:bg-labwhite/[.05] hover:text-labwhite">{{ __('Profil') }}</a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-kraft/15 pt-1">@csrf
                                <button class="block w-full px-4 py-2.5 text-left text-[14px] text-kraft/70 transition-colors hover:bg-labwhite/[.05] hover:text-labwhite">{{ __('Keluar') }}</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}"
                       class="px-2 py-2.5 font-display text-[14px] font-semibold text-kraft transition-colors hover:text-labwhite">
                        {{ __('Masuk') }}
                    </a>
                @endauth
                {{-- Satu aksi utama per layar: di beranda tombol ini baru muncul setelah hero lewat, di builder tidak perlu. --}}
                <a href="{{ route('builder') }}" x-show="land ? !top : !build" x-cloak x-transition.opacity.duration.200ms
                   class="rounded bg-bluelab px-5 py-2.5 font-display text-[14px] font-semibold text-paper transition-colors hover:bg-[#234E94]">
                    {{ __('Racik kopimu') }}
                </a>
            </div>

            <div class="flex items-center gap-2 md:hidden">
                <x-theme-toggle />
                <livewire:cart-badge />
                @auth<div class="sm:hidden"><livewire:notifications /></div>@endauth
            {{-- Tombol menu, hanya di layar sempit --}}
            <button type="button"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-controls="menu-utama"
                    class="-mr-2 flex h-10 w-10 items-center justify-center">
                <span class="sr-only">{{ __('Buka menu') }}</span>
                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path x-show="!open" d="M2 5h16M2 10h16M2 15h16" stroke-linecap="round"/>
                    <path x-show="open" x-cloak d="M4 4l12 12M16 4L4 16" stroke-linecap="round"/>
                </svg>
            </button>
            </div>
        </div>

        <nav id="menu-utama" x-show="open" x-cloak @click="open = false"
             class="border-t border-kraft/12 px-6 py-3 md:hidden">
            <a href="{{ route('landing') }}#biji" class="block py-2.5 text-[15px] text-kraft/85">{{ __('Biji') }}</a>
            <a href="{{ route('builder') }}" class="block py-2.5 text-[15px] text-kraft/85">Roast builder</a>
            @auth
                <a href="{{ route('dashboard') }}" class="block py-2.5 text-[15px] text-kraft/85">{{ auth()->user()->isAdmin() ? 'Dashboard' : (auth()->user()->isRoaster() ? __('Antrian') : __('Pesanan')) }}</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('roastery') }}" class="block py-2.5 text-[15px] text-kraft/85">{{ __('Antrian') }}</a>
                    <a href="{{ route('admin.coffees') }}" class="block py-2.5 text-[15px] text-kraft/85">{{ __('Kelola kopi') }}</a>
                @endif
                <a href="{{ route('profile') }}" class="block py-2.5 text-[15px] text-kraft/85">{{ __('Profil') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="block w-full py-2.5 text-left text-[15px] text-kraft/60">{{ __('Keluar') }}</button></form>
            @else
                <a href="{{ route('login') }}" class="block py-2.5 text-[15px] text-kraft/85">{{ __('Masuk') }}</a>
            @endauth
            <a href="{{ route('builder') }}" class="mt-2 block rounded bg-bluelab py-3 text-center font-display text-[15px] font-semibold">{{ __('Racik kopimu') }}</a>
            <x-lang-switch class="mt-4 flex pb-1" />
        </nav>
    </header>

    <main id="isi">
        {{ $slot }}
    </main>

    <footer class="krm-dark krm-footer relative isolate overflow-hidden border-t border-labwhite/10 bg-[#0a0a0a] text-kraft/80">
        <div class="mx-auto max-w-[1180px] px-6 pb-8 pt-14 md:pt-16">
            <div class="grid gap-10 md:grid-cols-3 md:items-start md:gap-8">

                <div>
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-3 text-labwhite">
                        <img src="{{ asset('images/mark-kromatik-white.png') }}" alt="" width="36" height="36" class="krm-mark h-9 w-9">
                        <span class="font-display text-[17px] font-semibold tracking-[0.22em]">KROMATIK</span>
                    </a>
                    <address class="mt-5 text-[14px] not-italic leading-relaxed text-kraft/75">
                        {{ \App\Models\Setting::get('store_address') }}
                    </address>
                    <p class="mt-3 text-[13px] text-kraft/55">{{ __('Disangrai setelah pesanan masuk') }}</p>
                </div>

                <nav aria-label="{{ __('Jelajahi') }}" class="md:justify-self-center">
                    <ul class="space-y-2.5 text-[14px]">
                        <li><a href="{{ route('landing') }}#biji" class="transition-colors hover:text-labwhite">{{ __('Biji') }}</a></li>
                        <li><a href="{{ route('builder') }}" class="transition-colors hover:text-labwhite">Roast builder</a></li>
                        <li><a href="{{ route('track') }}" class="transition-colors hover:text-labwhite">{{ __('Lacak pesanan') }}</a></li>
                        <li><a href="{{ route('landing') }}#tahapan" class="transition-colors hover:text-labwhite">FAQ</a></li>
                    </ul>
                </nav>

                <div class="flex gap-3 text-kraft/80 md:justify-self-end" aria-label="{{ __('Kontak') }}">
                    <a href="https://instagram.com/{{ \App\Models\Setting::get('instagram') }}" target="_blank" rel="noopener" aria-label="Instagram"
                       class="grid h-10 w-10 place-items-center rounded border border-labwhite/15 transition-colors hover:border-labwhite/40 hover:text-labwhite">
                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".6" fill="currentColor"/></svg>
                    </a>
                    <a href="{{ \App\Models\Setting::whatsappUrl() }}" target="_blank" rel="noopener" aria-label="WhatsApp"
                       class="grid h-10 w-10 place-items-center rounded border border-labwhite/15 transition-colors hover:border-labwhite/40 hover:text-labwhite">
                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 20.5l1.3-4.4A8.5 8.5 0 1 1 8 19.3l-4.5 1.2z"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.6-2-1-.9.8c-1-.4-1.9-1.3-2.3-2.300l.8-.9-1-2L9 8.5z" stroke-width="1"/></svg>
                    </a>
                    <a href="mailto:{{ \App\Models\Setting::get('contact_email') }}" aria-label="Email"
                       class="grid h-10 w-10 place-items-center rounded border border-labwhite/15 transition-colors hover:border-labwhite/40 hover:text-labwhite">
                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3.5 7l8.5 6.500L20.5 7"/></svg>
                    </a>
                </div>
            </div>

            <div class="mt-12 flex flex-wrap items-center justify-between gap-x-6 gap-y-3 border-t border-labwhite/10 pt-6 text-[12.5px] text-kraft/50">
                <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('store_name') }} &bull; {{ \App\Models\Setting::get('store_tagline') }} &bull; {{ \App\Models\Setting::get('store_city') }}</p>
                <x-lang-switch class="flex" />
            </div>
        </div>
    </footer>
</body>
</html>
