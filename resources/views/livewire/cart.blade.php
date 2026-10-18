<div class="mx-auto max-w-[1080px] px-6 pb-32 pt-12 md:pt-16 lg:pb-24">
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
                        <li wire:key="line-{{ $l->id }}" class="flex items-start gap-4 p-4 sm:gap-5 sm:p-5">
                            @if ($l->kind === 'product')
                                <x-product-thumb :product="$l->product" class="h-[84px] w-[68px]" />
                            @else
                                <x-coffee-bag :coffee="$l->profile->coffee" :level="$l->profile->level" class="h-[84px] w-[68px] shrink-0" />
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        @if ($l->kind === 'product')
                                            <p class="font-display text-[1.05rem] font-semibold leading-snug">{{ $l->product->localizedName() }}</p>
                                            <p class="mt-0.5 text-[13px] text-kraft/70">{{ $l->product->categoryLabel() }}@if ($l->option) · {{ $l->option }}@endif</p>
                                        @else
                                            <p class="font-display text-[1.05rem] font-semibold leading-snug">{{ $l->blend ? \App\Support\Blend::name($l->profile, $l->blend, $l->ratio) : $l->profile->coffee->name }}</p>
                                            <p class="mt-0.5 text-[13px] text-kraft/70">{{ $l->blend ? __('Blend').' · ' : '' }}{{ $l->profile->level_label }}</p>
                                        @endif
                                    </div>
                                    <span class="krm-readout shrink-0 text-[14px]">Rp{{ number_format($l->price, 0, ',', '.') }}</span>
                                </div>
                                @if ($l->kind === 'coffee')
                                @php($sel = 'rounded border border-kraft/25 bg-surface py-1.5 pl-2.5 pr-7 text-[13px] text-labwhite focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40')
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <select aria-label="{{ __('Berat') }}" wire:change="setGrams('{{ $l->id }}', $event.target.value)" class="{{ $sel }}">
                                        @foreach (\App\Support\Cart::GRAMS as $g)<option value="{{ $g }}" @selected($g === $l->grams)>{{ number_format($g) }} g</option>@endforeach
                                    </select>
                                    <select aria-label="{{ __('Gilingan') }}" wire:change="setGrind('{{ $l->id }}', $event.target.value)" class="{{ $sel }}">
                                        @foreach (\App\Models\OrderItem::GRINDS as $k => $label)<option value="{{ $k }}" @selected($k === $l->grind)>{{ __($label) }}</option>@endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <div class="inline-flex items-center rounded border border-kraft/25" role="group" aria-label="{{ __('Jumlah') }}">
                                        <button type="button" wire:click="step('{{ $l->id }}', -1)" @disabled($l->qty <= 1) aria-label="{{ __('Kurangi') }}" class="grid h-8 w-8 place-items-center text-[16px] text-kraft/80 transition-colors hover:text-labwhite disabled:opacity-30">&minus;</button>
                                        <span class="krm-readout w-7 text-center text-[13px]" aria-live="polite">{{ $l->qty }}</span>
                                        <button type="button" wire:click="step('{{ $l->id }}', 1)" @disabled($l->qty >= \App\Support\Cart::MAX_QTY) aria-label="{{ __('Tambah') }}" class="grid h-8 w-8 place-items-center text-[16px] text-kraft/80 transition-colors hover:text-labwhite disabled:opacity-30">+</button>
                                    </div>
                                    <button type="button" wire:click="remove('{{ $l->id }}')" class="text-[12.5px] text-kraft/55 underline-offset-4 transition-colors hover:text-redtext hover:underline">{{ __('Hapus') }}</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('builder') }}" wire:navigate class="mt-5 inline-block text-[13.5px] text-kraft/75 underline-offset-4 transition-colors hover:text-labwhite hover:underline">+ {{ __('Tambah kopi lain') }}</a>
                <a href="{{ route('gear') }}" wire:navigate class="mt-5 ml-5 inline-block text-[13.5px] text-kraft/75 underline-offset-4 transition-colors hover:text-labwhite hover:underline">+ {{ __('Lihat perlengkapan') }}</a>
            </div>

            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="space-y-2.5 rounded border border-kraft/25 bg-surface p-6 text-[13.5px]">
                    <p class="krm-readout text-[11px] tracking-[0.16em] text-kraft/55">{{ __('RINGKASAN') }}</p>
                    <div class="flex justify-between pt-3"><span class="text-kraft/65">{{ __('Barang') }} ({{ $this->lines->sum('qty') }})</span><span class="krm-readout">Rp{{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-kraft/65">{{ __('Ongkir') }}</span>
                        <span class="krm-readout">{{ $this->shipping ? 'Rp'.number_format($this->shipping, 0, ',', '.') : __('Gratis') }}</span></div>
                    <div class="flex items-end justify-between border-t border-dashed border-kraft/30 pt-4">
                        <span class="text-kraft/65">{{ __('Total') }}</span>
                        <span class="krm-readout text-[1.6rem] leading-none">Rp{{ number_format($this->subtotal + $this->shipping, 0, ',', '.') }}</span>
                    </div>
                    @if ($this->problem)
                        <p class="rounded border border-redtext/40 bg-redtext/10 px-3 py-2.5 text-[13px] text-redtext">{{ $this->problem }}</p>
                        <span class="mt-3 hidden w-full cursor-not-allowed rounded bg-bluelab py-4 text-center font-display text-[15px] font-semibold text-paper opacity-50 lg:block" aria-disabled="true">{{ __('Checkout') }}</span>
                    @else
                        <a href="{{ route('checkout') }}" wire:navigate class="mt-3 hidden w-full rounded bg-bluelab py-4 text-center font-display text-[15px] font-semibold text-paper transition-all hover:bg-[#234E94] lg:block">{{ __('Checkout') }}</a>
                    @endif
                    <p class="pt-1 text-center text-[12px] leading-relaxed text-kraft/50">{{ __('Voucher dipakai di langkah checkout.') }}</p>
                </div>
            </aside>
        </div>

        {{-- Mobile: total dan tombol checkout menempel di bawah layar. Di desktop tombolnya ada di ringkasan. --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-labwhite/15 bg-surface/95 px-4 py-3 backdrop-blur lg:hidden">
            <div class="mx-auto flex max-w-[1080px] items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[12px] text-kraft/60">{{ __('Total') }} · {{ $this->lines->sum('qty') }} {{ __('bungkus') }}</p>
                    <p class="krm-readout text-[1.15rem] leading-tight">Rp{{ number_format($this->subtotal + $this->shipping, 0, ',', '.') }}</p>
                </div>
                @if ($this->problem)
                    <span class="shrink-0 cursor-not-allowed rounded bg-bluelab px-6 py-3 font-display text-[14px] font-semibold text-paper opacity-50" aria-disabled="true">{{ __('Checkout') }}</span>
                @else
                    <a href="{{ route('checkout') }}" wire:navigate class="shrink-0 rounded bg-bluelab px-6 py-3 font-display text-[14px] font-semibold text-paper">{{ __('Checkout') }}</a>
                @endif
            </div>
        </div>
    @endif
</div>
