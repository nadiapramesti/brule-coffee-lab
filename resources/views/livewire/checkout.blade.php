<div class="mx-auto max-w-[1080px] px-6 pb-20 pt-12 md:pt-16">
    <a href="{{ route('builder') }}" wire:navigate class="krm-readout text-[12px] text-kraft/60 transition-colors hover:text-labwhite">&larr; {{ __('Kembali ke builder') }}</a>
    <h1 class="krm-h2 mt-4 font-bold" data-reveal>Checkout</h1>
    <p class="mt-3 max-w-[52ch] text-[14.5px] text-kraft/75">{{ __('Satu langkah lagi. Kamu mengisi alamat, kami menimbang biji mentahnya.') }}</p>

    <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">

        <form wire:submit="submit" class="space-y-6" novalidate>
            @if ($error)
                <p class="rounded border border-red-400/40 bg-red-500/10 px-4 py-3 text-[13.5px] text-red-200">{{ $error }}</p>
            @endif

            @foreach ([
                ['name', __('Nama penerima'), 'text', 'name'],
                ['phone', __('Nomor WhatsApp'), 'tel', 'tel'],
            ] as [$field, $label, $type, $ac])
                <div>
                    <label for="{{ $field }}" class="text-[13px] text-kraft/75">{{ $label }}</label>
                    <input id="{{ $field }}" type="{{ $type }}" autocomplete="{{ $ac }}" wire:model="{{ $field }}"
                           class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3.5 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                    @error($field)<p class="mt-1.5 text-[12.5px] text-red-300">{{ $message }}</p>@enderror
                </div>
            @endforeach

            <div>
                <label for="address" class="text-[13px] text-kraft/75">{{ __('Alamat lengkap') }}</label>
                <textarea id="address" rows="4" wire:model="address" autocomplete="street-address"
                          placeholder="{{ __('Jalan, nomor, kelurahan, kota, kode pos') }}"
                          class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3.5 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40"></textarea>
                @error('address')<p class="mt-1.5 text-[12.5px] text-red-300">{{ $message }}</p>@enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded bg-bluelab py-4 font-display text-[15px] font-semibold transition-all hover:bg-[#234E94] disabled:opacity-60 lg:hidden">
                {{ __('Buat pesanan') }}
            </button>
        </form>

        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="overflow-hidden rounded border border-kraft/25 bg-[#161616]">
                <div class="p-6">
                    <p class="krm-readout text-[11px] tracking-[0.16em] text-kraft/55">{{ __('RINGKASAN PESANAN') }}</p>
                    <h2 class="mt-3 font-display text-[1.25rem] font-semibold leading-snug">{{ $this->profile->coffee->name }}</h2>
                    <p class="mt-1 text-[13.5px] text-kraft/70">{{ $this->profile->level_label }} · {{ __(\App\Models\OrderItem::GRINDS[$grind]) }} · {{ number_format($grams) }} g</p>
                </div>
                <div class="space-y-2.5 border-t border-dashed border-kraft/30 bg-[#101010] p-6 text-[13.5px]">
                    <div class="flex justify-between"><span class="text-kraft/65">{{ __('Kopi') }}</span><span class="krm-readout">Rp{{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-kraft/65">{{ __('Ongkir') }}</span>
                        <span class="krm-readout">{{ $this->shipping ? 'Rp'.number_format($this->shipping, 0, ',', '.') : __('Gratis') }}</span></div>
                    <div class="flex items-end justify-between border-t border-dashed border-kraft/30 pt-4">
                        <span class="text-kraft/65">{{ __('Total') }}</span>
                        <span class="krm-readout text-[1.7rem] leading-none">Rp{{ number_format($this->subtotal + $this->shipping, 0, ',', '.') }}</span>
                    </div>
                    <button type="button" wire:click="submit" wire:loading.attr="disabled"
                            class="mt-4 hidden w-full rounded bg-bluelab py-4 font-display text-[15px] font-semibold transition-all hover:bg-[#234E94] disabled:opacity-60 lg:block">
                        {{ __('Buat pesanan') }}
                    </button>
                    <p class="pt-1 text-center text-[12px] leading-relaxed text-kraft/50">
                        {{ __('Gratis ongkir mulai Rp500.000. Pembayaran disimulasikan di halaman berikutnya.') }}
                    </p>
                </div>
            </div>
        </aside>
    </div>
</div>
