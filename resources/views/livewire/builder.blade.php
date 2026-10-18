<div>

    {{-- ══════════════════ HEADER ══════════════════ --}}
    <section class="krm-dark relative isolate -mt-[74px] overflow-hidden pb-14 pt-[calc(74px+3.5rem)] md:pb-16 md:pt-[calc(74px+5rem)]">
        <picture class="absolute inset-0 -z-20">
            <source media="(max-width: 767px)" srcset="{{ asset('images/hero-roastery-sm.webp') }}" type="image/webp">
            <img src="{{ asset('images/hero-roastery.webp') }}" alt="" width="1920" height="1280" decoding="async"
                 class="h-full w-full object-cover object-[70%_40%]">
        </picture>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(18,18,18,.96)_0%,rgba(18,18,18,.88)_55%,rgba(18,18,18,.6)_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-2/3 bg-gradient-to-b from-transparent to-charcoal"></div>

        <div class="mx-auto max-w-[1180px] px-6">
            <p class="krm-readout text-[12px] tracking-[0.18em] text-kraft/70" data-reveal>{{ __('PEMBUAT SANGRAI') }}</p>
            <h1 class="krm-h1 mt-3 font-bold" data-reveal style="--d:80ms">Roast builder</h1>
            <p class="mt-5 max-w-[56ch] text-[15px] leading-relaxed text-kraft" data-reveal style="--d:160ms">
                {{ __('Pilih bijinya, lalu geser level sangrainya. Profil rasa di sebelah kanan bergerak mengikuti pilihanmu, dan harganya ikut dihitung.') }}
            </p>

            {{-- Ringkasan pilihan: jadi penunjuk langkah sekaligus rekap --}}
            <ol class="mt-9 grid grid-cols-2 gap-2.5 sm:grid-cols-4" data-reveal style="--d:240ms">
                @foreach ([
                    ['01', __('Biji'), $this->coffee?->name],
                    ['02', __('Sangrai'), $this->profile?->level_label],
                    ['03', __('Gilingan'), __($this->grindOptions[$grind])],
                    ['04', __('Berat'), number_format($grams) . ' g'],
                ] as [$n, $label, $val])
                    <li class="min-w-0 rounded border border-labwhite/15 bg-charcoal/55 px-3.5 py-3 backdrop-blur-[2px]">
                        <span class="krm-readout flex items-center gap-2 text-[11px] text-kraft/60">
                            <span class="text-bluelab">{{ $n }}</span>{{ $label }}
                        </span>
                        <span class="mt-1 block truncate text-[13.5px] font-medium text-labwhite">{{ $val }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <div class="mx-auto max-w-[1180px] px-6 pb-28 pt-6 lg:pb-20">
    <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,430px)] lg:gap-14">

        {{-- ─────────── KONTROL ─────────── --}}
        <div class="min-w-0 space-y-14">

            {{-- Langkah 1 --}}
            <section>
                <h2 class="krm-h3 flex items-baseline gap-3 font-semibold">
                    <span class="krm-readout text-[12px] text-bluelab">01</span>
                    {{ __('Pilih biji') }}
                </h2>

                {{-- HP: daftar baris (foto kecil + nama + skor), semua terlihat tanpa geser.
                     Tablet ke atas: kartu. Kalau bijinya banyak, tampil sebagian dulu + tombol "semua". --}}
                @php $beanCount = $this->coffees->count(); @endphp
                <div x-data="{ all: false, max: innerWidth < 640 ? 5 : 8 }" class="mt-5">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($this->coffees as $bean)
                            @php
                                $on = $this->coffeeId === $bean->id;
                                $short = \Illuminate\Support\Str::of($bean->name)->replace([' Washed',' Natural',' Honey',' Wet-Hulled',' Anaerobic'], '');
                                $reg = \Illuminate\Support\Str::before($bean->traceability?->region ?? '', ',');
                                $ctry = \Illuminate\Support\Str::afterLast($bean->traceability?->region ?? '', ', ');
                            @endphp
                            <button type="button"
                                    wire:click="selectCoffee({{ $bean->id }})"
                                    aria-pressed="{{ $on ? 'true' : 'false' }}"
                                    @if ($loop->index >= 5) x-show="all || {{ $on ? 'true' : 'false' }} || {{ $loop->index }} < max" @endif
                                    class="group relative block w-full rounded border text-left transition-all duration-300
                                           {{ $on
                                                ? 'border-bluelab bg-bluelab/12 shadow-[0_0_0_1px_#2A5DB0,0_12px_32px_-14px_rgba(42,93,176,.7)]'
                                                : 'border-kraft/20 hover:border-kraft/50 sm:hover:-translate-y-0.5' }}">

                                {{-- Tampilan HP: satu baris --}}
                                <span class="grid grid-cols-[3.75rem_minmax(0,1fr)_auto] items-center gap-3 p-3 sm:hidden">
                                    <x-coffee-bag :coffee="$bean" class="h-[75px] w-[60px]" />
                                    <span class="min-w-0">
                                        <span class="block truncate font-display text-[15.5px] font-semibold leading-snug text-labwhite">{{ $short }}</span>
                                        <span class="mt-0.5 block truncate text-[12.5px] text-kraft/65">{{ $reg }} <span class="text-kraft/40">·</span> {{ $ctry }}</span>
                                        <span class="krm-readout mt-1.5 flex items-center gap-2 text-[11px] text-kraft/70">
                                            <span class="rounded-[6px] border border-kraft/25 px-1.5 py-0.5 text-[10.5px]">{{ $bean->traceability?->process }}</span>
                                            <span class="text-bluelab"><span class="text-[13px]">{{ number_format($bean->cupping_score, 1) }}</span> SCA</span>
                                        </span>
                                    </span>
                                    <span class="grid h-6 w-6 place-items-center rounded-full bg-bluelab text-paper transition-all duration-300 {{ $on ? 'scale-100 opacity-100' : 'scale-50 opacity-0' }}">
                                        <svg viewBox="0 0 12 12" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 6.3l2.4 2.4 4.6-5"/></svg>
                                    </span>
                                </span>

                                {{-- Tampilan tablet ke atas: kartu --}}
                                <span class="hidden flex-col items-start justify-start p-4 sm:flex">
                                    <span class="flex w-full items-center justify-between gap-2">
                                        <span class="krm-readout rounded-[6px] border border-kraft/25 px-1.5 py-0.5 text-[10.5px] text-kraft/75">
                                            {{ $bean->traceability?->process }}
                                        </span>
                                        <span class="grid h-5 w-5 place-items-center rounded-full bg-bluelab text-paper transition-all duration-300 {{ $on ? 'scale-100 opacity-100' : 'scale-50 opacity-0' }}">
                                            <svg viewBox="0 0 12 12" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 6.3l2.4 2.4 4.6-5"/></svg>
                                        </span>
                                    </span>
                                    <x-coffee-bag :coffee="$bean" class="mx-auto mt-3 h-[128px] transition-transform duration-300 group-hover:-translate-y-0.5" />
                                    <span class="mt-3 block font-display text-[15.5px] font-semibold leading-snug text-labwhite">{{ $short }}</span>
                                    <span class="mt-1.5 block text-[12.5px] leading-snug text-kraft/65">
                                        {{ $reg }} <span class="text-kraft/40">·</span> {{ $ctry }}
                                    </span>
                                    <span class="krm-readout mt-4 flex items-baseline gap-1 text-bluelab">
                                        <span class="text-[1.15rem]">{{ number_format($bean->cupping_score, 1) }}</span>
                                        <span class="text-[10.5px] text-kraft/50">SCA</span>
                                    </span>
                                </span>
                            </button>
                        @endforeach
                    </div>

                    @if ($beanCount > 5)
                        <button type="button" x-show="!all && {{ $beanCount }} > max" x-cloak @click="all = true"
                                class="mt-3 w-full rounded border border-kraft/30 py-2.5 text-[13.5px] font-semibold text-kraft transition-colors hover:border-kraft/60 hover:text-labwhite">
                            {{ __('Tampilkan semua :n biji', ['n' => $beanCount]) }}
                        </button>
                    @endif
                </div>

                @if ($this->coffee?->story)
                    <p class="mt-6 max-w-[60ch] border-l-2 border-bluelab/60 pl-4 text-[14px] leading-relaxed text-kraft/75">
                        {{ $this->coffee->localizedStory() }}
                    </p>
                @endif
            </section>

            {{-- Langkah 2 --}}
            <section>
                <h2 class="krm-h3 flex items-baseline gap-3 font-semibold">
                    <span class="krm-readout text-[12px] text-bluelab">02</span>
                    {{ __('Level sangrai') }}
                </h2>

                @if ($this->levels->isNotEmpty())
                    <div class="mt-5 overflow-hidden rounded border border-kraft/20 bg-[linear-gradient(135deg,rgba(255,251,242,.035),transparent_60%)]">
                        <div class="flex flex-col items-center gap-6 p-6 sm:flex-row sm:gap-8 sm:p-8">

                            {{-- Foto biji per level. Keempatnya ditumpuk dan dimuat sekaligus,
                                 jadi pindah level cuma cross-fade opacity 0.3s tanpa menunggu unduhan. --}}
                            @php
                                $beanImg = ['light' => 'light', 'medium_light' => 'medium-light', 'medium' => 'medium', 'medium_dark' => 'dark', 'dark' => 'dark'];
                                $activeBean = $beanImg[$this->profile?->level] ?? 'medium-light';
                            @endphp
                            <div class="relative h-[84px] w-[84px] shrink-0" aria-hidden="true">
                                @foreach (['light', 'medium-light', 'medium', 'dark'] as $bn)
                                    <img src="{{ asset("images/beans/bean-{$bn}.png") }}" alt="" width="60" height="60"
                                         class="absolute left-1/2 top-1/2 w-[60px] -translate-x-1/2 -translate-y-1/2 border-0 bg-transparent object-contain drop-shadow-[0_8px_10px_rgb(var(--shadow)/.45)] transition-opacity duration-300 ease-out {{ $bn === $activeBean ? 'opacity-100' : 'opacity-0' }}">
                                @endforeach
                            </div>

                            <div class="w-full min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-4">
                                    <p class="font-display text-[1.7rem] font-semibold leading-none">
                                        {{ $this->profile?->level_label }}
                                    </p>
                                    <p class="krm-readout text-[13px] text-kraft/70">
                                        Agtron <span class="text-labwhite">{{ $this->profile?->agtron }}</span>
                                    </p>
                                </div>

                                <label for="roast" class="sr-only">{{ __('Level sangrai') }}</label>
                                <input id="roast" type="range" min="0" max="{{ $this->levels->count() - 1 }}" step="1"
                                       wire:model.live="levelIndex" class="krm-slider mt-6">

                                {{-- Titik harus sejajar dengan posisi thumb slider: thumb 18px, jadi pusatnya
                                     bergerak dari 9px sampai (lebar - 9px), bukan dari tepi ke tepi. --}}
                                @php $last = max(1, $this->levels->count() - 1); @endphp
                                <div class="relative mt-2 h-11">
                                    @foreach ($this->levels as $i => $lv)
                                        @php $x = "calc(9px + (100% - 18px) * {$i} / {$last})"; $on = $i === $this->levelIndex; @endphp
                                        <button type="button" wire:click="setLevel({{ $i }})" aria-label="{{ $lv->level_label }}"
                                                class="absolute top-0 h-11 w-11 -translate-x-1/2" style="left: {{ $x }}">
                                            <span class="absolute left-1/2 top-[7px] h-2.5 w-2.5 -translate-x-1/2 rounded-full border border-labwhite/20 transition-transform duration-300 {{ $on ? 'scale-125' : '' }}"
                                                  style="background: {{ $this->beanColor($lv->agtron) }}"></span>
                                        </button>
                                        <span class="pointer-events-none absolute top-[22px] whitespace-nowrap text-[11px] transition-colors
                                                     {{ $on ? 'text-labwhite' : 'text-kraft/50' }}
                                                     {{ $loop->first ? 'left-0' : ($loop->last ? 'right-0' : '-translate-x-1/2') }}"
                                              @unless ($loop->first || $loop->last) style="left: {{ $x }}" @endunless>{{ $lv->level_label }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if ($this->levels->count() < 5)
                            <p class="border-t border-kraft/15 px-6 py-4 text-[13px] leading-relaxed text-kraft/60 sm:px-8">
                                {{ __('Biji ini berhenti sebelum level paling gelap. Disangrai lebih jauh, karakter aslinya hilang dan yang tersisa cuma rasa gosong.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Langkah 3 --}}
            <section>
                <h2 class="krm-h3 flex items-baseline gap-3 font-semibold">
                    <span class="krm-readout text-[12px] text-bluelab">03</span>
                    {{ __('Ukuran gilingan') }}
                </h2>

                <div class="mt-5 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                    @foreach ($this->grindOptions as $key => $label)
                        @php $on = $grind === $key; @endphp
                        <button type="button" wire:click="setGrind('{{ $key }}')"
                                aria-pressed="{{ $on ? 'true' : 'false' }}"
                                class="flex items-center gap-3 rounded border px-4 py-3.5 text-left text-[13.5px] transition-all duration-300
                                       {{ $on
                                            ? 'border-bluelab bg-bluelab/12 text-labwhite shadow-[0_0_0_1px_#2A5DB0]'
                                            : 'border-kraft/20 text-kraft/80 hover:border-kraft/50' }}">
                            <span class="grid h-2 w-2 shrink-0 place-items-center rounded-full {{ $on ? 'bg-bluelab' : 'bg-kraft/30' }}"></span>
                            {{ __($label) }}
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- Langkah 4 --}}
            <section>
                <h2 class="krm-h3 flex items-baseline gap-3 font-semibold">
                    <span class="krm-readout text-[12px] text-bluelab">04</span>
                    {{ __('Berat') }}
                </h2>

                <div class="mt-5 grid max-w-xl grid-cols-3 gap-3">
                    @foreach ([200, 500, 1000] as $g)
                        @php $on = $grams === $g; @endphp
                        <button type="button" wire:click="setGrams({{ $g }})"
                                aria-pressed="{{ $on ? 'true' : 'false' }}"
                                class="rounded border px-3 py-5 text-center transition-all duration-300
                                       {{ $on
                                            ? 'border-bluelab bg-bluelab/12 shadow-[0_0_0_1px_#2A5DB0]'
                                            : 'border-kraft/20 hover:-translate-y-0.5 hover:border-kraft/50' }}">
                            <span class="krm-readout block text-[1.45rem] leading-none">{{ $g >= 1000 ? '1' : $g }}</span>
                            <span class="mt-1.5 block text-[12px] text-kraft/65">{{ $g >= 1000 ? __('kilogram') : __('gram') }}</span>
                            @if ($this->profile)
                                <span class="krm-readout mt-3 block border-t border-kraft/15 pt-2.5 text-[12px] text-kraft/60">
                                    Rp{{ number_format($this->profile->priceFor($g), 0, ',', '.') }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ─────────── PANEL RASA ─────────── --}}
        <div class="lg:sticky lg:top-24 lg:self-start">
            <div class="overflow-hidden rounded border border-kraft/25 bg-surface">

                <div class="p-6 pb-2">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="text-[1.1rem] font-semibold">{{ __('Profil rasa') }}</h2>
                        <span class="krm-readout inline-flex items-center gap-1.5 text-[11px] text-kraft/55">
                            <span class="h-1.5 w-1.5 rounded-full bg-bluelab animate-pulse"></span>LIVE
                        </span>
                    </div>
                    <p class="mt-1 text-[13px] text-kraft/60">{{ __('Bergerak tiap kali pilihanmu berubah.') }}</p>

                    {{-- wire:ignore: isi div ini digambar D3, bukan Blade.
                         Nilai barunya dikirim lewat event, bukan render ulang. --}}
                    <div wire:ignore class="mt-4"
                         {{-- Alpine memanggil init() sendiri; menuliskannya lagi
                              di x-init membuat grafiknya tergambar dua kali. --}}
                         x-data="flavorWheel(@js($this->flavorValues), @js($this->wheelLabels))">
                        <div x-ref="wheel" class="flex justify-center"></div>
                    </div>

                    @if ($this->profile?->tasting_notes)
                        <div class="mt-4 flex flex-wrap gap-1.5 pb-4">
                            @foreach ($this->profile->tasting_notes as $note)
                                <span class="rounded-[6px] border border-kraft/25 px-2.5 py-1 text-[12.5px] text-labwhite">{{ $note }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Bagian "struk": dipisah garis putus-putus seperti tiket lab --}}
                <div class="border-t border-dashed border-kraft/30 bg-surface p-6">
                    @if ($this->coffee)
                        <x-coffee-bag :coffee="$this->coffee" :level="$this->profile?->level ?? 'medium'" class="mx-auto mb-5 h-[120px]" />
                    @endif
                    <dl class="krm-settling space-y-2.5 text-[13px]" wire:loading.delay.class="opacity-50">
                        <div class="flex justify-between gap-4"><dt class="text-kraft/60">{{ __('Biji kopi') }}</dt><dd class="text-right">{{ $this->coffee?->name }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-kraft/60">{{ __('Sangrai') }}</dt><dd class="text-right">{{ $this->profile?->level_label }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-kraft/60">{{ __('Gilingan') }}</dt><dd class="text-right">{{ __($this->grindOptions[$grind]) }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-kraft/60">{{ __('Berat') }}</dt><dd class="krm-readout text-right">{{ number_format($grams) }} g</dd></div>
                    </dl>

                    <div class="krm-settling mt-5 flex items-end justify-between gap-4 border-t border-dashed border-kraft/30 pt-5" wire:loading.delay.class="opacity-50">
                        <span class="text-[13px] text-kraft/60">{{ __('Total') }}</span>
                        <span class="krm-readout text-[1.9rem] font-medium leading-none">Rp{{ number_format($this->price, 0, ',', '.') }}</span>
                    </div>

                    @if (auth()->user()?->isStaff())
                        <div class="mt-6 rounded border border-kraft/30 px-4 py-4 text-center text-[13px] leading-relaxed text-kraft/75">
                            {{ __('Kamu masuk sebagai :role, jadi ini mode lihat saja. Belanja memakai akun pembeli.', ['role' => auth()->user()->isAdmin() ? 'admin' : 'roaster']) }}
                            <a href="{{ route(auth()->user()->homeRoute()) }}" class="mt-3 block font-display text-[14px] font-semibold text-bluetext hover:underline">{{ __('Kembali ke panel') }} &rarr;</a>
                        </div>
                    @elseif ($this->stockOk)
                        <button type="button" wire:click="addToCart"
                                class="mt-6 w-full rounded bg-bluelab py-4 font-display text-[15px] font-semibold transition-all hover:bg-[#234E94] hover:shadow-[0_10px_28px_-10px_rgba(42,93,176,.9)] active:scale-[.99]">
                            {{ __('Tambah ke keranjang') }}
                        </button>
                        @error('cart')<p class="mt-3 text-center text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                        <p class="mt-3 text-center text-[12px] leading-relaxed text-kraft/55">
                            {{ __('Disangrai setelah pesanan masuk. Berangkat dalam 24 jam.') }}
                        </p>
                    @else
                        <p class="mt-6 rounded border border-kraft/30 px-4 py-4 text-center text-[13px] leading-relaxed text-kraft/70">
                            {{ __('Stok biji mentahnya tidak cukup untuk berat ini. Coba ukuran yang lebih kecil.') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- Bar total untuk layar kecil: panel asli ada jauh di bawah --}}
    @if ($this->stockOk && ! auth()->user()?->isStaff())
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-labwhite/15 bg-surface/95 px-4 py-3 backdrop-blur lg:hidden">
            <div class="mx-auto flex max-w-[1180px] items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="truncate text-[12px] text-kraft/60">{{ $this->coffee?->name }} · {{ $this->profile?->level_label }} · {{ number_format($grams) }} g</p>
                    <p class="krm-readout text-[1.15rem] leading-tight">Rp{{ number_format($this->price, 0, ',', '.') }}</p>
                </div>
                <button type="button" wire:click="addToCart" class="shrink-0 rounded bg-bluelab px-5 py-3 font-display text-[14px] font-semibold">{{ __('Tambah ke keranjang') }}</button>
            </div>
        </div>
    @endif
</div>
