@php
    $inp = 'w-full rounded border border-kraft/25 bg-transparent px-3 py-2.5 text-[14px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $rp = fn ($v) => 'Rp' . number_format((int) $v, 0, ',', '.');
@endphp
<div>
    <div>
        <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Pengaturan toko') }}</h1>
        <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Identitas, kontak, ongkir, dan batas stok. Dipakai di footer, checkout, dan invoice.') }}</p>
    </div>

    @if ($notice)
        <p role="status" x-data x-init="setTimeout(() => $el.remove(), 5000)" class="mt-4 rounded border border-bluelab/50 bg-bluelab/10 px-4 py-2.5 text-[13px]">{{ $notice }}</p>
    @endif

    <form wire:submit="save" class="mt-4 grid gap-4 xl:grid-cols-2" novalidate>
        {{-- Identitas --}}
        <section class="rounded border border-kraft/20 bg-charcoal">
            <header class="border-b border-kraft/15 px-5 py-3.5"><h2 class="font-display text-[1rem] font-semibold">{{ __('Identitas toko') }}</h2></header>
            <div class="space-y-4 p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="sn" class="text-[12.5px] text-kraft/75">{{ __('Nama toko') }}</label>
                        <input id="sn" wire:model="storeName" placeholder="BRÛLÉ" class="{{ $inp }} mt-1.5">
                        @error('storeName')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="sc" class="text-[12.5px] text-kraft/75">{{ __('Kota') }}</label>
                        <input id="sc" wire:model="city" placeholder="Bandung" class="{{ $inp }} mt-1.5">
                        @error('city')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="st" class="text-[12.5px] text-kraft/75">{{ __('Tagline') }}</label>
                    <input id="st" wire:model="tagline" placeholder="Specialty Coffee Lab" class="{{ $inp }} mt-1.5">
                    @error('tagline')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="sa" class="text-[12.5px] text-kraft/75">{{ __('Alamat roastery') }}</label>
                    <input id="sa" wire:model="address" placeholder="{{ __('Contoh: Jl. Braga No. 12, Kota Bandung, Jawa Barat') }}" class="{{ $inp }} mt-1.5">
                    @error('address')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- Kontak --}}
        <section class="rounded border border-kraft/20 bg-charcoal">
            <header class="border-b border-kraft/15 px-5 py-3.5"><h2 class="font-display text-[1rem] font-semibold">{{ __('Kontak') }}</h2></header>
            <div class="space-y-4 p-5">
                <div>
                    <label for="ce" class="text-[12.5px] text-kraft/75">Email</label>
                    <input id="ce" type="email" wire:model="email" placeholder="halo@brule.id" class="{{ $inp }} mt-1.5">
                    @error('email')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="cw" class="text-[12.5px] text-kraft/75">WhatsApp</label>
                        <input id="cw" type="tel" wire:model="whatsapp" placeholder="081234567890" class="{{ $inp }} krm-readout mt-1.5">
                        @error('whatsapp')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ci" class="text-[12.5px] text-kraft/75">Instagram</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[14px] text-kraft/45">@</span>
                            <input id="ci" wire:model="instagram" placeholder="brule.coffeelab" class="{{ $inp }} pl-7">
                        </div>
                        @error('instagram')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="text-[12px] leading-relaxed text-kraft/50">{{ __('Nomor WhatsApp boleh ditulis 08…, 62…, atau +62…; Instagram boleh berupa @username atau link profil.') }}</p>
            </div>
        </section>

        {{-- Pengiriman --}}
        <section class="rounded border border-kraft/20 bg-charcoal">
            <header class="border-b border-kraft/15 px-5 py-3.5"><h2 class="font-display text-[1rem] font-semibold">{{ __('Pengiriman') }}</h2></header>
            <div class="space-y-4 p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="sf" class="text-[12.5px] text-kraft/75">{{ __('Ongkir flat (Rp)') }}</label>
                        <input id="sf" type="number" inputmode="numeric" wire:model.live.debounce.300ms="shippingFlat" placeholder="15000" class="{{ $inp }} krm-readout mt-1.5">
                        @error('shippingFlat')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ff" class="text-[12.5px] text-kraft/75">{{ __('Gratis ongkir mulai (Rp)') }}</label>
                        <input id="ff" type="number" inputmode="numeric" wire:model.live.debounce.300ms="freeFrom" placeholder="500000" class="{{ $inp }} krm-readout mt-1.5">
                        @error('freeFrom')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="rounded border border-kraft/15 bg-labwhite/[.02] px-3 py-2.5 text-[12.5px] text-kraft/65">
                    @if ((int) $freeFrom > 0)
                        {{ __('Pratinjau: belanja di bawah :f kena ongkir :s, selebihnya gratis.', ['f' => $rp($freeFrom), 's' => $rp($shippingFlat)]) }}
                    @else
                        {{ __('Pratinjau: semua pesanan kena ongkir :s (tanpa gratis ongkir).', ['s' => $rp($shippingFlat)]) }}
                    @endif
                </p>
                <p class="text-[12px] text-kraft/50">{{ __('Isi 0 pada "Gratis ongkir mulai" untuk mematikan gratis ongkir. Pesanan yang sudah dibuat tidak berubah.') }}</p>
            </div>
        </section>

        {{-- Pembayaran --}}
        <section class="rounded border border-kraft/20 bg-charcoal">
            <header class="border-b border-kraft/15 px-5 py-3.5"><h2 class="font-display text-[1rem] font-semibold">{{ __('Pembayaran') }}</h2></header>
            <div class="space-y-4 p-5">
                <label class="flex cursor-pointer items-center gap-3 text-[14px]">
                    <input type="checkbox" wire:model.live="codEnabled" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                    {{ __('Terima pembayaran COD (bayar di tempat)') }}
                </label>
                <div class="sm:max-w-[260px] {{ $codEnabled ? '' : 'opacity-50' }}">
                    <label for="cm" class="text-[12.5px] text-kraft/75">{{ __('Batas total COD (Rp)') }}</label>
                    <input id="cm" type="number" inputmode="numeric" wire:model="codMax" @disabled(! $codEnabled) placeholder="500000" class="{{ $inp }} krm-readout mt-1.5">
                    @error('codMax')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                </div>
                <p class="text-[12px] leading-relaxed text-kraft/50">{{ __('Isi 0 untuk tanpa batas. Pesanan di atas batas hanya bisa bayar online. Pesanan yang sudah dibuat tidak berubah.') }}</p>
            </div>
        </section>

        {{-- Stok --}}
        <section class="rounded border border-kraft/20 bg-charcoal">
            <header class="border-b border-kraft/15 px-5 py-3.5"><h2 class="font-display text-[1rem] font-semibold">{{ __('Stok') }}</h2></header>
            <div class="space-y-4 p-5">
                <div class="sm:max-w-[260px]">
                    <label for="ls" class="text-[12.5px] text-kraft/75">{{ __('Batas stok menipis (gram)') }}</label>
                    <input id="ls" type="number" inputmode="numeric" wire:model="lowStock" placeholder="5000" class="{{ $inp }} krm-readout mt-1.5">
                    @error('lowStock')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                </div>
                <p class="text-[12px] leading-relaxed text-kraft/50">{{ __('Kopi dengan stok biji mentah di bawah angka ini ditandai "Menipis" di Dashboard, Kelola kopi, dan halaman Stok.') }}</p>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3 xl:col-span-2">
            <span wire:loading wire:target="save" class="text-[12.5px] text-kraft/55">{{ __('Menyimpan…') }}</span>
            <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-7 py-3 font-display text-[14.5px] font-semibold transition-colors hover:bg-[#234E94] disabled:opacity-60">{{ __('Simpan pengaturan') }}</button>
        </div>
    </form>
</div>
