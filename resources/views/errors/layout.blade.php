{{-- Halaman error mandiri: tidak memakai Livewire, DB, atau session, supaya tetap
     tampil walau penyebab errornya justru di sana (mis. database mati saat 500). --}}
@php
    $loc = 'id';
    try { if (request()->hasSession()) { $loc = session('locale', 'id'); } } catch (\Throwable $e) {}
    app()->setLocale(in_array($loc, ['id', 'en'], true) ? $loc : 'id');
    $code = $code ?? 500;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · KROMATIK</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @include('layouts.partials.theme-boot')
    @vite(['resources/css/app.css'])
</head>
<body class="krm-grid flex min-h-screen flex-col bg-charcoal text-labwhite">
    <header class="mx-auto flex w-full max-w-[1180px] items-center px-6 py-5">
        <a href="{{ url('/') }}" class="flex items-center" aria-label="BRÛLÉ coffee lab">
            <x-brand-logo class="h-11" />
        </a>
    </header>

    <main class="mx-auto flex w-full max-w-[1180px] flex-1 flex-col justify-center px-6 pb-24 pt-8">
        <p class="krm-readout text-[12px] tracking-[0.18em] text-kraft/55">{{ __('KODE GALAT') }} · {{ $code }}</p>
        <p class="mt-2 font-display text-[clamp(5rem,18vw,11rem)] font-bold leading-none tracking-tight text-bluelab" aria-hidden="true">{{ $code }}</p>
        <h1 class="krm-h2 mt-4 max-w-[20ch] font-semibold">{{ $heading }}</h1>
        <p class="mt-3 max-w-[52ch] text-[15px] leading-relaxed text-kraft/75">{{ $body }}</p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ url('/') }}" class="rounded bg-bluelab px-5 py-2.5 font-display text-[14px] font-semibold text-paper transition-colors hover:bg-[#234E94]">{{ __('Ke beranda') }}</a>
            <a href="{{ url('/builder') }}" class="rounded border border-kraft/35 px-5 py-2.5 font-display text-[14px] font-semibold text-kraft transition-colors hover:border-kraft/70 hover:text-labwhite">{{ __('Racik kopimu') }}</a>
        </div>
    </main>
</body>
</html>
