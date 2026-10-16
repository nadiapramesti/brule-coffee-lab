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
                ['name', __('Nama penerima'), 'text', 'name', 'Budi Santoso'],
                ['phone', __('Nomor WhatsApp'), 'tel', 'tel', '081234567890'],
            ] as [$field, $label, $type, $ac, $ph])
                <div>
                    <label for="{{ $field }}" class="text-[13px] text-kraft/75">{{ $label }}</label>
                    <input id="{{ $field }}" type="{{ $type }}" autocomplete="{{ $ac }}" placeholder="{{ $ph }}" wire:model="{{ $field }}"
                           class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3.5 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                    @error($field)<p class="mt-1.5 text-[12.5px] text-red-300">{{ $message }}</p>@enderror
                </div>
            @endforeach

            <div>
                <label for="address" class="text-[13px] text-kraft/75">{{ __('Alamat lengkap') }}</label>
                <textarea id="address" rows="4" wire:model="address" autocomplete="street-address"
                          placeholder="{{ __('Contoh: Jl. Dago No. 25, Coblong, Kota Bandung 40135') }}"
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
                <div class="border-t border-dashed border-kraft/30 bg-[#101010] px-6 pt-5">
                    <label for="voucher" class="text-[12.5px] text-kraft/70">{{ __('Punya kode voucher?') }}</label>
                    @if ($voucher)
                        <div class="mt-2 flex items-center justify-between rounded border border-bluelab/50 bg-bluelab/10 px-3 py-2.5">
                            <span class="krm-readout text-[13px]">{{ $voucher }}</span>
                            <button type="button" wire:click="removeVoucher" class="text-[12.5px] text-kraft/70 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Lepas') }}</button>
                        </div>
                    @else
                        <form wire:submit="applyVoucher" class="mt-2 flex gap-2">
                            <input id="voucher" wire:model="voucherInput" autocomplete="off" autocapitalize="characters" placeholder="{{ __('Contoh: KOPIBARU10') }}"
                                   class="krm-readout min-w-0 flex-1 rounded border border-kraft/25 bg-transparent px-3 py-2.5 text-[13.5px] uppercase text-labwhite placeholder:normal-case placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                            <button type="submit" wire:loading.attr="disabled" class="rounded border border-kraft/30 px-4 text-[13px] transition-colors hover:border-kraft/70">{{ __('Pakai') }}</button>
                        </form>
                    @endif
                    @if ($voucherError)<p class="mt-1.5 text-[12.5px] text-red-300">{{ $voucherError }}</p>@endif
                </div>
                <div class="space-y-2.5 bg-[#101010] p-6 pt-5 text-[13.5px]">
                    <div class="flex justify-between"><span class="text-kraft/65">{{ __('Kopi') }}</span><span class="krm-readout">Rp{{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-kraft/65">{{ __('Ongkir') }}</span>
                        <span class="krm-readout">{{ $this->shipping ? 'Rp'.number_format($this->shipping, 0, ',', '.') : __('Gratis') }}</span></div>
                    @if ($this->discount)
                        <div class="flex justify-between"><span class="text-kraft/65">{{ __('Diskon') }} <span class="krm-readout text-[#9DBBEF]">{{ $voucher }}</span></span>
                            <span class="krm-readout text-[#9DBBEF]">- Rp{{ number_format($this->discount, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex items-end justify-between border-t border-dashed border-kraft/30 pt-4">
                        <span class="text-kraft/65">{{ __('Total') }}</span>
                        <span class="krm-readout text-[1.7rem] leading-none">Rp{{ number_format($this->subtotal + $this->shipping - $this->discount, 0, ',', '.') }}</span>
                    </div>
                    <button type="button" wire:click="submit" wire:loading.attr="disabled"
                            class="mt-4 hidden w-full rounded bg-bluelab py-4 font-display text-[15px] font-semibold transition-all hover:bg-[#234E94] disabled:opacity-60 lg:block">
                        {{ __('Buat pesanan') }}
                    </button>
                    <p class="pt-1 text-center text-[12px] leading-relaxed text-kraft/50">
                        @if (\App\Models\Setting::int('free_shipping_from') > 0){{ __('Gratis ongkir mulai Rp:n.', ['n' => number_format(\App\Models\Setting::int('free_shipping_from'), 0, ',', '.')]) }} @endif{{ __('Pembayaran disimulasikan di halaman berikutnya.') }}
                    </p>
                </div>
            </div>
        </aside>
    </div>
</div>
