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


    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="krm-grid min-h-screen bg-charcoal text-labwhite">

    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-6 focus:top-4 focus:z-50 focus:bg-bluelab focus:px-4 focus:py-2">
        {{ __('Lewati ke isi') }}
    </a>

    <header data-site-header x-data="{ open: false, top: true }"
            x-init="top = window.scrollY < 24"
            @scroll.window.passive="top = window.scrollY < 24"
            :class="top && !open ? 'border-transparent bg-gradient-to-b from-charcoal/90 to-transparent' : 'border-kraft/12 bg-charcoal/90 backdrop-blur-md'"
            class="sticky top-0 z-40 border-b transition-colors duration-300 [background-origin:border-box]">
        <div class="mx-auto flex max-w-[1180px] items-center justify-between gap-6 px-6 py-4">

            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                {{-- Tanda ringkas (K + kromatogram). Stempel penuhnya terlalu
                     rapat untuk dibaca di ukuran header; dipakai di footer. --}}
                <img src="{{ asset('images/mark-kromatik-white.png') }}"
                     alt="" width="40" height="40" class="h-10 w-10 shrink-0">
                <span class="font-display text-[1.05rem] font-bold tracking-[0.2em]">KROMATIK</span>
                <span class="sr-only">Specialty Coffee Lab</span>
            </a>

            <nav class="hidden items-center gap-8 text-[14px] md:flex">
                <a href="{{ route('landing') }}#biji" class="text-kraft/80 transition-colors hover:text-labwhite">{{ __('Biji') }}</a>
                <a href="{{ route('builder') }}" class="text-kraft/80 transition-colors hover:text-labwhite">Roast builder</a>
            </nav>

            <x-lang-switch class="hidden md:flex" />

            <a href="{{ route('builder') }}"
               class="hidden rounded bg-bluelab px-5 py-2.5 font-display text-[14px] font-semibold text-labwhite transition-colors hover:bg-[#234E94] sm:inline-block">
                {{ __('Racik kopimu') }}
            </a>

            {{-- Tombol menu, hanya di layar sempit --}}
            <button type="button"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-controls="menu-utama"
                    class="-mr-2 flex h-10 w-10 items-center justify-center md:hidden">
                <span class="sr-only">{{ __('Buka menu') }}</span>
                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path x-show="!open" d="M2 5h16M2 10h16M2 15h16" stroke-linecap="round"/>
                    <path x-show="open" x-cloak d="M4 4l12 12M16 4L4 16" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav id="menu-utama" x-show="open" x-cloak @click="open = false"
             class="border-t border-kraft/12 px-6 py-3 md:hidden">
            <a href="{{ route('landing') }}#biji" class="block py-2.5 text-[15px] text-kraft/85">{{ __('Biji') }}</a>
            <a href="{{ route('builder') }}" class="block py-2.5 text-[15px] text-kraft/85">Roast builder</a>
            <a href="{{ route('builder') }}" class="mt-2 block rounded bg-bluelab py-3 text-center font-display text-[15px] font-semibold">{{ __('Racik kopimu') }}</a>
            <x-lang-switch class="mt-4 flex pb-1" />
        </nav>
    </header>

    <main id="isi">
        {{ $slot }}
    </main>

    <footer class="relative isolate overflow-hidden border-t border-kraft/12">
        <picture class="absolute inset-0 -z-20">
            <source media="(max-width: 767px)" srcset="{{ asset('images/footer-roastery-sm.webp') }}" type="image/webp">
            <img src="{{ asset('images/footer-roastery.webp') }}" alt="" width="1920" height="640"
                 loading="lazy" decoding="async" class="h-full w-full object-cover object-[50%_40%]">
        </picture>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,#121212_0%,rgba(18,18,18,.86)_35%,rgba(18,18,18,.93)_100%)]"></div>

        <div class="mx-auto max-w-[1180px] px-6 pb-8 pt-16 md:pt-20">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between md:gap-12">
                <div class="max-w-[34rem]">
                    <h2 class="krm-h2 font-bold">{{ __('Mulai dari rasa, bukan dari nama kopinya') }}</h2>
                    <p class="mt-3 text-[15px] leading-relaxed text-kraft/80">
                        {{ __('Geser level sangrainya, lihat profil rasanya bergerak, lalu pesan di situ juga.') }}
                    </p>
                </div>
                <a href="{{ route('builder') }}"
                   class="self-start rounded bg-bluelab px-7 py-3.5 font-display text-[15px] font-semibold text-labwhite transition-colors hover:bg-[#234E94] md:self-auto">
                    {{ __('Buka roast builder') }}
                </a>
            </div>

            <div class="mt-12 grid grid-cols-2 gap-x-6 gap-y-10 border-t border-kraft/20 pt-10 lg:grid-cols-[minmax(0,1.3fr)_1fr_1fr_1fr]">

                <div class="col-span-2 lg:col-span-1">
                    <img src="{{ asset('images/logo-kromatik-white.png') }}"
                         alt="Kromatik Specialty Coffee Lab"
                         width="64" height="64" class="w-16 opacity-90">
                    <p class="mt-4 font-display text-[15px] font-semibold text-labwhite">Roasted after you order, not before.</p>
                    <p class="mt-2 max-w-[32ch] text-[13px] leading-relaxed text-kraft/70">
                        {{ __('Roastery kecil yang menyangrai kopi satu pesanan sekali jalan, dengan asal-usul tiap biji tercatat.') }}
                    </p>
                </div>

                <nav aria-label="{{ __('Jelajahi') }}">
                    <h2 class="text-[12px] text-kraft/50">{{ __('Jelajahi') }}</h2>
                    <ul class="mt-4 space-y-2.5 text-[14px]">
                        <li><a href="{{ route('landing') }}#biji" class="text-kraft/85 transition-colors hover:text-labwhite">{{ __('Biji di gudang') }}</a></li>
                        <li><a href="{{ route('landing') }}#tahapan" class="text-kraft/85 transition-colors hover:text-labwhite">{{ __('Setelah kamu bayar') }}</a></li>
                        <li><a href="{{ route('builder') }}" class="text-kraft/85 transition-colors hover:text-labwhite">Roast builder</a></li>
                    </ul>
                </nav>

                <div>
                    <h2 class="text-[12px] text-kraft/50">{{ __('Roastery') }}</h2>
                    <ul class="mt-4 space-y-2.5 text-[14px] text-kraft/85">
                        <li>{{ __('Sangrai: Senin–Sabtu') }}</li>
                        <li class="krm-readout text-[13px]">08.00 – 16.00 WIB</li>
                        <li class="krm-readout text-[13px]">halo@kromatik.test</li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-[12px] text-kraft/50">{{ __('Bahasa') }}</h2>
                    <x-lang-switch class="mt-4 flex" />
                </div>
            </div>

            <p class="mt-12 flex flex-wrap justify-between gap-x-6 gap-y-1 border-t border-kraft/12 pt-6 text-[12.5px] text-kraft/50">
                <span>&copy; {{ date('Y') }} Kromatik Specialty Coffee Lab</span>
                <span>{{ __('Dibuat sebagai proyek kuliah Sistem Informasi.') }}</span>
            </p>
        </div>
    </footer>
</body>
</html>
