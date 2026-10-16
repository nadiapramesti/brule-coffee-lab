{{-- Tombol ganti tema terang/gelap. Ikon menunjukkan mode TUJUAN: matahari saat gelap, bulan saat terang. --}}
<button type="button" onclick="window.krmTheme && window.krmTheme.toggle()"
        {{ $attributes->class('grid h-9 w-9 shrink-0 place-items-center rounded border border-kraft/20 text-kraft/75 transition-colors hover:border-kraft/50 hover:text-labwhite') }}
        aria-label="{{ __('Ganti tema') }}" title="{{ __('Ganti tema') }}">
    <svg viewBox="0 0 24 24" class="hidden h-[17px] w-[17px] [html[data-theme=dark]_&]:block" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4L7 17M17 7l1.4-1.4"/></svg>
    <svg viewBox="0 0 24 24" class="block h-[17px] w-[17px] [html[data-theme=dark]_&]:hidden" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8 8 0 019.5 4 8 8 0 1020 14.5z"/></svg>
</button>
