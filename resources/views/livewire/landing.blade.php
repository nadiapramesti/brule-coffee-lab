<div>

    {{-- ══════════════════ HERO ══════════════════ --}}
    <section class="krm-dark relative isolate overflow-hidden -mt-[74px] pb-2 pt-[calc(74px+3rem)] md:pt-[calc(74px+4.5rem)]">
        {{-- Foto ruang sangrai asli sebagai latar. Dua lapis gelap:
             satu dari kiri supaya teks terbaca, satu dari bawah supaya
             kromatogram duduk di atas warna yang sama dengan sisa halaman. --}}
        <picture class="krm-kenburns absolute inset-0 -z-20">
            <source media="(max-width: 767px)" srcset="{{ asset('images/hero-roastery-sm.webp') }}" type="image/webp">
            <source srcset="{{ asset('images/hero-roastery.webp') }}" type="image/webp">
            <img src="{{ asset('images/hero-roastery.jpg') }}" alt=""
                 width="1920" height="1280" fetchpriority="high" decoding="async"
                 class="h-full w-full object-cover object-[68%_50%] md:object-[60%_45%]">
        </picture>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(18,18,18,.95)_0%,rgba(18,18,18,.86)_46%,rgba(18,18,18,.42)_78%,rgba(18,18,18,.55)_100%)] max-md:bg-[linear-gradient(180deg,rgba(18,18,18,.82)_0%,rgba(18,18,18,.72)_55%,rgba(18,18,18,1)_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-3/5 bg-gradient-to-b from-transparent via-charcoal/80 to-charcoal"></div>
        <div class="absolute inset-0 -z-10" x-data="embers" aria-hidden="true"><canvas x-ref="canvas" class="h-full w-full"></canvas></div>

        <div class="mx-auto max-w-[1180px] px-6">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="max-w-3xl">
                <p class="mb-6 inline-flex items-center gap-3 rounded-full border border-labwhite/15 bg-charcoal/50 py-1.5 pl-3 pr-4 text-[12px] backdrop-blur-[2px]"
                   x-data="roastClock" data-reveal>
                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#E8782A] opacity-70"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-[#E8782A]"></span></span>
                    <span class="krm-readout tracking-[0.12em] text-kraft/80">{{ __('SEDANG DISANGRAI') }}</span>
                    <span class="krm-readout text-labwhite"><span x-text="temp.toFixed(1)">210.0</span>&deg;C</span>
                    <span class="krm-readout text-kraft/60" x-text="clock">09:42</span>
                </p>

                <h1 class="krm-h1 font-bold">
                    <span class="krm-line block"><span style="--d:0ms">{{ __('Disangrai setelah') }}</span></span>
                    <span class="krm-line block"><span class="krm-ember-text" style="--d:140ms">{{ __('pesanan masuk.') }}</span></span>
                </h1>

                <p class="mt-7 max-w-[54ch] text-[15px] leading-relaxed text-kraft" data-reveal style="--d:120ms">
                    {{ __('Kami tidak menyimpan kopi yang sudah disangrai. Biji mentahnya menunggu di gudang sampai kamu memilih level sangrai dan ukuran gilingannya — baru mesinnya dinyalakan.') }}
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-3" data-reveal style="--d:240ms">
                    <a href="{{ route('builder') }}"
                       class="rounded bg-bluelab px-7 py-3.5 font-display text-[15px] font-semibold text-paper transition-colors hover:bg-[#234E94]">
                        {{ __('Racik kopimu') }}
                    </a>
                    <a href="#biji"
                       class="group inline-flex items-center gap-2 px-2 py-3.5 font-display text-[15px] font-semibold text-kraft transition-colors hover:text-labwhite">
                        {{ __('Lihat biji yang ada') }}
                        <svg viewBox="0 0 16 16" class="h-3.5 w-3.5 transition-transform group-hover:translate-y-0.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3v10M3.5 8.5L8 13l4.5-4.5"/></svg>
                    </a>
                </div>
            </div>

            {{-- Keterangan jejak di bawah ini. Tanpa ini grafiknya
                 cuma gambar; dengan ini ia jadi data milik satu lot. --}}
            @php $contoh = $this->coffees->first(); @endphp
            @if ($contoh)
                <dl data-reveal style="--d:360ms" class="hidden max-w-[16rem] space-y-3 border-l border-kraft/20 pl-5 text-[12.5px] lg:block">
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
            <dl class="grid gap-5 border-t border-kraft/15 pt-5 sm:grid-cols-3 sm:gap-8" data-reveal>
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


    {{-- ══════════════════ KATALOG BIJI ══════════════════ --}}
    <section id="biji" class="mx-auto max-w-[1180px] scroll-mt-16 px-6 pt-16 md:pt-20">
        <h2 class="krm-h2 font-semibold" data-reveal>
            {{ __('Biji yang sedang ada di gudang') }}
        </h2>
        <p class="mt-3 max-w-[58ch] text-[15px] leading-relaxed text-kraft/75">
            {{ __('Tiap lot punya catatan asal-usulnya sendiri. Kalau datanya tidak lengkap, kopinya tidak kami jual.') }}
        </p>

        @php
            $processes = $this->coffees->map(fn ($c) => $c->traceability?->process)->filter()->unique()->values();
        @endphp

        @php
            $catalog = $this->coffees->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'hay' => mb_strtolower(implode(' ', array_filter([
                    $c->name, $c->traceability?->region, $c->traceability?->process, $c->traceability?->varietal,
                    implode(' ', $c->signatureProfile()?->tasting_notes ?? []),
                ]))),
                'process' => $c->traceability?->process,
                'price' => (int) ($c->startingPrice() ?? 0),
                'score' => (float) $c->cupping_score,
                'rating' => (float) ($c->reviews_avg ?? 0),
            ])->values();
        @endphp

        <div class="mt-10" x-data="{
                f: 'semua', q: '', sort: 'default', items: @js($catalog),
                res: [], posMap: {}, limit: 9, step: 9,
                init() {
                    // Banyaknya kartu per tampilan mengikuti lebar layar: HP 4, tablet 6, laptop 9.
                    this.step = innerWidth < 640 ? 4 : (innerWidth < 1024 ? 6 : 9);
                    this.limit = this.step;
                    this.recalc();
                    ['f', 'q', 'sort'].forEach(k => this.$watch(k, () => { this.limit = this.step; this.recalc(); }));
                },
                recalc() {
                    const words = this.q.trim().toLowerCase().split(/\s+/).filter(Boolean);
                    let l = this.items.filter(i => (this.f === 'semua' || i.process === this.f) && words.every(w => i.hay.includes(w)));
                    const by = {
                        price_asc: (a, b) => a.price - b.price,
                        price_desc: (a, b) => b.price - a.price,
                        score: (a, b) => b.score - a.score,
                        rating: (a, b) => b.rating - a.rating || b.score - a.score,
                        name: (a, b) => a.name.localeCompare(b.name),
                    };
                    if (by[this.sort]) l = [...l].sort(by[this.sort]);
                    const m = {}; l.forEach((i, k) => m[i.id] = k);
                    this.res = l; this.posMap = m;
                },
                show(id) { const p = this.posMap[id]; return p !== undefined && p < this.limit },
                pos(id) { return this.posMap[id] ?? 0 },
                more() { this.limit += this.step },
                reset() { this.f = 'semua'; this.q = ''; this.sort = 'default' },
                get remaining() { return Math.max(0, this.res.length - this.limit) },
                get count() {
                    return this.res.length > this.limit
                        ? @js(__('Menampilkan :shown dari :n lot')).replace(':shown', this.limit).replace(':n', this.res.length)
                        : @js(__(':n lot ditampilkan')).replace(':n', this.res.length);
                },
            }">

            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <label for="cari-biji" class="sr-only">{{ __('Cari biji') }}</label>
                    <svg viewBox="0 0 20 20" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-kraft/50" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="M13.2 13.2L17 17"/></svg>
                    <input id="cari-biji" type="search" x-model="q" autocomplete="off"
                           placeholder="{{ __('Contoh: Gayo, chocolate, citrus') }}"
                           class="w-full rounded border border-kraft/25 bg-transparent py-3 pl-10 pr-4 text-[14.5px] text-labwhite placeholder:text-kraft/40 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                </div>
                <div>
                    <label for="urut-biji" class="sr-only">{{ __('Urutkan') }}</label>
                    <select id="urut-biji" x-model="sort"
                            class="w-full rounded border border-kraft/25 bg-transparent px-4 py-3 text-[14.5px] text-labwhite focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40 sm:w-[15rem]">
                        <option value="default" class="text-charcoal">{{ __('Urutkan: Bawaan') }}</option>
                        <option value="price_asc" class="text-charcoal">{{ __('Harga terendah') }}</option>
                        <option value="price_desc" class="text-charcoal">{{ __('Harga tertinggi') }}</option>
                        <option value="score" class="text-charcoal">{{ __('Skor cupping tertinggi') }}</option>
                        <option value="rating" class="text-charcoal">{{ __('Rating tertinggi') }}</option>
                        <option value="name" class="text-charcoal">{{ __('Nama A–Z') }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('Saring berdasarkan proses') }}">
                <span class="mr-2 text-[12px] text-kraft/50">{{ __('Proses') }}</span>
                <button type="button" @click="f = 'semua'"
                        :aria-pressed="f === 'semua'"
                        :class="f === 'semua' ? 'border-bluelab bg-bluelab text-paper' : 'border-kraft/25 text-kraft/80 hover:border-kraft/60'"
                        class="rounded border px-3.5 py-1.5 text-[13px] transition-colors">
                    {{ __('Semua') }} <span class="krm-readout ml-1 text-[11px] opacity-70">{{ $this->coffees->count() }}</span>
                </button>
                @foreach ($processes as $proc)
                    <button type="button" @click="f = @js($proc)"
                            :aria-pressed="f === @js($proc)"
                            :class="f === @js($proc) ? 'border-bluelab bg-bluelab text-paper' : 'border-kraft/25 text-kraft/80 hover:border-kraft/60'"
                            class="rounded border px-3.5 py-1.5 text-[13px] transition-colors">
                        {{ $proc }}
                        <span class="krm-readout ml-1 text-[11px] opacity-70">{{ $this->coffees->filter(fn ($c) => $c->traceability?->process === $proc)->count() }}</span>
                    </button>
                @endforeach
            </div>

            <p class="krm-readout mt-5 text-[12px] text-kraft/55" aria-live="polite" x-text="count">{{ $this->coffees->count() }}</p>

            <div x-show="res.length === 0" x-cloak class="mt-4 rounded border border-dashed border-kraft/30 px-6 py-12 text-center">
                <p class="font-display text-[1.1rem] font-semibold">{{ __('Tidak ada biji yang cocok') }}</p>
                <p class="mx-auto mt-2 max-w-[44ch] text-[14px] text-kraft/65">{{ __('Coba kata kunci lain, atau hapus saringan proses.') }}</p>
                <button type="button" @click="reset()" class="mt-5 rounded border border-kraft/35 px-5 py-2.5 font-display text-[14px] font-semibold text-kraft transition-colors hover:border-kraft/70 hover:text-labwhite">{{ __('Reset pencarian') }}</button>
            </div>

            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->coffees as $bean)
                    @php
                        $t = $bean->traceability; $sig = $bean->signatureProfile();
                        $state = $bean->stockState(); $from = $bean->startingPrice();
                    @endphp

                    <article x-data="{ open: false }" data-reveal style="--d: {{ min($loop->index, 5) * 50 }}ms"
                             x-show="show({{ $bean->id }})" :style="{ order: pos({{ $bean->id }}) }"
                             class="flex min-w-0 flex-col overflow-hidden rounded border border-kraft/20 bg-surface transition-colors hover:border-kraft/45">

                        {{-- Foto produk kalau sudah diupload, kalau belum ilustrasi kantongnya --}}
                        <a href="{{ route('builder', ['kopi' => $bean->slug]) }}" class="relative block aspect-[4/3] overflow-hidden bg-charcoal/60" tabindex="-1" aria-hidden="true">
                            @if ($bean->imageUrl())
                                <img src="{{ $bean->imageUrl() }}" alt="" width="640" height="480" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            @else
                                <div class="grid h-full place-items-center py-4">
                                    <x-coffee-bag :coffee="$bean" :level="$sig?->level ?? 'medium'" class="h-full max-h-[210px] w-auto" />
                                </div>
                            @endif
                            <span class="absolute left-3 top-3 rounded-[6px] px-2 py-0.5 text-[11.5px] font-semibold
                                {{ $state === 'soldout' ? 'bg-[#8a2b24] text-white' : ($state === 'low' ? 'bg-[#a8741a] text-white' : 'bg-charcoal/80 text-labwhite') }}">
                                {{ $state === 'soldout' ? __('Habis') : ($state === 'low' ? __('Stok terbatas') : __('Tersedia')) }}
                            </span>
                        </a>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="krm-h3 min-w-0 font-semibold leading-tight">{{ $bean->name }}</h3>
                                <span class="krm-readout shrink-0 text-[13px] text-bluelab" title="{{ __('Skor cupping') }}">{{ number_format($bean->cupping_score, 1) }}</span>
                            </div>
                            <p class="mt-1 text-[13px] text-kraft/60">
                                {{ collect([$t?->region, $t?->process, $t?->altitude ? number_format($t->altitude).' m' : null])->filter()->implode(' · ') }}
                            </p>

                            <p class="mt-2 flex items-center gap-2 text-[12.5px] text-kraft/60">
                                @if ($bean->reviews_count)
                                    <x-stars :value="$bean->reviews_avg" size="h-3.5 w-3.5" />
                                    <span><span class="krm-readout text-labwhite">{{ number_format($bean->reviews_avg, 1) }}</span> · {{ trans_choice(':n ulasan|:n ulasan', $bean->reviews_count, ['n' => $bean->reviews_count]) }}</span>
                                @else
                                    <span class="text-kraft/45">{{ __('Belum ada ulasan') }}</span>
                                @endif
                            </p>

                            @if ($sig?->tasting_notes)
                                <p class="mt-3 text-[14px] leading-snug text-labwhite/90">{{ implode(', ', $sig->tasting_notes) }}</p>
                            @endif

                            <div class="mt-auto pt-5">
                                @if ($from)
                                    <p class="text-[13px] text-kraft/60">
                                        {{ __('Mulai dari') }}
                                        <span class="krm-readout text-[17px] font-semibold text-labwhite">Rp{{ number_format($from, 0, ',', '.') }}</span>
                                        <span class="text-kraft/50">/ 200 g</span>
                                    </p>
                                @endif

                                <div class="mt-3 flex items-center gap-3">
                                    @if ($state === 'soldout')
                                        <span class="flex-1 cursor-not-allowed rounded border border-kraft/20 px-4 py-2.5 text-center font-display text-[14px] font-semibold text-kraft/45">{{ __('Habis') }}</span>
                                    @else
                                        <a href="{{ route('builder', ['kopi' => $bean->slug]) }}"
                                           class="flex-1 rounded bg-bluelab px-4 py-2.5 text-center font-display text-[14px] font-semibold text-paper transition-colors hover:bg-[#234E94]">
                                            {{ __('Racik kopi ini') }}
                                        </a>
                                    @endif
                                    <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-controls="lot-{{ $bean->id }}"
                                            class="shrink-0 px-1 py-2 text-[13px] text-kraft/70 underline-offset-4 transition-colors hover:text-labwhite hover:underline">
                                        <span x-text="open ? @js(__('Tutup')) : @js(__('Detail lot'))">{{ __('Detail lot') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div id="lot-{{ $bean->id }}" x-show="open" x-cloak x-collapse>
                                <div class="mt-5 border-t border-kraft/15 pt-5">
                                    <p class="text-[13.5px] leading-relaxed text-kraft/75">{{ $bean->localizedStory() }}</p>
                                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-[12.5px]">
                                        <div><dt class="text-kraft/55">{{ __('Varietas') }}</dt><dd class="mt-0.5 text-labwhite">{{ $t?->varietal ?? '—' }}</dd></div>
                                        <div><dt class="text-kraft/55">{{ __('Panen') }}</dt><dd class="krm-readout mt-0.5 text-labwhite">{{ $t?->harvest_date?->format('m.Y') ?? '—' }}</dd></div>
                                        <div><dt class="text-kraft/55">{{ __('Petani') }}</dt><dd class="mt-0.5 text-labwhite">{{ $t?->farmer ?? '—' }}</dd></div>
                                        <div><dt class="text-kraft/55">{{ __('Dinilai oleh') }}</dt><dd class="mt-0.5 text-labwhite">{{ $t?->q_grader ?? '—' }}</dd></div>
                                    </dl>

                                    <div class="mt-5 border-t border-kraft/15 pt-4">
                                        <p class="krm-readout text-[11px] tracking-[0.14em] text-kraft/55">{{ __('ULASAN PEMBELI') }}</p>
                                        @forelse ($bean->visibleReviews->take(3) as $rv)
                                            <figure class="mt-3">
                                                <figcaption class="flex flex-wrap items-center gap-x-2 text-[12.5px] text-kraft/65">
                                                    <x-stars :value="$rv->rating" size="h-3 w-3" />
                                                    <span class="text-labwhite">{{ $rv->displayName() }}</span>
                                                    <span class="rounded-[6px] border border-kraft/30 px-1.5 text-[10.5px]">{{ __('Pembeli terverifikasi') }}</span>
                                                </figcaption>
                                                @if ($rv->body)<blockquote class="mt-1 text-[13px] leading-relaxed text-kraft/75">{{ $rv->body }}</blockquote>@endif
                                            </figure>
                                        @empty
                                            <p class="mt-2 text-[13px] text-kraft/55">{{ __('Belum ada yang mengulas. Ulasan hanya bisa ditulis pembeli yang pesanannya sudah selesai.') }}</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Banyak produk: tampilkan bertahap, bukan satu halaman super panjang. --}}
            <div x-show="remaining > 0" x-cloak class="mt-8 flex flex-col items-center gap-2">
                <button type="button" @click="more()"
                        class="rounded border border-kraft/35 px-7 py-3 font-display text-[14.5px] font-semibold text-kraft transition-colors hover:border-kraft/70 hover:text-labwhite focus:outline-none focus-visible:ring-2 focus-visible:ring-bluelab/50">
                    <span x-text="@js(__('Tampilkan :n lot lagi')).replace(':n', Math.min(step, remaining))"></span>
                </button>
                <p class="krm-readout text-[11.5px] text-kraft/50" x-text="@js(__('Sisa :n lot')).replace(':n', remaining)"></p>
            </div>
        </div>
    </section>


    {{-- ══════════════════ TAHAPAN: protokol satu foto, lima langkah ══════════════════ --}}
    <section id="tahapan" class="krm-dark mt-16 scroll-mt-16 bg-kraft py-14 text-charcoal md:mt-20 md:py-16">
        <div class="mx-auto max-w-[1180px] px-6">

            <div class="flex items-start justify-between gap-10" data-reveal>
                <div>
                    <h2 class="krm-h2 max-w-[22ch] font-semibold text-[#1a1208]">
                        {{ __('Yang terjadi setelah kamu bayar') }}
                    </h2>
                    <p class="mt-3 max-w-[56ch] text-[15px] leading-relaxed text-kraftink">
                        {{ __('Lima tahap, dan kamu bisa melihat pesananmu ada di tahap mana lewat halaman lacak.') }}
                    </p>
                </div>
                <x-brand-logo variant="stacked" force="light" class="hidden h-[130px] shrink-0 opacity-90 lg:block" />
            </div>

            {{-- Lima kartu: foto 3:4 di atas, teks di bawah. Fotonya gelap
                 (kayu tua) supaya kontras dengan kertas kraft. Di HP kartu
                 digeser ke samping (scroll-snap), bukan ditumpuk 5 layar. --}}
            {{-- HP: daftar vertikal (foto kecil di kiri, teks di kanan) supaya semua tahap langsung
                 terlihat tanpa perlu menggeser. Tablet ke atas: kartu foto 3:4 dalam grid. --}}
            <ol class="mt-10 grid gap-5 sm:grid-cols-3 sm:gap-5 lg:grid-cols-5">
                @foreach ($this->stages as $k => $stage)
                    <li class="flex items-center gap-4 sm:block" data-reveal style="--d: {{ $k * 90 }}ms">
                        <div class="relative aspect-square w-[7.5rem] shrink-0 overflow-hidden rounded border-t border-labwhite/20 bg-charcoal sm:aspect-[3/4] sm:w-full">
                            <img src="{{ asset('images/' . $stage['img']) }}" alt="{{ $stage['name'] }}"
                                 width="900" height="1200" loading="lazy" decoding="async"
                                 class="h-full w-full object-cover transition-transform duration-700 ease-out hover:scale-[1.04]">
                            {{-- Vinyet hangat: menggelapkan tepi, menjaga tengah --}}
                            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_45%,transparent_52%,rgba(40,22,8,.5)_100%)]"></div>
                            <span class="krm-readout absolute left-2 top-2 rounded-[6px] bg-charcoal/80 px-1.5 py-0.5 text-[11px] text-labwhite sm:left-3 sm:top-3">
                                {{ str_pad($k + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[1.15rem] font-semibold text-[#1a1208] sm:mt-4">{{ $stage['name'] }}</h3>
                            <p class="mt-1 text-[13.5px] leading-relaxed text-kraftink sm:mt-1.5 sm:text-[13px]">{{ $stage['note'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

</div>
