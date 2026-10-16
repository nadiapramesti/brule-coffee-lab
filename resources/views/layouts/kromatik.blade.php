<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'KROMATIK — Specialty Coffee Lab' }}</title>
    <meta name="description" content="Kopi specialty yang disangrai setelah pesanan masuk. Pilih bijinya, level sangrainya, dan ukuran gilingannya.">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">


    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="krm-grid min-h-screen bg-charcoal text-labwhite">

    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-6 focus:top-4 focus:z-50 focus:bg-bluelab focus:px-4 focus:py-2">
        Lewati ke isi
    </a>

    <header x-data="{ open: false }"
            class="sticky top-0 z-40 border-b border-kraft/12 bg-charcoal/85 backdrop-blur-md">
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
                <a href="{{ route('landing') }}#biji" class="text-kraft/80 transition-colors hover:text-labwhite">Biji</a>
                <a href="{{ route('builder') }}" class="text-kraft/80 transition-colors hover:text-labwhite">Roast builder</a>
            </nav>

            <a href="{{ route('builder') }}"
               class="hidden rounded bg-bluelab px-5 py-2.5 font-display text-[14px] font-semibold text-labwhite transition-colors hover:bg-[#234E94] sm:inline-block">
                Racik kopimu
            </a>

            {{-- Tombol menu, hanya di layar sempit --}}
            <button type="button"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-controls="menu-utama"
                    class="-mr-2 flex h-10 w-10 items-center justify-center md:hidden">
                <span class="sr-only">Buka menu</span>
                <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path x-show="!open" d="M2 5h16M2 10h16M2 15h16" stroke-linecap="round"/>
                    <path x-show="open" x-cloak d="M4 4l12 12M16 4L4 16" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav id="menu-utama" x-show="open" x-cloak @click="open = false"
             class="border-t border-kraft/12 px-6 py-3 md:hidden">
            <a href="{{ route('landing') }}#biji" class="block py-2.5 text-[15px] text-kraft/85">Biji</a>
            <a href="{{ route('builder') }}" class="block py-2.5 text-[15px] text-kraft/85">Roast builder</a>
            <a href="{{ route('builder') }}" class="mt-2 block rounded bg-bluelab py-3 text-center font-display text-[15px] font-semibold">Racik kopimu</a>
        </nav>
    </header>

    <main id="isi">
        {{ $slot }}
    </main>

    <footer class="border-t border-kraft/12">
        <div class="mx-auto flex max-w-[1180px] flex-col gap-6 px-6 py-12 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-5">
                <img src="{{ asset('images/logo-kromatik-white.png') }}"
                     alt="Kromatik Specialty Coffee Lab"
                     width="84" height="84" class="w-[84px] shrink-0 opacity-90">
                <p class="font-display text-[1.1rem] font-semibold leading-snug text-kraft">
                    Roasted after you order,<br>not before.
                </p>
            </div>
            <p class="text-[13px] text-kraft/50">
                &copy; {{ date('Y') }} Kromatik Specialty Coffee Lab
            </p>
        </div>
    </footer>
</body>
</html>
