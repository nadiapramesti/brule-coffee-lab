@props(['coffee', 'level' => 'medium'])
@php
    use Illuminate\Support\Str;

    $t = $coffee->traceability;
    $process = $t?->process;
    $accents = ['Washed' => '#2A5DB0', 'Natural' => '#E8782A', 'Honey' => '#D9A441', 'Wet-Hulled' => '#5E8C6A', 'Anaerobic' => '#2FA3A3', 'Carbonic Maceration' => '#8E6BBF'];
    $accent = $accents[$process] ?? '#2A5DB0';

    // Nama pendek di label: buang kata proses, kata pertama jadi baris besar.
    $short = $coffee->shortName();
    $words = explode(' ', $short);
    $line1 = array_shift($words);
    $line2 = implode(' ', $words);
    $size1 = mb_strlen($line1) > 8 ? 21 : 25;

    $levels = array_keys(\App\Models\RoastProfile::LEVELS);
    $lv = max(0, (int) array_search($level, $levels, true));
    $lvLabel = strtoupper(\App\Models\RoastProfile::LEVELS[$levels[$lv]] ?? 'MEDIUM');

    // Kromatogram mini yang unik per kopi (bentuknya diturunkan dari slug, jadi tetap sama tiap render).
    $peaks = [];
    for ($i = 0; $i < 5; $i++) {
        $peaks[] = [55 + crc32($coffee->slug . 'c' . $i) % 88, 2.5 + crc32($coffee->slug . 'a' . $i) % 60 / 10, 1.6 + crc32($coffee->slug . 'w' . $i) % 18 / 10];
    }
    $pts = [];
    for ($x = 50; $x <= 150; $x += 2) {
        $y = 0;
        foreach ($peaks as [$c, $a, $w]) { $y += $a * exp(-pow(($x - $c) / $w, 2)); }
        $pts[] = $x . ',' . number_format(113 - $y, 2, '.', '');
    }
    $uid = 'b' . $coffee->id . Str::random(3);

    $photo = $coffee->image
        ? (Str::startsWith($coffee->image, ['http://', 'https://', '/']) ? $coffee->image : asset('storage/' . ltrim($coffee->image, '/')))
        : null;
@endphp

@if ($photo)
    <img src="{{ $photo }}" alt="{{ $coffee->name }}" loading="lazy" decoding="async" {{ $attributes->class('aspect-[4/5] rounded object-cover') }}>
@else
<svg viewBox="0 0 200 250" role="img" aria-label="{{ __('Kemasan :n', ['n' => $coffee->name]) }}" {{ $attributes->class('block aspect-[4/5]') }}>
    <defs>
        <linearGradient id="{{ $uid }}-body" x1="0" x2="1">
            <stop offset="0" stop-color="#232323"/><stop offset=".45" stop-color="#121212"/><stop offset="1" stop-color="#1c1c1c"/>
        </linearGradient>
        <radialGradient id="{{ $uid }}-gr" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="rgb(var(--shadow))" stop-opacity=".38"/><stop offset="1" stop-color="rgb(var(--shadow))" stop-opacity="0"/></radialGradient>
    </defs>

    <ellipse cx="100" cy="243" rx="86" ry="7" fill="url(#{{ $uid }}-gr)"/>

    {{-- badan kantong --}}
    <path d="M26 36H174C180 90 184 170 182 230Q182 244 170 244H30Q18 244 18 230C16 170 20 90 26 36Z" fill="url(#{{ $uid }}-body)" stroke="#fff" stroke-opacity=".13"/>
    <path d="M30 44C26 100 25 170 27 228" stroke="#fff" stroke-opacity=".07" stroke-width="3" fill="none" stroke-linecap="round"/>

    {{-- segel atas --}}
    <rect x="26" y="12" width="148" height="26" rx="3" fill="#191919"/>
    @foreach ([17, 22, 27, 32] as $ly)<line x1="28" x2="172" y1="{{ $ly }}" y2="{{ $ly }}" stroke="#000" stroke-opacity=".55"/>@endforeach
    <path d="M26 24l-4 2.5 4 2.5M174 24l4 2.5-4 2.5" stroke="#2a2a2a" fill="none"/>

    {{-- katup --}}
    <circle cx="152" cy="56" r="6.5" fill="#1d1d1d" stroke="#3a3a3a"/><circle cx="152" cy="56" r="2.2" fill="#0c0c0c"/>

    {{-- label kraft --}}
    <rect x="38" y="64" width="124" height="158" rx="3" fill="#D8C5A6"/>
    <rect x="42" y="68" width="116" height="150" rx="2" fill="none" stroke="#5C4B33" stroke-opacity=".45"/>

    <image href="{{ asset('logo-brule-light.svg') }}" x="64" y="76" width="72" height="29.4" preserveAspectRatio="xMidYMid meet" aria-label="BRÛLÉ"/>
    <g transform="translate(0 11)">
    <polyline points="{{ implode(' ', $pts) }}" fill="none" stroke="{{ $accent }}" stroke-width="1" stroke-linejoin="round" transform="translate(0 -2)"/>
    <line x1="50" x2="150" y1="111" y2="111" stroke="#1a1208" stroke-opacity=".35"/>

    <text x="100" y="143" text-anchor="middle" font-family="'IBM Plex Sans Condensed', system-ui, sans-serif" font-weight="700" font-size="{{ $size1 }}" fill="#1a1208">{{ $line1 }}</text>
    @if ($line2 !== '')
        <text x="100" y="160" text-anchor="middle" font-family="'IBM Plex Sans Condensed', system-ui, sans-serif" font-weight="600" font-size="{{ mb_strlen($line2) > 11 ? 11 : 13 }}" letter-spacing=".5" fill="#1a1208" fill-opacity=".82">{{ $line2 }}</text>
    @endif

    <line x1="50" x2="150" y1="170" y2="170" stroke="#1a1208" stroke-opacity=".35"/>
    <text x="100" y="182" text-anchor="middle" font-family="'IBM Plex Mono', monospace" font-size="6.2" letter-spacing=".6" fill="#5C4B33">{{ strtoupper($process ?? '') }}@if ($t?->altitude) · {{ number_format($t->altitude, 0, ',', '.') }} M @endif</text>

    @foreach (range(0, 4) as $i)
        <circle cx="{{ 64 + $i * 18 }}" cy="193" r="2.7" fill="{{ $i <= $lv ? $accent : 'none' }}" stroke="{{ $i <= $lv ? $accent : '#5C4B33' }}" stroke-opacity="{{ $i <= $lv ? 1 : .6 }}" stroke-width=".9"/>
    @endforeach
    <text x="100" y="204" text-anchor="middle" font-family="'IBM Plex Mono', monospace" font-size="5.2" letter-spacing=".8" fill="#5C4B33">{{ $lvLabel }}</text>
    </g>

    {{-- pita warna proses di dasar kantong --}}
    <rect x="24" y="228" width="152" height="4" rx="2" fill="{{ $accent }}"/>
</svg>
@endif
