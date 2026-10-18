@props(['product', 'option' => null])
@php
    $url = $product->imageUrl($option);
    // Foto merchandise membawa logo B kromatogram; teks alternatifnya menyebut itu.
    $alt = $product->category === 'merch' ? 'BRÛLÉ merch - B chromatogram high contrast' : $product->localizedName();
@endphp
<div {{ $attributes->class(['relative grid shrink-0 place-items-center overflow-hidden rounded border border-kraft/20 bg-labwhite/[.04]']) }}>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
    @else
        <x-product-art :product="$product" class="absolute inset-0 h-full w-full" />
    @endif
</div>
