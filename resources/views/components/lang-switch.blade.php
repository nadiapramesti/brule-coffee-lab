{{-- Saklar ID | EN. Bahasa aktif ditandai garis bawah biru, bukan tombol penuh,
     supaya tidak berebut perhatian dengan CTA. --}}
<div {{ $attributes->class('items-center gap-1 text-[13px]') }} role="group" aria-label="{{ __('Bahasa') }}">
    @foreach (['id' => 'ID', 'en' => 'EN'] as $code => $label)
        <a href="{{ route('locale', $code) }}"
           hreflang="{{ $code }}" lang="{{ $code }}"
           @if (app()->getLocale() === $code) aria-current="true" @endif
           class="krm-readout rounded px-2 py-1 tracking-wider transition-colors
                  {{ app()->getLocale() === $code
                        ? 'text-labwhite underline decoration-bluelab decoration-2 underline-offset-[6px]'
                        : 'text-kraft/60 hover:text-labwhite' }}">{{ $label }}</a>
        @unless ($loop->last)<span class="text-kraft/25" aria-hidden="true">/</span>@endunless
    @endforeach
</div>
