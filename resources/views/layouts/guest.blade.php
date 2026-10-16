@php
    $route = request()->route()?->getName();
    $copy = [
        'login'            => [__('Masuk'),            __('Lanjutkan pesananmu dan pantau antrian sangrainya.')],
        'register'         => [__('Buat akun'),        __('Satu akun untuk memesan, membayar, dan melacak pesananmu.')],
        'password.request' => [__('Lupa password'),    __('Kami kirim tautan untuk membuat password baru.')],
        'password.reset'   => [__('Password baru'),    __('Pilih password yang belum pernah kamu pakai di tempat lain.')],
        'verification.notice' => [__('Verifikasi email'), __('Satu langkah lagi sebelum kamu bisa memesan.')],
        'password.confirm' => [__('Konfirmasi password'), __('Area ini butuh konfirmasi sebelum lanjut.')],
    ][$route] ?? [__('Akun'), ''];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $copy[0] }} — KROMATIK</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="krm-auth min-h-screen bg-charcoal text-labwhite antialiased">
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">

    {{-- Panel foto: hanya di layar lebar --}}
    <aside class="relative isolate hidden overflow-hidden lg:block">
        <picture class="krm-kenburns absolute inset-0 -z-20">
            <img src="{{ asset('images/hero-roastery.webp') }}" alt="" width="1920" height="1280" decoding="async"
                 class="h-full w-full object-cover object-[62%_50%]">
        </picture>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(18,18,18,.55)_0%,rgba(18,18,18,.35)_40%,rgba(18,18,18,.92)_100%)]"></div>
        <div class="flex h-full flex-col justify-between p-12">
            <a href="{{ route('landing') }}" wire:navigate class="inline-flex items-center gap-3">
                <img src="{{ asset('images/mark-kromatik-white.png') }}" alt="" width="40" height="40" class="h-10 w-10">
                <span class="font-display text-[18px] font-semibold tracking-[0.22em]">KROMATIK</span>
            </a>
            <div>
                <p class="krm-readout text-[12px] tracking-[0.18em] text-kraft/70">{{ __('SPECIALTY COFFEE LAB') }}</p>
                <p class="mt-4 max-w-[16ch] font-display text-[2.6rem] font-bold leading-[1.02] tracking-[-0.02em]">
                    {{ __('Disangrai setelah pesanan masuk.') }}
                </p>
            </div>
        </div>
    </aside>

    <main class="relative flex min-h-screen flex-col px-6 py-8 sm:px-12">
        <div class="flex items-center justify-between">
            <a href="{{ route('landing') }}" wire:navigate class="inline-flex items-center gap-2.5 lg:invisible">
                <img src="{{ asset('images/mark-kromatik-white.png') }}" alt="" width="32" height="32" class="h-8 w-8">
                <span class="font-display text-[15px] font-semibold tracking-[0.2em]">KROMATIK</span>
            </a>
            <x-lang-switch class="flex" />
        </div>

        <div class="mx-auto flex w-full max-w-[400px] flex-1 flex-col justify-center py-10">
            <h1 class="krm-h2 font-bold" data-reveal>{{ $copy[0] }}</h1>
            @if ($copy[1])<p class="mt-3 text-[14.5px] leading-relaxed text-kraft/75" data-reveal style="--d:80ms">{{ $copy[1] }}</p>@endif

            <div class="mt-8" data-reveal style="--d:160ms">
                {{ $slot }}
            </div>
        </div>

        <a href="{{ route('landing') }}" wire:navigate class="text-center text-[12.5px] text-kraft/50 transition-colors hover:text-labwhite">&larr; {{ __('Kembali ke beranda') }}</a>
    </main>
</div>
</body>
</html>
