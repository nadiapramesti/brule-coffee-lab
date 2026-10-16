<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'KROMATIK — Specialty Coffee Lab' }}</title>
    <meta name="description" content="Kopi specialty yang disangrai setelah pesanan masuk. Pilih bijinya, level sangrainya, dan ukuran gilingannya.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="krm-grid min-h-screen bg-charcoal text-labwhite">

    <a href="#isi" class="sr-only focus:not-sr-only focus:absolute focus:left-6 focus:top-4 focus:z-50 focus:bg-bluelab focus:px-4 focus:py-2">
        Lewati ke isi
    </a>

    <header class="sticky top-0 z-40 border-b border-kraft/12 bg-charcoal/90 backdrop-blur">
        <div class="mx-auto flex max-w-[1180px] items-center justify-between gap-6 px-6 py-4">

            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                @if (file_exists(public_path('images/logo-kromatik-white.png')))
                    <img src="{{ asset('images/logo-kromatik-white.png') }}"
                         alt="" class="h-10 w-10 object-contain">
                @else
                    {{-- Tanda sementara. Taruh stempel aslinya di
                         public/images/logo-kromatik-white.png untuk menggantinya. --}}
                    <svg viewBox="0 0 40 20" class="h-5 w-10 shrink-0" aria-hidden="true">
                        <path d="M0 18 L6 18 L8 10 L10 18 L13 18 L15 4 L17.5 18 L20 18 L22 12 L24 18 L27 18 L29 7 L31.5 18 L40 18"
                              fill="none" stroke="#2A5DB0" stroke-width="1.6"
                              stroke-linejoin="round" stroke-linecap="round"/>
                    </svg>
                @endif
                <span class="font-display text-[1.05rem] font-bold tracking-[0.2em]">KROMATIK</span>
                <span class="sr-only">Specialty Coffee Lab</span>
            </a>

            <nav class="hidden items-center gap-8 text-[14px] md:flex">
                <a href="{{ route('landing') }}#biji" class="text-kraft/80 transition-colors hover:text-labwhite">Biji</a>
                <a href="{{ route('builder') }}" class="text-kraft/80 transition-colors hover:text-labwhite">Roast builder</a>
            </nav>

            <a href="{{ route('builder') }}"
               class="bg-bluelab px-5 py-2.5 font-display text-[14px] font-semibold text-labwhite transition-colors hover:bg-[#234E94]">
                Racik kopimu
            </a>
        </div>
    </header>

    <main id="isi">
        {{ $slot }}
    </main>

    <footer class="border-t border-kraft/12">
        <div class="mx-auto flex max-w-[1180px] flex-col gap-4 px-6 py-10 sm:flex-row sm:items-end sm:justify-between">
            <p class="font-display text-[1.1rem] font-semibold text-kraft">
                Roasted after you order, not before.
            </p>
            <p class="text-[13px] text-kraft/50">
                &copy; {{ date('Y') }} Kromatik Specialty Coffee Lab
            </p>
        </div>
    </footer>
</body>
</html>
