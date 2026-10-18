@props(['product'])
@php
    $url = $product->imageUrl();
    // Satu glyph garis per kategori; dipakai bila admin belum mengunggah foto.
    $glyph = [
        'brewer'    => '<path d="M6 5h12l-2 6H8L6 5zM9 11l1.2 6h3.6L15 11M8 20h8"/>',
        'grinder'   => '<path d="M8 4h8l-1 5H9L8 4zM9 9v9a2 2 0 002 2h2a2 2 0 002-2V9M18 11h2v4"/>',
        'filter'    => '<path d="M5 6h14l-5 7v5l-4 2v-7L5 6z"/>',
        'merch'     => '<path d="M8 4l-4 3 2 3 2-1v11h8V9l2 1 2-3-4-3c-.6 1.3-1.8 2-4 2s-3.4-.7-4-2z"/>',
        'accessory' => '<path d="M5 13h10v3a4 4 0 01-4 4H9a4 4 0 01-4-4v-3zM15 14h2a2 2 0 010 4h-2M8 4c-1 1.4 1 2 0 3.5M11.5 4c-1 1.4 1 2 0 3.5"/>',
    ][$product->category] ?? '<rect x="5" y="5" width="14" height="14" rx="2"/>';
@endphp
<div {{ $attributes->class(['relative grid shrink-0 place-items-center overflow-hidden rounded border border-kraft/20 bg-labwhite/[.04]']) }}>
    @if ($url)
        <img src="{{ $url }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
    @else
        <svg viewBox="0 0 24 24" class="h-[44%] w-[44%] text-kraft/55" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $glyph !!}</svg>
    @endif
</div>
