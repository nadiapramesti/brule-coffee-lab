@props(['xlsx', 'pdf'])
{{-- Satu tombol "Unduh" dengan dua pilihan format, menggantikan dua tombol berdampingan. --}}
<div class="relative" x-data="{ o: false }" @click.outside="o = false" @keydown.escape.window="o = false">
    <button type="button" @click="o = !o" :aria-expanded="o ? 'true' : 'false'" aria-haspopup="menu"
            class="inline-flex items-center gap-2 rounded border border-kraft/30 px-4 py-2.5 text-[13.5px] transition-colors hover:border-kraft/70">
        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>
        {{ __('Unduh') }}
        <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 text-kraft/70 transition-transform" :class="o && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8l5 5 5-5"/></svg>
    </button>
    <div x-show="o" x-cloak x-transition.opacity.duration.150ms role="menu"
         class="absolute right-0 top-[calc(100%+6px)] z-40 w-44 overflow-hidden rounded-[10px] border border-kraft/25 bg-surface shadow-[0_20px_50px_-15px_rgb(var(--shadow)/.9)]">
        <a href="{{ $xlsx }}" role="menuitem" class="flex items-center justify-between px-4 py-2.5 text-[13.5px] text-kraft/85 transition-colors hover:bg-labwhite/[.05] hover:text-labwhite">Excel <span class="krm-readout text-[11px] text-kraft/70">.xlsx</span></a>
        <a href="{{ $pdf }}" role="menuitem" class="flex items-center justify-between px-4 py-2.5 text-[13.5px] text-kraft/85 transition-colors hover:bg-labwhite/[.05] hover:text-labwhite">PDF <span class="krm-readout text-[11px] text-kraft/70">.pdf</span></a>
    </div>
</div>
