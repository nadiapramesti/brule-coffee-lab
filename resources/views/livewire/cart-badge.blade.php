<a href="{{ route('cart') }}" wire:navigate aria-label="{{ __('Keranjang') }}{{ $count ? ' ('.$count.')' : '' }}"
   class="relative grid h-10 w-10 place-items-center rounded text-kraft/80 transition-colors hover:text-labwhite">
    <svg viewBox="0 0 24 24" class="h-[19px] w-[19px]" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 11.2a1.5 1.5 0 0 1-1.5 1.3H7.7a1.5 1.5 0 0 1-1.5-1.3L5 8Z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/></svg>
    @if ($count)
        <span class="krm-readout absolute right-0.5 top-0.5 grid h-[17px] min-w-[17px] place-items-center rounded-full bg-bluelab px-1 text-[10px] font-semibold leading-none text-paper">{{ $count }}</span>
    @endif
</a>
