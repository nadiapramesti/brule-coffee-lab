@props(['id', 'model', 'label', 'autocomplete' => 'current-password', 'error' => null, 'aside' => null])
<div>
    <div class="flex items-baseline justify-between gap-3">
        <label for="{{ $id }}">{{ $label }}</label>
        @if ($aside){{ $aside }}@endif
    </div>
    <div class="relative mt-1.5" x-data="{ show: false }">
        <input wire:model="{{ $model }}" id="{{ $id }}" name="{{ $id }}" :type="show ? 'text' : 'password'" type="password"
               required autocomplete="{{ $autocomplete }}" class="pr-12">
        <button type="button" @click="show = !show" :aria-label="show ? '{{ __('Sembunyikan password') }}' : '{{ __('Tampilkan password') }}'"
                class="absolute right-1.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded text-kraft/60 transition-colors hover:text-labwhite">
            <svg x-show="!show" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg x-show="show" x-cloak viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 6.7A10 10 0 0112 6.5c6.4 0 10 5.5 10 5.5a17 17 0 01-3.200 3.8M6.3 8.3C3.6 10 2 12 2 12s3.6 6.5 10 6.5c1.5 0 2.8-.4 4-.9"/></svg>
        </button>
    </div>
    <x-input-error :messages="$error" class="mt-1.5" />
</div>
