@php
    $in = 'mt-1.5 w-full rounded border border-kraft/25 bg-transparent px-3 py-2.5 text-[14px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $lb = 'text-[12.5px] text-kraft/75';
    $er = 'mt-1 text-[12px] text-redtext';
    $card = 'rounded border border-kraft/15 p-4';
@endphp
<form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
    <header class="flex shrink-0 items-center justify-between gap-3 border-b border-kraft/15 px-5 py-3.5">
        <div>
            <h2 id="form-title" class="font-display text-[1.1rem] font-semibold leading-tight">{{ $productId ? __('Ubah produk') : __('Produk baru') }}</h2>
            @if ($productId)<p class="krm-readout text-[11px] text-kraft/70">{{ $slug }}</p>@endif
        </div>
        <button type="button" wire:click="$dispatch('close-product-form')" class="-mr-2 grid h-9 w-9 place-items-center rounded text-kraft/70 transition-colors hover:bg-labwhite/[.06] hover:text-labwhite" aria-label="{{ __('Tutup') }}">
            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg>
        </button>
    </header>

    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-5 py-4">
        <section class="{{ $card }}">
            <h2 class="font-display text-[0.95rem] font-semibold">{{ __('Info produk') }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="name" class="{{ $lb }}">{{ __('Nama produk') }}</label>
                    <input id="name" wire:model.live.debounce.400ms="name" placeholder="Hario V60 Dripper 02" class="{{ $in }}">
                    @error('name')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name_en" class="{{ $lb }}">{{ __('Nama produk (English)') }} <span class="text-kraft/70">· {{ __('opsional') }}</span></label>
                    <input id="name_en" wire:model="name_en" placeholder="Hario V60 Dripper 02" class="{{ $in }}">
                    @error('name_en')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="slug" class="{{ $lb }}">Slug (URL)</label>
                    <input id="slug" wire:model="slug" @disabled($productId) placeholder="hario-v60-dripper-02" class="{{ $in }} krm-readout text-[14px] disabled:opacity-50">
                    @error('slug')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="category" class="{{ $lb }}">{{ __('Kategori') }}</label>
                    <select id="category" wire:model="category" class="{{ $in }}">
                        @foreach (\App\Models\Product::CATEGORIES as $k => $label)<option value="{{ $k }}">{{ __($label) }}</option>@endforeach
                    </select>
                    @error('category')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-3">
                <label for="description" class="{{ $lb }}">{{ __('Deskripsi (Indonesia)') }}</label>
                <textarea id="description" rows="2" wire:model="description" placeholder="{{ __('Contoh: Dripper keramik untuk 1–4 cangkir. Aliran stabil, mudah dibersihkan.') }}" class="{{ $in }}"></textarea>
                @error('description')<p class="{{ $er }}">{{ $message }}</p>@enderror
            </div>
            <div class="mt-3">
                <label for="description_en" class="{{ $lb }}">{{ __('Deskripsi (English)') }} <span class="text-kraft/70">· {{ __('opsional') }}</span></label>
                <textarea id="description_en" rows="2" wire:model="description_en" placeholder="Example: Ceramic dripper for 1–4 cups. Steady flow, easy to clean." class="{{ $in }}"></textarea>
                @error('description_en')<p class="{{ $er }}">{{ $message }}</p>@enderror
            </div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="price" class="{{ $lb }}">{{ __('Harga (Rp)') }}</label>
                    <input id="price" type="number" inputmode="numeric" wire:model="price" placeholder="185000" class="{{ $in }} krm-readout">
                    @error('price')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="stock" class="{{ $lb }}">{{ __('Stok (unit)') }}</label>
                    <input id="stock" type="number" inputmode="numeric" wire:model="stock" placeholder="20" class="{{ $in }} krm-readout">
                    @error('stock')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="option_label" class="{{ $lb }}">{{ __('Nama pilihan') }} <span class="text-kraft/70">· {{ __('opsional') }}</span></label>
                    <input id="option_label" wire:model="option_label" placeholder="{{ __('Contoh: Ukuran') }}" class="{{ $in }}">
                    @error('option_label')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="options" class="{{ $lb }}">{{ __('Daftar pilihan') }} <span class="text-kraft/70">· {{ __('pisahkan dengan koma') }}</span></label>
                    <input id="options" wire:model="options" placeholder="S, M, L, XL" class="{{ $in }}">
                    @error('options')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="mt-2 text-[12px] text-kraft/70">{{ __('Stok dihitung total per produk, bukan per pilihan.') }}</p>

            <div class="mt-4">
                <span class="{{ $lb }}">{{ __('Foto produk') }} <span class="text-kraft/70">· {{ __('opsional') }}</span></span>
                @php($shown = $photo ? $photo->temporaryUrl() : $this->currentImageUrl())
                <div class="mt-1.5 flex items-start gap-4">
                    <div class="h-24 w-28 shrink-0 overflow-hidden rounded border border-kraft/15 bg-surface">
                        @if ($shown)<img src="{{ $shown }}" alt="{{ $name ?: __('Foto produk') }}" class="h-full w-full object-cover">@endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <label for="photo" class="inline-flex cursor-pointer items-center rounded border border-kraft/30 px-3 py-2 text-[13px] text-labwhite transition hover:bg-kraft/10 focus-within:ring-2 focus-within:ring-bluelab/40">
                                {{ $shown ? __('Ganti foto') : __('Pilih foto') }}
                                <input id="photo" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                            </label>
                            @if ($shown)
                                <button type="button" wire:click="clearPhoto" class="rounded px-3 py-2 text-[13px] text-redtext transition hover:bg-red-500/10">{{ $photo ? __('Batal pilih') : __('Hapus foto') }}</button>
                            @endif
                            <span wire:loading wire:target="photo" class="text-[12px] text-kraft/70">{{ __('Mengunggah…') }}</span>
                        </div>
                        <p class="mt-2 text-[12px] leading-relaxed text-kraft/70">{{ __('JPG, PNG, atau WebP. Maks 3 MB. Rasio 4:3 paling pas. Tanpa foto, toko menampilkan ikon kategori.') }}</p>
                        @error('photo')<p class="{{ $er }}">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            <label class="mt-4 flex cursor-pointer items-center gap-3 text-[14px]">
                <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluetext focus:ring-bluelab/40">
                {{ __('Tampilkan di toko') }}
            </label>
        </section>
    </div>

    <footer class="flex shrink-0 flex-wrap items-center justify-end gap-3 border-t border-kraft/15 bg-surface px-5 py-3.5">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="$dispatch('close-product-form')" class="rounded px-4 py-2.5 text-[13.5px] text-kraft/70 transition-colors hover:text-labwhite">{{ __('Batal') }}</button>
            <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-6 py-2.5 font-display text-[14px] font-semibold transition-colors hover:bg-[#234E94] disabled:opacity-60">
                <span wire:loading.remove wire:target="save">{{ __('Simpan') }}</span><span wire:loading wire:target="save">{{ __('Menyimpan…') }}</span>
            </button>
        </div>
    </footer>
</form>
