@props(['value' => 0, 'size' => 'h-4 w-4'])
@php $v = max(0, min(5, (float) $value)); @endphp
<span {{ $attributes->class('inline-flex items-center gap-0.5 text-[#D9A441]') }} role="img" aria-label="{{ number_format($v, 1) }} / 5">
    @for ($i = 1; $i <= 5; $i++)
        <svg viewBox="0 0 20 20" class="{{ $size }} shrink-0" aria-hidden="true">
            <path d="M10 1.8l2.4 5.2 5.7.6-4.2 3.9 1.2 5.6L10 14.2l-5.1 2.9 1.2-5.6L1.9 7.6l5.7-.6L10 1.8z"
                  fill="{{ $v >= $i - 0.25 ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" opacity="{{ $v >= $i - 0.25 ? 1 : .45 }}"/>
        </svg>
    @endfor
</span>
