<div>

    {{-- ══════════════════ HERO ══════════════════ --}}
    <section class="pt-20 pb-4 md:pt-28">
        <div class="mx-auto max-w-[1180px] px-6">
            <div class="max-w-3xl">
                <h1 class="krm-h1 font-bold">
                    Disangrai setelah<br>pesanan masuk.
                </h1>

                <p class="mt-7 max-w-[54ch] text-[15px] leading-relaxed text-kraft/80">
                    Kami tidak menyimpan kopi yang sudah disangrai. Biji mentahnya
                    menunggu di gudang sampai kamu memilih level sangrai dan
                    ukuran gilingannya — baru mesinnya dinyalakan.
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="{{ route('builder') }}"
                       class="rounded bg-bluelab px-7 py-3.5 font-display text-[15px] font-semibold text-labwhite transition-colors hover:bg-[#234E94]">
                        Racik kopimu
                    </a>
                    <a href="#biji"
                       class="rounded border border-kraft/35 px-7 py-3.5 font-display text-[15px] font-semibold text-kraft transition-colors hover:border-kraft/70 hover:text-labwhite">
                        Lihat biji yang ada
                    </a>
                </div>
            </div>
        </div>

        {{-- Kromatogram: melintas penuh dari tepi ke tepi, persis
             seperti waveform di stempel logo. --}}
        <div class="mt-14"
             x-data="chromatogram(@js($this->peaks))"
             x-init="init()">
            <div x-ref="trace" class="w-full"></div>
        </div>

        {{-- Tiga janji, ditulis sebagai angka yang bisa dicek --}}
        <div class="mx-auto mt-10 max-w-[1180px] px-6">
            <dl class="grid gap-8 border-t border-kraft/15 pt-8 sm:grid-cols-3">
                <div>
                    <dt class="krm-readout text-[1.6rem] font-medium text-labwhite">1,2 kg</dt>
                    <dd class="mt-1.5 text-[13px] leading-relaxed text-kraft/70">
                        Batch terbesar yang kami sangrai sekali jalan.
                    </dd>
                </div>
                <div>
                    <dt class="krm-readout text-[1.6rem] font-medium text-labwhite">1 petani</dt>
                    <dd class="mt-1.5 text-[13px] leading-relaxed text-kraft/70">
                        Tiap lot datang dari satu kebun, bukan campuran.
                    </dd>
                </div>
                <div>
                    <dt class="krm-readout text-[1.6rem] font-medium text-labwhite">24 jam</dt>
                    <dd class="mt-1.5 text-[13px] leading-relaxed text-kraft/70">
                        Jarak dari biji keluar mesin sampai paket berangkat.
                    </dd>
                </div>
            </dl>
        </div>
    </section>


    {{-- ══════════════════ BIJI / LOGBOOK ══════════════════ --}}
    <section id="biji" class="mx-auto max-w-[1180px] px-6 pt-24">
        <h2 class="krm-h2 font-semibold">
            Biji yang sedang ada di gudang
        </h2>
        <p class="mt-3 max-w-[58ch] text-[15px] leading-relaxed text-kraft/75">
            Tiap lot punya catatan asal-usulnya sendiri. Kalau datanya tidak
            lengkap, kopinya tidak kami jual.
        </p>

        <div class="mt-12">
            @foreach ($this->coffees as $bean)
                @php $t = $bean->traceability; @endphp

                <article class="grid gap-7 border-t border-kraft/15 py-9 md:grid-cols-[minmax(0,1fr)_minmax(0,420px)] md:gap-14">

                    <div>
                        <div class="flex items-baseline gap-4">
                            <h3 class="krm-h3 font-semibold">
                                {{ $bean->name }}
                            </h3>
                            <span class="krm-readout shrink-0 text-[13px] text-bluelab">
                                {{ number_format($bean->cupping_score, 1) }}
                            </span>
                        </div>

                        <p class="mt-3 max-w-[56ch] text-[14px] leading-relaxed text-kraft/70">
                            {{ $bean->story }}
                        </p>

                        <p class="mt-5 text-[14px] leading-relaxed text-labwhite/90">
                            {{ $bean->roastProfiles->first()?->tasting_notes
                                ? implode(', ', $bean->roastProfiles->first()->tasting_notes)
                                : '' }}
                        </p>
                    </div>

                    <dl class="grid grid-cols-2 gap-x-8 gap-y-5 self-start text-[13px]">
                        <div>
                            <dt class="text-kraft/55">Ketinggian</dt>
                            <dd class="krm-readout mt-1 text-labwhite">{{ $t?->altitude ? number_format($t->altitude) . " mdpl" : "—" }}</dd>
                        </div>
                        <div>
                            <dt class="text-kraft/55">Proses</dt>
                            <dd class="mt-1 text-labwhite">{{ $t?->process ?? "—" }}</dd>
                        </div>
                        <div>
                            <dt class="text-kraft/55">Varietas</dt>
                            <dd class="mt-1 text-labwhite">{{ $t?->varietal ?? "—" }}</dd>
                        </div>
                        <div>
                            <dt class="text-kraft/55">Panen</dt>
                            <dd class="krm-readout mt-1 text-labwhite">{{ $t->harvest_date?->format('m.Y') }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-kraft/55">Petani</dt>
                            <dd class="mt-1 text-labwhite">{{ $t?->farmer ?? "—" }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-kraft/55">Dinilai oleh</dt>
                            <dd class="mt-1 text-labwhite">{{ $t?->q_grader ?? "—" }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach

            <div class="border-t border-kraft/15"></div>
        </div>
    </section>


    {{-- ══════════════════ TAHAPAN (kertas kraft) ══════════════════ --}}
    <section class="mt-24 bg-kraft py-20 text-charcoal">
        <div class="mx-auto max-w-[1180px] px-6">
            <div class="flex items-start justify-between gap-10">
                <div>
                    <h2 class="krm-h2 max-w-[20ch] font-semibold">
                        Yang terjadi setelah kamu bayar
                    </h2>
                    <p class="mt-3 max-w-[56ch] text-[15px] leading-relaxed text-kraftink">
                        Lima tahap, dan kamu bisa melihat pesananmu ada di tahap mana
                        lewat halaman lacak.
                    </p>
                </div>

                {{-- Stempel versi tinta gelap. Di sinilah tempatnya:
                     dicap di atas kertas, bukan melayang di layar hitam. --}}
                <img src="{{ asset('images/logo-kromatik-dark.png') }}"
                     alt="" width="128" height="128"
                     class="hidden w-[128px] shrink-0 opacity-70 lg:block">
            </div>

            <ol class="mt-14 grid gap-10 border-t border-charcoal/20 pt-8 sm:grid-cols-2 lg:grid-cols-5 lg:gap-6">
                @foreach ($this->stages as $i => $stage)
                    <li class="relative">
                        <span class="krm-readout block text-[12px] text-charcoal/45">
                            {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <h3 class="mt-2 text-[1.15rem] font-semibold">{{ $stage['name'] }}</h3>
                        <p class="mt-2 text-[13px] leading-relaxed text-kraftink">
                            {{ $stage['note'] }}
                        </p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>


    {{-- ══════════════════ PENUTUP ══════════════════ --}}
    <section class="mx-auto max-w-[1180px] px-6 py-24">
        <div class="max-w-[46ch]">
            <h2 class="krm-h2 font-semibold">
                Mulai dari rasa, bukan dari nama kopinya
            </h2>
            <p class="mt-4 text-[15px] leading-relaxed text-kraft/75">
                Geser level sangrainya dan lihat profil rasanya bergerak.
                Kalau sudah ketemu yang kamu mau, pesan di situ juga.
            </p>
            <a href="{{ route('builder') }}"
               class="mt-8 inline-block rounded bg-bluelab px-7 py-3.5 font-display text-[15px] font-semibold text-labwhite transition-colors hover:bg-[#234E94]">
                Buka roast builder
            </a>
        </div>
    </section>
</div>
