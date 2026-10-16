<div>

    {{-- ══════════════════ HERO ══════════════════ --}}
    <section class="relative isolate overflow-hidden -mt-[73px] pb-2 pt-[calc(73px+3rem)] md:pt-[calc(73px+4.5rem)]">
        {{-- Foto ruang sangrai asli sebagai latar. Dua lapis gelap:
             satu dari kiri supaya teks terbaca, satu dari bawah supaya
             kromatogram duduk di atas warna yang sama dengan sisa halaman. --}}
        <picture class="absolute inset-0 -z-20">
            <source media="(max-width: 767px)" srcset="{{ asset('images/hero-roastery-sm.webp') }}" type="image/webp">
            <source srcset="{{ asset('images/hero-roastery.webp') }}" type="image/webp">
            <img src="{{ asset('images/hero-roastery.jpg') }}" alt=""
                 width="1920" height="1280" fetchpriority="high" decoding="async"
                 class="h-full w-full object-cover object-[68%_50%] md:object-[60%_45%]">
        </picture>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(18,18,18,.95)_0%,rgba(18,18,18,.86)_46%,rgba(18,18,18,.42)_78%,rgba(18,18,18,.55)_100%)] max-md:bg-[linear-gradient(180deg,rgba(18,18,18,.82)_0%,rgba(18,18,18,.72)_55%,rgba(18,18,18,1)_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-3/5 bg-gradient-to-b from-transparent via-charcoal/80 to-charcoal"></div>

        <div class="mx-auto max-w-[1180px] px-6">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="max-w-3xl">
                <h1 class="krm-h1 font-bold">
                    {{ __('Disangrai setelah') }}<br>{{ __('pesanan masuk.') }}
                </h1>

                <p class="mt-7 max-w-[54ch] text-[15px] leading-relaxed text-kraft">
                    {{ __('Kami tidak menyimpan kopi yang sudah disangrai. Biji mentahnya menunggu di gudang sampai kamu memilih level sangrai dan ukuran gilingannya — baru mesinnya dinyalakan.') }}
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="{{ route('builder') }}"
                       class="rounded bg-bluelab px-7 py-3.5 font-display text-[15px] font-semibold text-labwhite transition-colors hover:bg-[#234E94]">
                        {{ __('Racik kopimu') }}
                    </a>
                    <a href="#biji"
                       class="rounded border border-kraft/35 px-7 py-3.5 font-display text-[15px] font-semibold text-kraft transition-colors hover:border-kraft/70 hover:text-labwhite">
                        {{ __('Lihat biji yang ada') }}
                    </a>
                </div>
            </div>

            {{-- Keterangan jejak di bawah ini. Tanpa ini grafiknya
                 cuma gambar; dengan ini ia jadi data milik satu lot. --}}
            @php $contoh = $this->coffees->first(); @endphp
            @if ($contoh)
                <dl class="hidden max-w-[16rem] space-y-3 border-l border-kraft/20 pl-5 text-[12.5px] lg:block">
                    <div>
                        <dt class="text-kraft/50">{{ __('Contoh yang diukur') }}</dt>
                        <dd class="mt-0.5 text-labwhite">{{ $contoh->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-kraft/50">{{ __('Sangrai') }}</dt>
                        <dd class="mt-0.5 text-labwhite">{{ $contoh->signatureProfile()?->level_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-kraft/50">{{ __('Puncak bernama') }}</dt>
                        <dd class="krm-readout mt-0.5 text-labwhite">{{ __(':count senyawa', ['count' => count($this->peaks)]) }}</dd>
                    </div>
                </dl>
            @endif
            </div>
        </div>

        {{-- Kromatogram: melintas penuh dari tepi ke tepi, persis
             seperti waveform di stempel logo. --}}
        <div class="mt-6"
             x-data="chromatogram(@js($this->peaks), @js($this->chromatogramText))">
            <div x-ref="trace" class="w-full"></div>
        </div>

        {{-- Tiga janji, ditulis sebagai angka yang bisa dicek --}}
        <div class="mx-auto mt-4 max-w-[1180px] px-6">
            <dl class="grid gap-5 border-t border-kraft/15 pt-5 sm:grid-cols-3 sm:gap-8">
                <div>
                    <dt class="krm-readout text-[1.6rem] font-medium text-labwhite">1,2 kg</dt>
                    <dd class="mt-1.5 text-[13px] leading-relaxed text-kraft/70">
                        {{ __('Batch terbesar yang kami sangrai sekali jalan.') }}
                    </dd>
                </div>
                <div>
                    <dt class="krm-readout text-[1.6rem] font-medium text-labwhite">{{ __('1 petani') }}</dt>
                    <dd class="mt-1.5 text-[13px] leading-relaxed text-kraft/70">
                        {{ __('Tiap lot datang dari satu kebun, bukan campuran.') }}
                    </dd>
                </div>
                <div>
                    <dt class="krm-readout text-[1.6rem] font-medium text-labwhite">{{ __('24 jam') }}</dt>
                    <dd class="mt-1.5 text-[13px] leading-relaxed text-kraft/70">
                        {{ __('Jarak dari biji keluar mesin sampai paket berangkat.') }}
                    </dd>
                </div>
            </dl>
        </div>
    </section>


    {{-- ══════════════════ BIJI / LOGBOOK ══════════════════ --}}
    <section id="biji" class="mx-auto max-w-[1180px] px-6 pt-16 md:pt-20">
        <h2 class="krm-h2 font-semibold">
            {{ __('Biji yang sedang ada di gudang') }}
        </h2>
        <p class="mt-3 max-w-[58ch] text-[15px] leading-relaxed text-kraft/75">
            {{ __('Tiap lot punya catatan asal-usulnya sendiri. Kalau datanya tidak lengkap, kopinya tidak kami jual.') }}
        </p>

        @php
            $processes = $this->coffees->map(fn ($c) => $c->traceability?->process)->filter()->unique()->values();
        @endphp

        <div class="mt-10" x-data="{ f: 'semua' }">

            {{-- Saringan proses. Di gudang beneran, orang memilah lot
                 berdasarkan cara pengolahannya dulu. --}}
            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('Saring berdasarkan proses') }}">
                <span class="mr-2 text-[12px] text-kraft/50">{{ __('Proses') }}</span>
                <button type="button" @click="f = 'semua'"
                        :aria-pressed="f === 'semua'"
                        :class="f === 'semua' ? 'border-bluelab bg-bluelab text-labwhite' : 'border-kraft/25 text-kraft/80 hover:border-kraft/60'"
                        class="rounded border px-3.5 py-1.5 text-[13px] transition-colors">
                    {{ __('Semua') }} <span class="krm-readout ml-1 text-[11px] opacity-70">{{ $this->coffees->count() }}</span>
                </button>
                @foreach ($processes as $proc)
                    <button type="button" @click="f = @js($proc)"
                            :aria-pressed="f === @js($proc)"
                            :class="f === @js($proc) ? 'border-bluelab bg-bluelab text-labwhite' : 'border-kraft/25 text-kraft/80 hover:border-kraft/60'"
                            class="rounded border px-3.5 py-1.5 text-[13px] transition-colors">
                        {{ $proc }}
                        <span class="krm-readout ml-1 text-[11px] opacity-70">{{ $this->coffees->filter(fn ($c) => $c->traceability?->process === $proc)->count() }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Kepala kolom, hanya di layar lebar --}}
            <div class="mt-8 hidden grid-cols-[minmax(0,1fr)_150px_96px_64px_28px] gap-6 border-b border-kraft/15 pb-3 text-[12px] text-kraft/50 md:grid">
                <span>{{ __('Lot') }}</span><span>{{ __('Proses') }}</span><span>{{ __('Ketinggian') }}</span><span>{{ __('Skor') }}</span><span></span>
            </div>

            @foreach ($this->coffees as $bean)
                @php $t = $bean->traceability; $sig = $bean->signatureProfile(); @endphp

                <article x-data="{ open: false }"
                         x-show="f === 'semua' || f === @js($t?->process)"
                         class="border-b border-kraft/15">

                    <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                            aria-controls="lot-{{ $bean->id }}"
                            class="group grid w-full grid-cols-[minmax(0,1fr)_28px] items-center gap-x-6 gap-y-1 py-5 text-left md:grid-cols-[minmax(0,1fr)_150px_96px_64px_28px]">

                        <span class="min-w-0">
                            <span class="krm-h3 block font-semibold leading-tight transition-colors group-hover:text-bluelab/90">{{ $bean->name }}</span>
                            <span class="mt-1 block truncate text-[13px] text-kraft/60">{{ $t?->region }}</span>
                        </span>

                        <span class="hidden text-[14px] text-labwhite md:block">{{ $t?->process ?? '—' }}</span>
                        <span class="krm-readout hidden text-[13px] text-labwhite md:block">{{ $t?->altitude ? number_format($t->altitude) . ' m' : '—' }}</span>
                        <span class="krm-readout hidden text-[13px] text-bluelab md:block">{{ number_format($bean->cupping_score, 1) }}</span>

                        <svg viewBox="0 0 12 12" class="h-3 w-3 shrink-0 justify-self-end text-kraft/60 transition-transform duration-200"
                             :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M2 4.5l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>

                        {{-- Ringkasan untuk layar sempit, kolom di atas disembunyikan --}}
                        <span class="col-span-2 mt-1 flex flex-wrap gap-x-4 gap-y-1 text-[12.5px] text-kraft/70 md:hidden">
                            <span>{{ $t?->process }}</span>
                            <span class="krm-readout">{{ $t?->altitude ? number_format($t->altitude) . ' m' : '' }}</span>
                            <span>{{ __('skor') }} <span class="krm-readout text-bluelab">{{ number_format($bean->cupping_score, 1) }}</span></span>
                        </span>
                    </button>

                    <div id="lot-{{ $bean->id }}" x-show="open" x-cloak x-collapse
                         class="grid gap-7 pb-8 md:grid-cols-[minmax(0,1fr)_minmax(0,340px)] md:gap-12">
                        <div>
                            <p class="max-w-[56ch] text-[14px] leading-relaxed text-kraft/75">{{ $bean->localizedStory() }}</p>

                            @if ($sig?->tasting_notes)
                                <p class="mt-5 text-[14px] leading-relaxed text-labwhite/90">
                                    {{ implode(', ', $sig->tasting_notes) }}
                                    <span class="text-kraft/55">{{ __('— pada sangrai :level', ['level' => strtolower($sig->level_label)]) }}</span>
                                </p>
                            @endif

                            <a href="{{ route('builder', ['kopi' => $bean->slug]) }}"
                               class="mt-6 inline-block rounded border border-kraft/35 px-5 py-2.5 font-display text-[14px] font-semibold text-kraft transition-colors hover:border-kraft/70 hover:text-labwhite">
                                {{ __('Racik kopi ini') }}
                            </a>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-8 gap-y-5 self-start text-[13px]">
                            <div>
                                <dt class="text-kraft/55">{{ __('Varietas') }}</dt>
                                <dd class="mt-1 text-labwhite">{{ $t?->varietal ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-kraft/55">{{ __('Panen') }}</dt>
                                <dd class="krm-readout mt-1 text-labwhite">{{ $t?->harvest_date?->format('m.Y') ?? '—' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-kraft/55">{{ __('Petani') }}</dt>
                                <dd class="mt-1 text-labwhite">{{ $t?->farmer ?? '—' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-kraft/55">{{ __('Dinilai oleh') }}</dt>
                                <dd class="mt-1 text-labwhite">{{ $t?->q_grader ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </article>
            @endforeach
        </div>
    </section>


    {{-- ══════════════════ TAHAPAN (kertas kraft) ══════════════════ --}}
    <section id="tahapan" class="mt-16 scroll-mt-16 bg-kraft py-14 text-charcoal md:mt-20">
        <div class="mx-auto max-w-[1180px] px-6">
            <div class="flex items-start justify-between gap-10">
                <div>
                    <h2 class="krm-h2 max-w-[20ch] font-semibold">
                        {{ __('Yang terjadi setelah kamu bayar') }}
                    </h2>
                    <p class="mt-3 max-w-[56ch] text-[15px] leading-relaxed text-kraftink">
                        {{ __('Lima tahap, dan kamu bisa melihat pesananmu ada di tahap mana lewat halaman lacak.') }}
                    </p>
                </div>

                <img src="{{ asset('images/logo-kromatik-dark.png') }}"
                     alt="" width="128" height="128"
                     class="hidden w-[112px] shrink-0 opacity-70 lg:block">
            </div>

            {{-- Satu strip, bukan lima kotak terpisah: panel menyatu dengan
                 garis rambut di antaranya, seperti lembar kontak film. --}}
            <ol class="mt-8 grid overflow-hidden rounded border border-charcoal/25 bg-charcoal/25 gap-px sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($this->stages as $i => $stage)
                    <li class="flex bg-kraft sm:block">
                        <div class="relative w-[104px] shrink-0 overflow-hidden sm:w-full">
                            <img src="{{ asset('images/stage-' . ($i + 1) . '.webp') }}" alt="{{ $stage['name'] }}"
                                 width="586" height="1060" loading="lazy" decoding="async"
                                 class="h-full w-full object-cover sm:aspect-[4/5]">
                            <span class="krm-readout absolute left-2 top-2 rounded-[2px] bg-charcoal/85 px-1.5 py-0.5 text-[11px] text-labwhite">
                                {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                        <div class="p-4 sm:p-4 sm:pb-5">
                            <h3 class="text-[1.1rem] font-semibold">{{ $stage['name'] }}</h3>
                            <p class="mt-1.5 text-[13px] leading-relaxed text-kraftink">
                                {{ $stage['note'] }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>


    {{-- ══════════════════ PENUTUP ══════════════════ --}}
    <section class="mx-auto max-w-[1180px] px-6 py-14 md:py-16">
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between md:gap-12">
            <div class="max-w-[40ch]">
                <h2 class="krm-h2 font-semibold">
                    {{ __('Mulai dari rasa, bukan dari nama kopinya') }}
                </h2>
                <p class="mt-3 text-[15px] leading-relaxed text-kraft/75">
                    {{ __('Geser level sangrainya, lihat profil rasanya bergerak, lalu pesan di situ juga.') }}
                </p>
            </div>
            <a href="{{ route('builder') }}"
               class="self-start rounded bg-bluelab px-7 py-3.5 font-display text-[15px] font-semibold text-labwhite transition-colors hover:bg-[#234E94] md:self-auto">
                {{ __('Buka roast builder') }}
            </a>
        </div>
    </section>
</div>
