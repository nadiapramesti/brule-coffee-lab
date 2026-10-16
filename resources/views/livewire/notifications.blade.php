@php
    $icons = [
        'order' => 'M6 6h15l-1.5 9h-12zM6 6L5 3H2M9 20a1 1 0 100-2 1 1 0 000 2zM18 20a1 1 0 100-2 1 1 0 000 2z',
        'queue' => 'M4 7h16M4 12h16M4 17h10',
        'status' => 'M12 3v9l5 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'ship' => 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM17 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
        'stock' => 'M12 3l9 16H3zM12 10v4M12 17v.01',
        'cancel' => 'M6 6l12 12M18 6L6 18',
    ];
    $tone = ['stock' => 'text-ambertext border-[#E8782A]/40', 'cancel' => 'text-redtext border-[#E26D6D]/40'];
@endphp
<div class="relative" wire:poll.20s x-data="{ o: false }" @click.outside="o = false" @keydown.escape.window="o = false">
    <button type="button" @click="o = !o" :aria-expanded="o ? 'true' : 'false'" aria-haspopup="true"
            class="relative grid h-9 w-9 place-items-center rounded border border-kraft/20 text-kraft/75 transition-colors hover:border-kraft/50 hover:text-labwhite"
            aria-label="{{ __('Notifikasi') }}{{ $this->unread ? ': '.$this->unread : '' }}" title="{{ __('Notifikasi') }}">
        <svg viewBox="0 0 24 24" class="h-[17px] w-[17px]" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9a6 6 0 1112 0c0 5 2 6 2 6H4s2-1 2-6zM10 19a2 2 0 004 0"/></svg>
        @if ($this->unread)
            <span class="krm-readout absolute -right-1.5 -top-1.5 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-[#E8782A] px-1 text-[10.5px] font-semibold text-night">{{ $this->unread > 99 ? '99+' : $this->unread }}</span>
        @endif
    </button>

    <div x-show="o" x-cloak x-transition.opacity.duration.150ms role="dialog" aria-label="{{ __('Notifikasi') }}"
         class="fixed inset-x-3 top-16 z-50 overflow-hidden rounded-[10px] border border-kraft/25 bg-surface shadow-[0_20px_50px_-15px_rgb(var(--shadow)/.9)] sm:absolute sm:inset-x-auto sm:right-0 sm:top-[calc(100%+8px)] sm:w-[380px]">
        <div class="flex items-center justify-between border-b border-kraft/15 px-4 py-3">
            <p class="text-[13.5px] font-medium">{{ __('Notifikasi') }}
                @if ($this->unread)<span class="krm-readout ml-1.5 text-[11px] text-kraft/50">{{ $this->unread }} {{ __('baru') }}</span>@endif
            </p>
            @if ($this->unread)
                <button type="button" wire:click="markAllRead" class="text-[12.5px] text-bluetext transition-colors hover:text-labwhite">{{ __('Tandai semua dibaca') }}</button>
            @endif
        </div>

        <div class="max-h-[min(70vh,440px)] overflow-y-auto">
            @forelse ($this->items as $n)
                <button type="button" wire:click="open({{ $n->id }})" wire:key="n-{{ $n->id }}"
                        class="relative flex w-full items-start gap-3 border-b border-kraft/10 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-labwhite/[.04]">
                    @unless ($n->read_at)<span class="absolute inset-y-0 left-0 w-[2px] bg-bluelab" aria-hidden="true"></span>@endunless
                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full border {{ $tone[$n->type] ?? 'border-kraft/25 text-bluetext' }}">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$n->type] ?? $icons['status'] }}"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="truncate text-[13.5px] {{ $n->read_at ? 'text-kraft/70' : 'font-medium text-labwhite' }}">{{ $n->titleText() }}</span>
                            <span class="krm-readout shrink-0 text-[10.5px] text-kraft/45">{{ $n->created_at->locale(app()->getLocale())->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, parts: 1) }}</span>
                        </span>
                        @if ($n->bodyText())<span class="mt-0.5 block text-[12.5px] leading-snug text-kraft/55 [overflow-wrap:anywhere]">{{ $n->bodyText() }}</span>@endif
                    </span>
                </button>
            @empty
                <div class="px-4 py-10 text-center">
                    <p class="text-[13.5px] text-kraft/70">{{ __('Belum ada notifikasi') }}</p>
                    <p class="mt-1 text-[12.5px] text-kraft/45">{{ __('Kabar pesanan dan stok akan muncul di sini.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
