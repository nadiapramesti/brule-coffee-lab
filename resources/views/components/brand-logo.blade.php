{{--
    Logo BRÛLÉ coffee lab. Dua berkas SVG monokrom murni (tanpa abu-abu):
    putih untuk latar gelap, hitam untuk latar terang. Pilihan berkas dilakukan CSS
    (lihat .krm-logo-* di app.css) mengikuti tema DAN area .krm-dark (hero, footer),
    jadi tidak ada kedipan saat halaman dimuat dan tidak perlu JS.

    variant: full (horizontal, header/footer) | mark (ikon saja) | stacked (vertikal)
    force:   'light' (selalu hitam) | 'dark' (selalu putih), untuk latar yang warnanya tetap
--}}
@props(['variant' => 'full', 'force' => null])
@php
    // viewBox tiap berkas: horizontal & mark punya margin 6 unit untuk garis yang ditebalkan.
    [$base, $w, $h] = match ($variant) {
        'mark' => ['logo-brule-mark', 539, 632],
        'stacked' => ['logo-brule-stacked', 587, 910],
        default => ['logo-brule', 1547, 632],
    };
    $alt = 'BRÛLÉ coffee lab';
@endphp
{{-- Wrapper menerima class tata letak (tinggi, hidden, dll). Gambar di dalamnya tidak diberi
     utility display, supaya tidak bentrok dengan aturan tema di .krm-logo-*. --}}
<div {{ $attributes }} style="line-height:0">
    @if ($force !== 'dark')
        <img src="{{ asset($base.'-light.svg') }}" alt="{{ $alt }}" width="{{ $w }}" height="{{ $h }}"
             class="{{ $force ? '' : 'krm-logo-light' }} h-full w-auto max-w-none">
    @endif
    @if ($force !== 'light')
        <img src="{{ asset($base.'-dark.svg') }}" alt="{{ $alt }}" width="{{ $w }}" height="{{ $h }}"
             class="{{ $force ? '' : 'krm-logo-dark' }} h-full w-auto max-w-none">
    @endif
</div>
