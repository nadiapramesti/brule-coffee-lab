<div class="mx-auto max-w-[1080px] px-6 pb-24 pt-12 md:pt-16">
    <h1 class="krm-h2 font-bold" data-reveal>{{ __('Keranjang') }}</h1>

    @if ($this->lines->isEmpty())
        <div class="mt-10 max-w-[46ch]">
            <p class="text-[15px] text-kraft/75">{{ __('Keranjangmu masih kosong. Racik dulu kopinya, nanti sangrai kami mulai setelah pesananmu masuk.') }}</p>
            <a href="{{ route('builder') }}" wire:navigate class="mt-6 inline-block rounded bg-bluelab px-6 py-3.5 font-display text-[15px] font-semibold text-paper transition-colors hover:bg-[#234E94]">{{ __('Racik kopimu') }}</a>
        </div>
    @else
        <p class="mt-3 max-w-[52ch] text-[14.5px] text-kraft/75">{{ __('Tiap item disangrai sesuai pesananmu. Semua dikirim dalam satu paket.') }}</p>

        <div class="mt-10 grid gap-10 grid-cols-[minmax(0,1fr)] lg:grid-cols-[minmax(0,1fr)_360px]">
            <div>
                <ul class="divide-y divide-kraft/15 rounded border border-kraft/20">
                    @foreach ($this->lines as $l)
                        <li wire:key="line-{{ $l->id }}" class="flex items-center gap-4 p-4 sm:gap-5 sm:p-5">
                            <x-coffee-bag :coffee="$l->profile->coffee" :level="$l->profile->level" class="h-[84px] w-[68px] shrink-0" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-display text-[1.05rem] font-semibold leading-snug">{{ $l->profile->coffee->name }}</p>
                                <p class="mt-1 text-[13px] text-kraft/70">{{ $l->profile->level_label }} · {{ __(\App\Models\OrderItem::GRINDS[$l->grind]) }} · {{ number_format($l->grams) }} g</p>
                                <button type="button" wire:click="remove('{{ $l->id }}')" class="mt-2 text-[12.5px] text-kraft/55 underline-offset-4 transition-colors hover:text-redtext hover:underline">{{ __('Hapus') }}</button>
                            </div>
                            <span class="krm-readout shrink-0 text-[14px]">Rp{{ number_format($l->price, 0, ',', '.') }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('builder') }}" wire:navigate class="mt-5 inline-block text-[13.5px] text-kraft/75 underline-offset-4 transition-colors hover:text-labwhite hover:underline">+ {{ __('Tambah kopi lain') }}</a>
            </div>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="space-y-2.5 rounded border border-kraft/25 bg-surface p-6 text-[13.5px]">
                    <p class="krm-readout text-[11px] tracking-[0.16em] text-kraft/55">{{ __('RINGKASAN') }}</p>
                    <div class="flex justify-between pt-3"><span class="text-kraft/65">{{ __('Kopi') }} ({{ $this->lines->count() }})</span><span class="krm-readout">Rp{{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-kraft/65">{{ __('Ongkir') }}</span>
                        <span class="krm-readout">{{ $this->shipping ? 'Rp'.number_format($this->shipping, 0, ',', '.') : __('Gratis') }}</span></div>
                    <div class="flex items-end justify-between border-t border-dashed border-kraft/30 pt-4">
                        <span class="text-kraft/65">{{ __('Total') }}</span>
                        <span class="krm-readout text-[1.6rem] leading-none">Rp{{ number_format($this->subtotal + $this->shipping, 0, ',', '.') }}</span>
                    </div>
                    @if ($this->problem)
                        <p class="rounded border border-redtext/40 bg-redtext/10 px-3 py-2.5 text-[13px] text-redtext">{{ $this->problem }}</p>
                        <span class="mt-3 block w-full cursor-not-allowed rounded bg-bluelab py-4 text-center font-display text-[15px] font-semibold text-paper opacity-50" aria-disabled="true">{{ __('Checkout') }}</span>
                    @else
                        <a href="{{ route('checkout') }}" wire:navigate class="mt-3 block w-full rounded bg-bluelab py-4 text-center font-display text-[15px] font-semibold text-paper transition-all hover:bg-[#234E94]">{{ __('Checkout') }}</a>
                    @endif
                    <p class="pt-1 text-center text-[12px] leading-relaxed text-kraft/50">{{ __('Voucher dipakai di langkah checkout.') }}</p>
                </div>
            </aside>
        </div>
    @endif
</div>
