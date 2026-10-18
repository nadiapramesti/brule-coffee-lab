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
    <title>{{ $copy[0] }} — BRÛLÉ</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @include('layouts.partials.theme-boot')
    <script>document.documentElement.classList.add('js')</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="krm-auth krm-grid min-h-screen bg-charcoal text-labwhite antialiased">
<div class="flex min-h-screen flex-col px-6 py-4 sm:py-5">

    <div class="flex items-center justify-between">
        <a href="{{ route('landing') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[13px] text-kraft/70 transition-colors hover:text-labwhite">
            <svg viewBox="0 0 12 12" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.5 2.5L4 6l3.5 3.5"/></svg>
            <span class="max-[420px]:hidden">{{ __('Kembali ke beranda') }}</span><span class="min-[421px]:hidden">{{ __('Beranda') }}</span>
        </a>
        <div class="flex items-center gap-3"><x-lang-switch class="flex" /><x-theme-toggle /></div>
    </div>

    <main class="flex flex-1 items-center justify-center py-3 sm:py-6">
        <div class="w-full {{ $route === 'register' ? 'max-w-[560px]' : 'max-w-[420px]' }}" data-reveal>
            <a href="{{ route('landing') }}" wire:navigate class="flex items-center justify-center" aria-label="BRÛLÉ coffee lab">
                <x-brand-logo class="h-14" />
            </a>

            <div class="mt-4 rounded-[14px] border border-kraft/20 bg-surface/90 p-5 shadow-[0_30px_60px_-30px_rgb(var(--shadow)/.9)] sm:mt-5 sm:p-7">
                <h1 class="font-display text-[1.65rem] font-bold leading-tight tracking-[-0.01em]">{{ $copy[0] }}</h1>
                @if ($copy[1])<p class="mt-1.5 text-[14px] leading-relaxed text-kraft/70 {{ $route === 'register' ? 'hidden' : '' }}">{{ $copy[1] }}</p>@endif
                <div class="{{ $route === 'register' ? 'mt-4' : 'mt-5 sm:mt-7' }}">
                    {{ $slot }}
                </div>
            </div>

            <p class="mt-5 text-center text-[12px] text-kraft/45 max-sm:hidden {{ $route === 'register' ? 'hidden' : '' }}">{{ __('Disangrai setelah pesanan masuk.') }}</p>
        </div>
    </main>
</div>

{{-- Ruang gulir yang hanya muncul di HP saat kolom isian fokus, dan tingginya dihitung PAS: cukup untuk
     menaikkan kolom yang fokus ke dekat atas layar (di atas keyboard), tidak lebih. Letaknya di luar
     wadah min-h-screen supaya tinggi halaman = 1 layar + ruang ini, bukan lebih. --}}
<div class="krm-kbspacer" aria-hidden="true"></div>
<script>
    if (!window.__krmKb) {
        window.__krmKb = true;
        var kbTimer, TOP_GAP = 140;
        document.addEventListener('focusin', function (e) {
            var el = e.target;
            if (!el.matches || !el.matches('input:not([type=checkbox]):not([type=radio]), textarea, select')) return;
            if (!window.matchMedia('(max-width: 639px)').matches) return;
            clearTimeout(kbTimer);
            var sp = document.querySelector('.krm-kbspacer');
            var natural = document.documentElement.scrollHeight - sp.offsetHeight;
            var target = Math.max(0, el.getBoundingClientRect().top + window.scrollY - TOP_GAP);
            sp.style.height = Math.max(0, target - Math.max(0, natural - window.innerHeight)) + 'px';
            setTimeout(function () { window.scrollTo({ top: target, behavior: 'smooth' }); }, 300);
        });
        document.addEventListener('focusout', function () {
            kbTimer = setTimeout(function () {
                var sp = document.querySelector('.krm-kbspacer');
                if (sp) sp.style.height = '0px';
            }, 200);
        });
    }
</script>
</body>
</html>
