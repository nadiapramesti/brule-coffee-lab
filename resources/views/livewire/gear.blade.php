<div class="mx-auto max-w-[1180px] px-6 pb-24 pt-12 md:pt-16">
    <p class="krm-readout text-[11px] tracking-[0.18em] text-kraft/55">{{ __('PERLENGKAPAN') }}</p>
    <h1 class="krm-h2 mt-2 font-bold" data-reveal>{{ __('Alat dan barang pendamping seduhmu') }}</h1>
    <p class="mt-3 max-w-[58ch] text-[14.5px] text-kraft/75">{{ __('Dipilih seperlunya, yang kami pakai sendiri di lab. Barang non-kopi langsung dikemas tanpa antre sangrai, jadi dikirim lebih cepat.') }}</p>

    <div class="mt-8 flex flex-wrap gap-2" role="tablist" aria-label="{{ __('Kategori') }}">
        @php $total = array_sum($this->categories); @endphp
        @foreach (['' => __('Semua')] + collect(\App\Models\Product::CATEGORIES)->map(fn ($l) => __($l))->all() as $key => $label)
            @continue($key !== '' && empty($this->categories[$key]))
            <button type="button" role="tab" aria-selected="{{ $category === $key ? 'true' : 'false' }}" wire:click="setCategory('{{ $key }}')"
                    class="rounded-full border px-4 py-1.5 text-[13px] transition-colors {{ $category === $key ? 'border-bluelab bg-bluelab/20 text-labwhite' : 'border-kraft/25 text-kraft/70 hover:border-kraft/50 hover:text-labwhite' }}">
                {{ $label }}<span class="krm-readout ml-1.5 text-[11px] text-kraft/50">{{ $key === '' ? $total : $this->categories[$key] }}</span>
            </button>
        @endforeach
    </div>

    @if ($error)
        <p role="alert" class="mt-5 rounded border border-redtext/40 bg-redtext/10 px-4 py-2.5 text-[13px] text-redtext">{{ $error }}</p>
    @endif

    @if ($this->products->isEmpty())
        <p class="mt-12 text-[14px] text-kraft/60">{{ __('Belum ada perlengkapan di kategori ini.') }}</p>
    @else
        <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->products as $p)
                @php $state = $p->stockState(); @endphp
                <li wire:key="p-{{ $p->id }}" class="flex flex-col overflow-hidden rounded border border-kraft/20 bg-surface">
                    <x-product-thumb :product="$p" class="aspect-[4/3] w-full rounded-none border-0 border-b border-kraft/15" />
                    <div class="flex flex-1 flex-col p-5">
                        <p class="krm-readout text-[10.5px] tracking-[0.14em] text-kraft/50">{{ mb_strtoupper($p->categoryLabel()) }}</p>
                        <h2 class="mt-1 font-display text-[1.1rem] font-semibold leading-snug">{{ $p->localizedName() }}</h2>
                        <p class="mt-2 text-[13.5px] leading-relaxed text-kraft/75">{{ $p->localizedDescription() }}</p>

                        @if ($p->hasOptions())
                            <div class="mt-4" role="radiogroup" aria-label="{{ $p->option_label ?: __('Varian') }}">
                                <p class="text-[12px] text-kraft/55">{{ $p->option_label ?: __('Varian') }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($p->optionList() as $opt)
                                        <label class="cursor-pointer">
                                            <input type="radio" class="peer sr-only" name="opt-{{ $p->id }}" value="{{ $opt }}" wire:model="choice.{{ $p->id }}">
                                            <span class="block rounded border border-kraft/25 px-3 py-1 text-[12.5px] text-kraft/75 transition-colors peer-checked:border-bluelab peer-checked:bg-bluelab/20 peer-checked:text-labwhite peer-focus-visible:ring-2 peer-focus-visible:ring-bluelab/50">{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-auto flex items-end justify-between gap-3 pt-5">
                            <div>
                                <p class="krm-readout text-[1.15rem] leading-none">Rp{{ number_format($p->price, 0, ',', '.') }}</p>
                                <p class="mt-1.5 text-[12px] {{ $state === 'ok' ? 'text-kraft/50' : ($state === 'low' ? 'text-ambertext' : 'text-redtext') }}">
                                    {{ $state === 'soldout' ? __('Stok habis') : ($state === 'low' ? __('Sisa :n', ['n' => $p->stock]) : __('Tersedia')) }}
                                </p>
                            </div>
                            <button type="button" wire:click="add({{ $p->id }})" @disabled($state === 'soldout')
                                    class="rounded bg-bluelab px-4 py-2.5 font-display text-[13.5px] font-semibold text-paper transition-colors hover:bg-[#234E94] disabled:cursor-not-allowed disabled:opacity-40">
                                {{ $addedId === $p->id ? __('Masuk keranjang ✓') : __('Tambah') }}
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <p class="mt-10 text-[13px] text-kraft/55">
        <a href="{{ route('cart') }}" wire:navigate class="underline underline-offset-4 hover:text-labwhite">{{ __('Lihat keranjang') }}</a>
    </p>
</div>
