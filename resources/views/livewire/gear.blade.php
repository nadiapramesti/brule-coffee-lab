<div class="mx-auto max-w-[1180px] px-6 pb-24 pt-12 md:pt-16">
    <p class="krm-readout text-[11px] tracking-[0.18em] text-kraft/55">{{ __('PERLENGKAPAN') }}</p>
    <h1 class="krm-h2 mt-2 font-bold" data-reveal>{{ __('Alat dan barang pendamping seduhmu') }}</h1>
    <p class="mt-3 max-w-[58ch] text-[14.5px] text-kraft/75">{{ __('Dipilih seperlunya, yang kami pakai sendiri di lab. Barang non-kopi langsung dikemas tanpa antre sangrai, jadi dikirim lebih cepat.') }}</p>


    {{-- Kategori: satu baris yang bisa digeser di HP, membungkus di layar lebar --}}
    <div class="-mx-6 mt-8 overflow-x-auto px-6 [scrollbar-width:none] sm:mx-0 sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden">
        <div class="flex w-max gap-2 sm:w-auto sm:flex-wrap" role="tablist" aria-label="{{ __('Kategori') }}">
            @php $total = array_sum($this->categories); @endphp
            @foreach (['' => __('Semua')] + collect(\App\Models\Product::CATEGORIES)->map(fn ($l) => __($l))->all() as $key => $label)
                @continue($key !== '' && empty($this->categories[$key]))
                <button type="button" role="tab" aria-selected="{{ $category === $key ? 'true' : 'false' }}" wire:click="setCategory('{{ $key }}')"
                        class="whitespace-nowrap rounded-full border px-4 py-1.5 text-[13px] transition-colors {{ $category === $key ? 'border-bluelab bg-bluelab/20 text-labwhite' : 'border-kraft/25 text-kraft/70 hover:border-kraft/50 hover:text-labwhite' }}">
                    {{ $label }}<span class="krm-readout ml-1.5 text-[11px] text-kraft/50">{{ $key === '' ? $total : $this->categories[$key] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($error)
        <p role="alert" class="mt-5 rounded border border-redtext/40 bg-redtext/10 px-4 py-2.5 text-[13px] text-redtext">{{ $error }}</p>
    @endif

    @if ($this->products->isEmpty())
        <p class="mt-12 text-[14px] text-kraft/60">{{ __('Belum ada perlengkapan di kategori ini.') }}</p>
    @else
        <ul class="mt-6 grid grid-cols-2 gap-3 sm:mt-8 sm:gap-5 lg:grid-cols-3">
            @foreach ($this->products as $p)
                @php $state = $p->stockState(); @endphp
                <li wire:key="p-{{ $p->id }}" class="flex flex-col overflow-hidden rounded border border-kraft/20 bg-surface transition-shadow duration-300 hover:shadow-[0_18px_40px_-22px_rgb(var(--shadow)/.7)]">
                    <div class="relative">
                        <button type="button" wire:click="openDetail('{{ $p->slug }}')" aria-label="{{ __('Lihat detail :n', ['n' => $p->localizedName()]) }}"
                                class="block w-full overflow-hidden text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-bluelab/60 [&_img]:transition-transform [&_img]:duration-500 hover:[&_img]:scale-[1.04]">
                            <x-product-thumb :product="$p" :option="$choice[$p->id] ?? null" class="aspect-[4/3] w-full rounded-none border-0 border-b border-kraft/15 {{ $state === 'soldout' ? '[&_img]:grayscale [&_img]:opacity-60' : '' }}" />
                        </button>
                        @if ($state !== 'ok')
                            <span class="krm-readout pointer-events-none absolute left-2 top-2 rounded-[6px] px-2 py-0.5 text-[10.5px] sm:left-3 sm:top-3 {{ $state === 'low' ? 'bg-[#E8782A] text-white' : 'bg-black/75 text-white' }}">
                                {{ $state === 'soldout' ? __('Stok habis') : __('Sisa :n', ['n' => $p->stock]) }}
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-3 sm:p-5">
                        <p class="krm-readout text-[9.5px] tracking-[0.14em] text-kraft/50 sm:text-[10.5px]">{{ mb_strtoupper($p->categoryLabel()) }}</p>
                        <h2 class="mt-1 font-display text-[0.95rem] font-semibold leading-snug sm:text-[1.1rem]"><button type="button" wire:click="openDetail('{{ $p->slug }}')" class="text-left underline-offset-4 hover:underline">{{ $p->localizedName() }}</button></h2>
                        <p class="mt-1.5 line-clamp-2 text-[12.5px] leading-relaxed text-kraft/75 sm:mt-2 sm:line-clamp-3 sm:text-[13.5px]">{{ $p->localizedDescription() }}</p>

                        @if ($p->hasOptions())
                            <div class="mt-3 sm:mt-4" role="radiogroup" aria-label="{{ $p->option_label ?: __('Varian') }}">
                                <p class="text-[11.5px] text-kraft/55 sm:text-[12px]">{{ $p->option_label ?: __('Varian') }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($p->optionList() as $opt)
                                        <label class="cursor-pointer">
                                            <input type="radio" class="peer sr-only" name="opt-{{ $p->id }}" value="{{ $opt }}" wire:model.live="choice.{{ $p->id }}">
                                            <span class="block rounded border border-kraft/25 px-2.5 py-1 text-[12px] text-kraft/75 transition-colors peer-checked:border-bluelab peer-checked:bg-bluelab/20 peer-checked:text-labwhite peer-focus-visible:ring-2 peer-focus-visible:ring-bluelab/50 sm:px-3 sm:text-[12.5px]">{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-auto flex flex-col gap-2.5 pt-4 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:pt-5">
                            <p class="krm-readout text-[1rem] leading-none sm:text-[1.15rem]">Rp{{ number_format($p->price, 0, ',', '.') }}</p>
                            <button type="button" wire:click="add({{ $p->id }})" @disabled($state === 'soldout')
                                    class="rounded bg-bluelab px-4 py-2.5 font-display text-[13.5px] font-semibold text-paper transition-colors hover:bg-[#234E94] disabled:cursor-not-allowed disabled:opacity-40">
                                @if ($addedId === $p->id)<span class="sm:hidden">✓</span><span class="hidden sm:inline">{{ __('Masuk keranjang ✓') }}</span>@else{{ __('Tambah') }}@endif
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif


    @if ($d = $this->detailProduct)
        @php $ds = $d->stockState(); @endphp
        <div class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
             x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             @keydown.escape.window="$wire.closeDetail()">
            <div class="krm-fade absolute inset-0 bg-black/65" wire:click="closeDetail"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="gear-title" x-trap="true"
                 class="krm-pop relative flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-xl border border-kraft/25 bg-surface text-labwhite shadow-2xl sm:max-w-[760px] sm:rounded-lg sm:flex-row">
                <x-product-thumb :product="$d" :option="$choice[$d->id] ?? null" class="aspect-[4/3] w-full shrink-0 rounded-none border-0 sm:aspect-auto sm:w-[46%]" />
                <div class="flex min-h-0 flex-1 flex-col">
                    <div class="flex items-start justify-between gap-3 px-5 pt-5">
                        <div class="min-w-0">
                            <p class="krm-readout text-[10.5px] tracking-[0.14em] text-kraft/50">{{ mb_strtoupper($d->categoryLabel()) }}</p>
                            <h3 id="gear-title" class="mt-1 font-display text-[1.3rem] font-semibold leading-tight">{{ $d->localizedName() }}</h3>
                        </div>
                        <button type="button" wire:click="closeDetail" aria-label="{{ __('Tutup') }}" class="-mr-1 grid h-9 w-9 shrink-0 place-items-center rounded text-kraft/70 transition-colors hover:bg-labwhite/10 hover:text-labwhite">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15"/></svg>
                        </button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4">
                        <p class="text-[14px] leading-relaxed text-kraft/80">{{ $d->localizedDescription() }}</p>
                        <p class="mt-3 text-[12.5px] text-kraft/55">{{ __('Dikemas tanpa antre sangrai, jadi berangkat lebih cepat dari kopi.') }}</p>
                        @if ($d->hasOptions())
                            <div class="mt-4" role="radiogroup" aria-label="{{ $d->option_label ?: __('Varian') }}">
                                <p class="text-[12px] text-kraft/55">{{ $d->option_label ?: __('Varian') }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($d->optionList() as $opt)
                                        <label class="cursor-pointer">
                                            <input type="radio" class="peer sr-only" name="dopt-{{ $d->id }}" value="{{ $opt }}" wire:model.live="choice.{{ $d->id }}">
                                            <span class="block rounded border border-kraft/25 px-3 py-1 text-[12.5px] text-kraft/75 transition-colors peer-checked:border-bluelab peer-checked:bg-bluelab/20 peer-checked:text-labwhite peer-focus-visible:ring-2 peer-focus-visible:ring-bluelab/50">{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if ($error)<p role="alert" class="mt-4 text-[12.5px] text-redtext">{{ $error }}</p>@endif
                    </div>
                    <div class="flex items-end justify-between gap-3 border-t border-kraft/15 px-5 py-4">
                        <div>
                            <p class="krm-readout text-[1.3rem] leading-none">Rp{{ number_format($d->price, 0, ',', '.') }}</p>
                            <p class="mt-1.5 text-[12px] {{ $ds === 'ok' ? 'text-kraft/50' : ($ds === 'low' ? 'text-ambertext' : 'text-redtext') }}">{{ $ds === 'soldout' ? __('Stok habis') : ($ds === 'low' ? __('Sisa :n', ['n' => $d->stock]) : __('Tersedia')) }}</p>
                        </div>
                        <button type="button" wire:click="add({{ $d->id }})" @disabled($ds === 'soldout')
                                class="rounded bg-bluelab px-5 py-3 font-display text-[14px] font-semibold text-paper transition-colors hover:bg-[#234E94] disabled:cursor-not-allowed disabled:opacity-40">
                            {{ $addedId === $d->id ? __('Masuk keranjang ✓') : __('Tambah ke keranjang') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <p class="mt-10 text-[13px] text-kraft/55">
        <a href="{{ route('cart') }}" wire:navigate class="underline underline-offset-4 hover:text-labwhite">{{ __('Lihat keranjang') }}</a>
    </p>
</div>
