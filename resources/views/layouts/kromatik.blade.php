<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'KROMATIK - Specialty Coffee Lab' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --krm-charcoal: #121212;
            --krm-labwhite: #FFFBF2;
            --krm-kraft:    #D8C5A6;
            --krm-blue:     #2A5DB0;
        }
        body { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .krm-display { font-family: 'Oswald', sans-serif; letter-spacing: .02em; }

        /* kertas grafik laboratorium */
        .krm-grid {
            background-color: var(--krm-charcoal);
            background-image:
                linear-gradient(rgba(216,197,166,.055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(216,197,166,.055) 1px, transparent 1px),
                linear-gradient(rgba(216,197,166,.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(216,197,166,.1) 1px, transparent 1px);
            background-size: 20px 20px, 20px 20px, 100px 100px, 100px 100px;
        }
    </style>
</head>
<body class="krm-grid min-h-screen text-[#FFFBF2] antialiased">

    <header class="sticky top-0 z-40 border-b border-[#D8C5A6]/15 bg-[#121212]/85 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3">

            <a href="{{ url('/') }}" class="flex items-center gap-3">
                {{-- Simpan logo lu di: public/images/logo-kromatik-white.png --}}
                <img src="{{ asset('images/logo-kromatik-white.png') }}"
                     alt="KROMATIK"
                     class="h-11 w-11 object-contain"
                     onerror="this.style.display='none'">
                <span class="krm-display text-lg font-semibold tracking-[0.28em]">
                    KROMATIK
                </span>
            </a>

            <nav class="hidden items-center gap-7 text-[11px] tracking-[0.2em] md:flex">
                <a href="{{ url('/') }}" class="text-[#D8C5A6] transition hover:text-[#FFFBF2]">BERANDA</a>
                <a href="{{ url('/builder') }}" class="text-[#D8C5A6] transition hover:text-[#FFFBF2]">ROAST BUILDER</a>
                <a href="#traceability" class="text-[#D8C5A6] transition hover:text-[#FFFBF2]">TRACEABILITY</a>
            </nav>

            <a href="{{ url('/builder') }}"
               class="krm-display rounded-sm bg-[#2A5DB0] px-5 py-2 text-xs font-semibold tracking-[0.18em] text-[#FFFBF2] transition hover:bg-[#2A5DB0]/85">
                MULAI RACIK
            </a>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-[#D8C5A6]/15 py-10">
        <div class="mx-auto max-w-7xl px-5 text-center">
            <p class="krm-display text-sm tracking-[0.3em] text-[#D8C5A6]">
                ROASTED AFTER YOU ORDER, NOT BEFORE
            </p>
            <p class="mt-3 text-[11px] tracking-[0.15em] text-[#D8C5A6]/50">
                &copy; {{ date('Y') }} KROMATIK SPECIALTY COFFEE LAB
            </p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
