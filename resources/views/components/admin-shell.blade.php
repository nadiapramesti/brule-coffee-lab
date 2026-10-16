@props(['active' => 'dashboard'])
@php
    $items = [
        ['dashboard', route('admin.dashboard'), 'Dashboard', 'M3 12l9-8 9 8M5 10v10h5v-6h4v6h5V10'],
        ['coffees', route('admin.coffees'), __('Kelola kopi'), 'M5 8h11v6a5 5 0 01-5 5H10a5 5 0 01-5-5V8zM16 9h2a2 2 0 010 4h-2M8 3v2M12 3v2'],
        ['queue', route('roastery'), __('Antrian roastery'), 'M4 6h16M4 12h16M4 18h10'],
        ['shop', route('landing'), __('Lihat toko'), 'M7 17L17 7M9 7h8v8'],
    ];
@endphp
<div class="mx-auto max-w-[1280px] px-6 pb-24 pt-8 lg:grid lg:grid-cols-[210px_minmax(0,1fr)] lg:gap-10 lg:pt-12">
    <aside class="lg:sticky lg:top-24 lg:self-start">
        <p class="krm-readout hidden text-[11.5px] tracking-[0.18em] text-kraft/55 lg:block">{{ __('PANEL ADMIN') }}</p>
        <nav aria-label="{{ __('Menu admin') }}" class="-mx-6 flex gap-2 overflow-x-auto px-6 pb-2 lg:mx-0 lg:mt-3 lg:flex-col lg:gap-1 lg:overflow-visible lg:px-0 lg:pb-0">
            @foreach ($items as [$key, $url, $label, $icon])
                <a href="{{ $url }}" @if (in_array($key, ['dashboard', 'coffees'])) wire:navigate @endif
                   @if ($active === $key) aria-current="page" @endif
                   class="flex shrink-0 items-center gap-2.5 rounded border px-3.5 py-2.5 text-[13.5px] transition-colors lg:border-transparent
                          {{ $active === $key ? 'border-bluelab/60 bg-bluelab/15 text-labwhite lg:border-bluelab/60' : 'border-kraft/20 text-kraft/75 hover:border-kraft/40 hover:text-labwhite lg:hover:bg-white/[.04]' }}">
                    <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    <span class="whitespace-nowrap">{{ $label }}</span>
                </a>
            @endforeach
        </nav>
    </aside>
    <div class="mt-6 min-w-0 lg:mt-0">{{ $slot }}</div>
</div>
