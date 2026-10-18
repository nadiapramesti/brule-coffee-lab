@props(['product'])
@php
    use Illuminate\Support\Str;

    // Ilustrasi sederhana per produk, satu palet dengan kantong kopi: latar gelap, benda warna kraft, satu aksen.
    $kraft = '#D8C5A6'; $ink = '#5C4B33'; $blue = '#2A5DB0'; $orange = '#E8782A';
    $key = match (true) {
        Str::contains($product->slug, 'v60') && Str::contains($product->slug, 'kertas') => 'filter',
        Str::contains($product->slug, 'v60') => 'dripper',
        Str::contains($product->slug, 'aeropress') => 'aeropress',
        Str::contains($product->slug, 'french') => 'press',
        Str::contains($product->slug, 'grinder') => 'grinder',
        Str::contains($product->slug, 'timbangan') => 'scale',
        Str::contains($product->slug, 'kettle') => 'kettle',
        Str::contains($product->slug, ['kaos']) => 'tee',
        Str::contains($product->slug, ['tote']) => 'tote',
        Str::contains($product->slug, ['mug']) => 'mug',
        default => $product->category,
    };
    $uid = 'pa'.$product->id.Str::random(3);
@endphp
<svg viewBox="0 0 200 200" role="img" aria-label="{{ $product->localizedName() }}" {{ $attributes->class('block bg-[#161616]') }}>
    <defs>
        <radialGradient id="{{ $uid }}-g" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#000" stop-opacity=".55"/><stop offset="1" stop-color="#000" stop-opacity="0"/></radialGradient>
        <linearGradient id="{{ $uid }}-k" x1="0" x2="1"><stop offset="0" stop-color="{{ $kraft }}"/><stop offset="1" stop-color="#B9A482"/></linearGradient>
    </defs>
    <ellipse cx="100" cy="176" rx="64" ry="6" fill="url(#{{ $uid }}-g)"/>
    <g stroke="{{ $ink }}" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round" fill="url(#{{ $uid }}-k)">
    @switch($key)
        @case('dripper')
            <path d="M74 138h52v36a4 4 0 01-4 4H78a4 4 0 01-4-4z"/>
            <path d="M44 70h112l-30 62H74z"/>
            <path d="M156 78c18 0 18 24 0 24" fill="none"/>
            <path d="M62 70l22 62M80 70l12 62M100 70v62M120 70l-12 62M138 70l-22 62" fill="none" stroke-opacity=".45"/>
            <path d="M44 70h112" stroke="{{ $blue }}" stroke-width="3"/>
            @break
        @case('aeropress')
            <rect x="72" y="146" width="56" height="30" rx="4"/>
            <rect x="78" y="124" width="44" height="22" rx="3"/>
            <rect x="82" y="40" width="36" height="86" rx="3"/>
            <rect x="88" y="26" width="24" height="16" rx="3" fill="{{ $blue }}" stroke="{{ $blue }}"/>
            <path d="M82 62h36M82 78h36M82 94h36M82 110h36" fill="none" stroke-opacity=".4"/>
            @break
        @case('press')
            <path d="M100 26v34" fill="none" stroke-width="3"/>
            <circle cx="100" cy="24" r="7" fill="{{ $blue }}" stroke="{{ $blue }}"/>
            <rect x="62" y="60" width="76" height="104" rx="8" fill="#2a2a2a" fill-opacity=".6" stroke="{{ $kraft }}" stroke-opacity=".7"/>
            <path d="M64 108h72v48a8 8 0 01-8 8H72a8 8 0 01-8-8z" fill="#3a2a1e" stroke="none"/>
            <rect x="58" y="56" width="84" height="8" rx="3"/>
            <rect x="58" y="160" width="84" height="10" rx="3"/>
            <path d="M142 82c24 0 24 58 0 58" fill="none" stroke-width="3"/>
            @break
        @case('grinder')
        @case('grinder')
            <path d="M100 56v14M100 56h44" fill="none" stroke-width="3"/>
            <circle cx="148" cy="56" r="7" fill="{{ $orange }}" stroke="{{ $orange }}"/>
            <rect x="82" y="46" width="36" height="14" rx="3"/>
            <path d="M76 70h48l-6 70H82z"/>
            <path d="M80 92h40M82 112h36" fill="none" stroke-opacity=".4"/>
            <rect x="70" y="140" width="60" height="30" rx="4" fill="#2a2a2a" stroke="{{ $kraft }}" stroke-opacity=".7"/>
            @break
        @case('filter')
            <ellipse cx="100" cy="150" rx="46" ry="12"/><path d="M54 150v-10c0-7 20-12 46-12s46 5 46 12v10" />
            <ellipse cx="100" cy="140" rx="46" ry="12"/><ellipse cx="100" cy="130" rx="46" ry="12"/>
            <path d="M70 92h60l-14 30H84z" fill="#F2EBDD"/>
            <path d="M100 92v30" fill="none" stroke-opacity=".4"/>
            <path d="M54 130v20" fill="none" stroke="{{ $blue }}" stroke-width="3"/>
            @break
        @case('scale')
            <rect x="36" y="112" width="128" height="52" rx="8"/>
            <rect x="52" y="124" width="52" height="24" rx="3" fill="#111" stroke="#111"/>
            <text x="78" y="142" text-anchor="middle" font-family="'IBM Plex Mono', monospace" font-size="13" fill="{{ $orange }}" stroke="none">18.0</text>
            <circle cx="132" cy="136" r="8" fill="{{ $blue }}" stroke="{{ $blue }}"/>
            <rect x="52" y="96" width="96" height="12" rx="4"/>
            @break
        @case('kettle')
            <path d="M100 52v-8" fill="none" stroke-width="3"/><circle cx="100" cy="42" r="5" fill="{{ $blue }}" stroke="{{ $blue }}"/>
            <path d="M66 140c-4-30 2-62 14-70h40c12 8 18 40 14 70z"/>
            <path d="M68 110c-22-6-30-30-44-34" fill="none" stroke-width="5"/>
            <path d="M134 84c26 0 26 44 0 48" fill="none" stroke-width="4"/>
            <rect x="62" y="140" width="76" height="12" rx="3"/>
            @break
        @case('tee')
            <path d="M72 50c6 10 14 14 28 14s22-4 28-14l32 18-14 28-14-6v70H68V90l-14 6-14-28z"/>
            <polyline fill="none" stroke="{{ $blue }}" stroke-width="2" points="78,128 86,128 90,112 94,128 100,128 104,100 108,128 114,128 118,120 122,128"/>
            <path d="M78 130h44" stroke-opacity=".4" fill="none"/>
            @break
        @case('tote')
            <path d="M78 74c0-36 44-36 44 0" fill="none" stroke-width="4"/>
            <path d="M56 70h88l6 102H50z"/>
            <polyline fill="none" stroke="{{ $orange }}" stroke-width="2" points="76,136 86,136 91,118 96,136 104,136 109,106 114,136 124,136"/>
            <path d="M76 140h48" stroke-opacity=".4" fill="none"/>
            @break
        @case('mug')
            <path d="M136 84c26 0 26 52 0 52" fill="none" stroke-width="5"/>
            <path d="M62 70h76v78a14 14 0 01-14 14H76a14 14 0 01-14-14z"/>
            <rect x="62" y="70" width="76" height="9" rx="3" fill="{{ $blue }}" stroke="{{ $blue }}"/>
            <path d="M84 52c-6 8 6 10 0 18M104 52c-6 8 6 10 0 18" fill="none" stroke="{{ $kraft }}" stroke-opacity=".55"/>
            @break
        @default
            <rect x="56" y="60" width="88" height="100" rx="6"/>
            <path d="M56 84h88" fill="none" stroke="{{ $blue }}" stroke-width="3"/>
    @endswitch
    </g>
</svg>
