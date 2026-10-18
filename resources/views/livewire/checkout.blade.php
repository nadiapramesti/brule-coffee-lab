<div class="mx-auto max-w-[1080px] px-6 pb-20 pt-12 md:pt-16">
    <a href="{{ $direct ? route('builder') : route('cart') }}" wire:navigate class="krm-readout text-[12px] text-kraft/60 transition-colors hover:text-labwhite">&larr; {{ $direct ? __('Kembali ke builder') : __('Kembali ke keranjang') }}</a>
    <h1 class="krm-h2 mt-4 font-bold" data-reveal>Checkout</h1>
    <p class="mt-3 max-w-[52ch] text-[14.5px] text-kraft/75">{{ __('Satu langkah lagi. Kamu mengisi alamat, kami menimbang biji mentahnya.') }}</p>

    <div class="mt-10 grid grid-cols-[minmax(0,1fr)] gap-10 lg:grid-cols-[minmax(0,1fr)_380px]">

        <form wire:submit="submit" class="space-y-6" novalidate>
            @if ($error)
                <p class="rounded border border-redtext/40 bg-redtext/10 px-4 py-3 text-[13.5px] text-redtext">{{ $error }}</p>
            @endif

            @foreach ([
                ['name', __('Nama penerima'), 'text', 'name', 'Budi Santoso'],
                ['phone', __('Nomor WhatsApp'), 'tel', 'tel', '081234567890'],
            ] as [$field, $label, $type, $ac, $ph])
                <div>
                    <label for="{{ $field }}" class="text-[13px] text-kraft/75">{{ $label }}</label>
                    <input id="{{ $field }}" type="{{ $type }}" autocomplete="{{ $ac }}" placeholder="{{ $ph }}" wire:model="{{ $field }}"
                           class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3.5 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                    @error($field)<p class="mt-1.5 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                </div>
            @endforeach

            @if (auth()->user()->addresses()->count())
                <div>
                    <label for="saved" class="text-[13px] text-kraft/75">{{ __('Alamat tersimpan') }}</label>
                    <select id="saved" wire:change="useAddress($event.target.value)"
                            class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3.5 text-[15px] text-labwhite focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                        <option value="" class="text-charcoal">{{ __('Isi manual') }}</option>
                        @foreach (auth()->user()->addresses as $a)
                            <option value="{{ $a->id }}" @selected($savedId === $a->id) class="text-charcoal">{{ $a->label }} — {{ \Illuminate\Support\Str::limit($a->address, 40) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="address" class="text-[13px] text-kraft/75">{{ __('Alamat lengkap') }}</label>
                <textarea id="address" rows="4" wire:model="address" autocomplete="street-address"
                          placeholder="{{ __('Contoh: Jl. Dago No. 25, Coblong, Kota Bandung 40135') }}"
                          class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3.5 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40"></textarea>
                @error('address')<p class="mt-1.5 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
            </div>

            @if (! $savedId)
                <label class="flex items-center gap-3 text-[13.5px] text-kraft/80">
                    <input type="checkbox" wire:model="saveAddress" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                    {{ __('Simpan alamat ini untuk pesanan berikutnya') }}
                </label>
            @endif

            @if (\App\Models\Order::addonsEnabled())
                @php
                    $addonPrice = fn (int $n) => $n > 0 ? '+ Rp'.number_format($n, 0, ',', '.') : __('Gratis');
                @endphp
                <fieldset>
                    <legend class="text-[13px] text-kraft/75">{{ __('Tambahan (opsional)') }}</legend>
                    <div class="mt-2 space-y-3">
                        <label class="flex cursor-pointer gap-3 rounded border p-4 transition-colors {{ $giftWrap ? 'border-bluelab bg-bluelab/10' : 'border-kraft/25 hover:border-kraft/50' }}">
                            <input type="checkbox" wire:model.live="giftWrap" class="mt-1 h-4 w-4 shrink-0 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-3">
                                    <span class="text-[14.5px] font-semibold">{{ __('Kemasan hadiah') }}</span>
                                    <span class="krm-readout shrink-0 text-[13px] text-kraft/75">{{ $addonPrice(\App\Models\Order::giftWrapPrice()) }}</span>
                                </span>
                                <span class="mt-0.5 block text-[12.5px] leading-relaxed text-kraft/65">{{ __('Bungkus kopi dimasukkan ke kotak kraft dan diikat pita, siap diberikan.') }}</span>
                            </span>
                        </label>

                        <div class="rounded border p-4 transition-colors {{ trim($cardMessage) !== '' ? 'border-bluelab bg-bluelab/10' : 'border-kraft/25' }}">
                            <div class="flex items-baseline justify-between gap-3">
                                <label for="card" class="text-[14.5px] font-semibold">{{ __('Kartu ucapan') }}</label>
                                <span class="krm-readout shrink-0 text-[13px] text-kraft/75">{{ $addonPrice(\App\Models\Order::cardPrice()) }}</span>
                            </div>
                            <p class="mt-0.5 text-[12.5px] leading-relaxed text-kraft/65">{{ __('Pesanmu ditulis tangan dan diselipkan di paket. Kosongkan kalau tidak perlu kartu.') }}</p>
                            <textarea id="card" rows="2" maxlength="{{ \App\Models\Order::CARD_MAX }}" wire:model.live.debounce.400ms="cardMessage"
                                      placeholder="{{ __('Contoh: Selamat ulang tahun, Rani! Semoga harimu semanis kopi ini.') }}"
                                      class="mt-3 w-full rounded border border-kraft/25 bg-transparent px-4 py-3 text-[14.5px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40"></textarea>
                            <p class="krm-readout mt-1 text-right text-[11.5px] text-kraft/50">{{ mb_strlen($cardMessage) }}/{{ \App\Models\Order::CARD_MAX }}</p>
                            @error('cardMessage')<p class="mt-1 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </fieldset>
            @endif

            <fieldset>
                <legend class="text-[13px] text-kraft/75">{{ __('Metode pembayaran') }}</legend>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        'online' => [__('Bayar online'), __('Bayar sekarang, pesanan langsung masuk antrian setelah lunas.')],
                        'cod' => [__('COD (bayar di tempat)'), $this->codProblem ?? __('Bayar tunai ke kurir saat paket tiba. Pesanan langsung diproses.')],
                    ] as $key => [$title, $hint])
                        @continue($key === 'cod' && ! \App\Models\Setting::int('cod_enabled'))
                        @php($off = $key === 'cod' && $this->codProblem)
                        <label class="flex gap-3 rounded border p-4 transition-colors {{ $off ? 'cursor-not-allowed opacity-50' : 'cursor-pointer has-[:checked]:border-bluelab has-[:checked]:bg-bluelab/10' }} {{ $payment === $key ? 'border-bluelab bg-bluelab/10' : 'border-kraft/25 '.($off ? '' : 'hover:border-kraft/50') }}">
                            <input type="radio" name="payment" value="{{ $key }}" wire:model.live="payment" @disabled($off) class="mt-1 h-4 w-4 shrink-0 border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                            <span class="min-w-0">
                                <span class="block text-[14.5px] font-semibold">{{ $title }}</span>
                                <span class="mt-0.5 block text-[12.5px] leading-relaxed text-kraft/65">{{ $hint }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('payment')<p class="mt-1.5 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
            </fieldset>

            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded bg-bluelab py-4 font-display text-[15px] font-semibold transition-all hover:bg-[#234E94] disabled:opacity-60 lg:hidden">
                {{ __('Buat pesanan') }}
            </button>
        </form>

        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="overflow-hidden rounded border border-kraft/25 bg-surface">
                <div class="p-6">
                    <p class="krm-readout text-[11px] tracking-[0.16em] text-kraft/55">{{ __('RINGKASAN PESANAN') }}</p>
                    <ul class="mt-4 space-y-4">
                        @foreach ($this->lines as $l)
                            <li class="flex items-center gap-4">
                                @if ($l->kind === 'product')
                                    <x-product-thumb :product="$l->product" class="h-[64px] w-[52px]" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-display text-[1rem] font-semibold leading-snug">{{ $l->product->localizedName() }}</p>
                                        <p class="mt-0.5 text-[12.5px] text-kraft/70">{{ $l->option ?: $l->product->categoryLabel() }}{{ $l->qty > 1 ? ' × '.$l->qty : '' }}</p>
                                    </div>
                                @else
                                    <x-coffee-bag :coffee="$l->profile->coffee" :level="$l->profile->level" class="h-[64px] w-[52px] shrink-0" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-display text-[1rem] font-semibold leading-snug">{{ $l->blend ? \App\Support\Blend::name($l->profile, $l->blend, $l->ratio) : $l->profile->coffee->name }}</p>
                                        <p class="mt-0.5 text-[12.5px] text-kraft/70">{{ $l->blend ? __('Blend').' · ' : '' }}{{ $l->profile->level_label }} · {{ __(\App\Models\OrderItem::GRINDS[$l->grind]) }} · {{ number_format($l->grams) }} g{{ $l->qty > 1 ? ' × '.$l->qty : '' }}</p>
                                    </div>
                                @endif
                                <span class="krm-readout shrink-0 text-[13px]">Rp{{ number_format($l->price, 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if (! $direct)
                        <a href="{{ route('cart') }}" wire:navigate class="mt-4 inline-block text-[12.5px] text-kraft/60 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Ubah isi keranjang') }}</a>
                    @endif
                </div>
                <div class="border-t border-dashed border-kraft/30 bg-surface px-6 pt-5">
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
                    @if ($voucherError)<p class="mt-1.5 text-[12.5px] text-redtext">{{ $voucherError }}</p>@endif
                </div>
                <div class="space-y-2.5 bg-surface p-6 pt-5 text-[13.5px]">
                    <div class="flex justify-between"><span class="text-kraft/65">{{ __('Kopi') }}</span><span class="krm-readout">Rp{{ number_format($this->subtotal, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-kraft/65">{{ __('Ongkir') }}</span>
                        <span class="krm-readout">{{ $this->shipping ? 'Rp'.number_format($this->shipping, 0, ',', '.') : __('Gratis') }}</span></div>
                    @if ($this->addon)
                        <div class="flex justify-between"><span class="text-kraft/65">{{ __('Tambahan') }}</span>
                            <span class="krm-readout">Rp{{ number_format($this->addon, 0, ',', '.') }}</span></div>
                    @endif
                    @if ($this->discount)
                        <div class="flex justify-between"><span class="text-kraft/65">{{ __('Diskon') }} <span class="krm-readout text-bluetext">{{ $voucher }}</span></span>
                            <span class="krm-readout text-bluetext">- Rp{{ number_format($this->discount, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex items-end justify-between border-t border-dashed border-kraft/30 pt-4">
                        <span class="text-kraft/65">{{ __('Total') }}</span>
                        <span class="krm-readout text-[1.7rem] leading-none">Rp{{ number_format($this->total, 0, ',', '.') }}</span>
                    </div>
                    <button type="button" wire:click="submit" wire:loading.attr="disabled"
                            class="mt-4 hidden w-full rounded bg-bluelab py-4 font-display text-[15px] font-semibold transition-all hover:bg-[#234E94] disabled:opacity-60 lg:block">
                        {{ __('Buat pesanan') }}
                    </button>
                    <p class="text-center text-[12px] leading-relaxed text-kraft/50">{{ __('Dengan membuat pesanan, kamu menyetujui') }} <a href="{{ route('policy.terms') }}" target="_blank" rel="noopener" class="underline underline-offset-4 hover:text-labwhite">{{ __('Syarat & Ketentuan') }}</a>.</p>
                    <p class="pt-1 text-center text-[12px] leading-relaxed text-kraft/50">
                        @if (\App\Models\Setting::int('free_shipping_from') > 0){{ __('Gratis ongkir mulai Rp:n.', ['n' => number_format(\App\Models\Setting::int('free_shipping_from'), 0, ',', '.')]) }} @endif{{ $payment === 'cod' ? __('COD: siapkan uang tunai sebesar total saat paket tiba.') : (\App\Support\Midtrans::enabled() ? __('Pembayaran lewat Midtrans di halaman berikutnya.') : __('Pembayaran disimulasikan di halaman berikutnya.')) }}
                    </p>
                </div>
            </div>
        </aside>
    </div>
</div>
