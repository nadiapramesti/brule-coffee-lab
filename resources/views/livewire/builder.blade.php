<div class="mx-auto max-w-7xl px-5 py-12">

    {{-- ===== Judul halaman ===== --}}
    <div class="mb-10 flex items-end justify-between gap-6 border-b border-[#D8C5A6]/15 pb-6">
        <div>
            <p class="text-[11px] tracking-[0.3em] text-[#2A5DB0]">LAB / 01</p>
            <h1 class="krm-display mt-2 text-4xl font-bold tracking-wide md:text-5xl">
                ROAST BUILDER
            </h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-[#D8C5A6]/80">
                Racik sendiri profil kopimu. Setiap perubahan langsung
                terbaca di kromatogram rasa di sebelah kanan.
            </p>
        </div>
        <div class="hidden text-right md:block">
            <p class="text-[11px] tracking-[0.2em] text-[#D8C5A6]/60">CUPPING SCORE</p>
            <p class="krm-display text-4xl font-bold text-[#2A5DB0]">
                {{ number_format($this->coffee?->cupping_score ?? 0, 1) }}
            </p>
        </div>
    </div>

    <div class="grid gap-10 lg:grid-cols-[1fr_minmax(0,460px)]">

        {{-- ================= KIRI : KONTROL ================= --}}
        <div class="space-y-10">

            {{-- 1. Pilih biji --}}
            <section>
                <div class="mb-4 flex items-center gap-3">
                    <span class="krm-display text-xs tracking-[0.25em] text-[#2A5DB0]">01</span>
                    <h2 class="krm-display text-lg tracking-[0.18em]">PILIH BIJI</h2>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($this->coffees as $bean)
                        <button type="button"
                                wire:click="selectCoffee({{ $bean->id }})"
                                class="group rounded-sm border p-4 text-left transition
                                       {{ $this->coffeeId === $bean->id
                                            ? 'border-[#2A5DB0] bg-[#2A5DB0]/10'
                                            : 'border-[#D8C5A6]/20 hover:border-[#D8C5A6]/50' }}">
                            <p class="krm-display text-sm leading-snug tracking-wide">
                                {{ $bean->name }}
                            </p>
                            <p class="mt-2 text-[11px] text-[#D8C5A6]/70">
                                {{ $bean->traceability?->region }}
                            </p>
                            <p class="mt-3 text-[11px] tracking-[0.1em] text-[#2A5DB0]">
                                {{ number_format($bean->cupping_score, 1) }} PTS
                            </p>
                        </button>
                    @endforeach
                </div>

                @if ($this->coffee?->story)
                    <p class="mt-4 border-l-2 border-[#2A5DB0]/50 pl-4 text-sm leading-relaxed text-[#D8C5A6]/75">
                        {{ $this->coffee->story }}
                    </p>
                @endif
            </section>

            {{-- 2. Level sangrai --}}
            <section>
                <div class="mb-4 flex items-center gap-3">
                    <span class="krm-display text-xs tracking-[0.25em] text-[#2A5DB0]">02</span>
                    <h2 class="krm-display text-lg tracking-[0.18em]">LEVEL SANGRAI</h2>
                </div>

                @if ($this->levels->isNotEmpty())
                    <div class="rounded-sm border border-[#D8C5A6]/20 p-5">
                        <div class="mb-5 flex items-baseline justify-between">
                            <p class="krm-display text-2xl tracking-wide">
                                {{ $this->profile?->level_label }}
                            </p>
                            <p class="text-[11px] tracking-[0.15em] text-[#D8C5A6]/70">
                                AGTRON {{ $this->profile?->agtron }}
                            </p>
                        </div>

                        <input type="range"
                               min="0"
                               max="{{ $this->levels->count() - 1 }}"
                               step="1"
                               wire:model.live="levelIndex"
                               class="h-1 w-full cursor-pointer appearance-none rounded-full
                                      bg-gradient-to-r from-[#D8C5A6] to-[#4A2C18]
                                      accent-[#2A5DB0]">

                        <div class="mt-3 flex justify-between text-[10px] tracking-[0.12em] text-[#D8C5A6]/60">
                            @foreach ($this->levels as $lv)
                                <span>{{ strtoupper(str_replace('_', ' ', $lv->level)) }}</span>
                            @endforeach
                        </div>

                        @if ($this->levels->count() < 5)
                            <p class="mt-4 text-[11px] leading-relaxed text-[#D8C5A6]/55">
                                Biji ini tidak disangrai sampai level paling gelap —
                                karakter aslinya akan hilang.
                            </p>
                        @endif
                    </div>
                @endif
            </section>

            {{-- 3. Gilingan --}}
            <section>
                <div class="mb-4 flex items-center gap-3">
                    <span class="krm-display text-xs tracking-[0.25em] text-[#2A5DB0]">03</span>
                    <h2 class="krm-display text-lg tracking-[0.18em]">GILINGAN</h2>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($this->grindOptions as $key => $label)
                        <button type="button"
                                wire:click="setGrind('{{ $key }}')"
                                class="rounded-sm border px-4 py-2 text-xs tracking-[0.1em] transition
                                       {{ $grind === $key
                                            ? 'border-[#2A5DB0] bg-[#2A5DB0]/15 text-[#FFFBF2]'
                                            : 'border-[#D8C5A6]/20 text-[#D8C5A6]/80 hover:border-[#D8C5A6]/50' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- 4. Berat --}}
            <section>
                <div class="mb-4 flex items-center gap-3">
                    <span class="krm-display text-xs tracking-[0.25em] text-[#2A5DB0]">04</span>
                    <h2 class="krm-display text-lg tracking-[0.18em]">BERAT</h2>
                </div>

                <div class="grid max-w-md grid-cols-3 gap-3">
                    @foreach ([200, 500, 1000] as $g)
                        <button type="button"
                                wire:click="setGrams({{ $g }})"
                                class="rounded-sm border py-4 transition
                                       {{ $grams === $g
                                            ? 'border-[#2A5DB0] bg-[#2A5DB0]/15'
                                            : 'border-[#D8C5A6]/20 hover:border-[#D8C5A6]/50' }}">
                            <span class="krm-display block text-xl">
                                {{ $g >= 1000 ? '1' : $g }}
                            </span>
                            <span class="text-[10px] tracking-[0.15em] text-[#D8C5A6]/70">
                                {{ $g >= 1000 ? 'KILOGRAM' : 'GRAM' }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ================= KANAN : WHEEL + RINGKASAN ================= --}}
        <div class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-sm border border-[#D8C5A6]/20 bg-[#121212]/70 p-6">

                <div class="mb-5 flex items-center justify-between">
                    <p class="text-[11px] tracking-[0.25em] text-[#D8C5A6]/70">
                        KROMATOGRAM RASA
                    </p>
                    <span class="flex items-center gap-2 text-[10px] tracking-[0.2em] text-[#2A5DB0]">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#2A5DB0]"></span>
                        LIVE
                    </span>
                </div>

                {{-- wire:ignore penting: biar D3 yang pegang isi div ini,
                     bukan Livewire. Update nilai dikirim lewat event. --}}
                <div wire:ignore
                     x-data="flavorWheel(@js($this->flavorValues))"
                     x-init="init()">
                    <div x-ref="wheel" class="flex justify-center"></div>
                </div>

                {{-- Tasting notes --}}
                <div class="mt-6 border-t border-[#D8C5A6]/15 pt-5">
                    <p class="mb-3 text-[11px] tracking-[0.2em] text-[#D8C5A6]/70">
                        ESTIMATED PROFILE
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->profile?->tasting_notes ?? [] as $note)
                            <span class="rounded-full border border-[#2A5DB0]/50 bg-[#2A5DB0]/10 px-3 py-1 text-[11px] tracking-wide">
                                {{ $note }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Ringkasan pesanan --}}
                <div class="mt-6 space-y-2 border-t border-[#D8C5A6]/15 pt-5 text-xs">
                    <div class="flex justify-between text-[#D8C5A6]/75">
                        <span>Biji</span>
                        <span class="text-[#FFFBF2]">{{ $this->coffee?->name }}</span>
                    </div>
                    <div class="flex justify-between text-[#D8C5A6]/75">
                        <span>Sangrai</span>
                        <span class="text-[#FFFBF2]">{{ $this->profile?->level_label }}</span>
                    </div>
                    <div class="flex justify-between text-[#D8C5A6]/75">
                        <span>Gilingan</span>
                        <span class="text-[#FFFBF2]">{{ $this->grindOptions[$grind] }}</span>
                    </div>
                    <div class="flex justify-between text-[#D8C5A6]/75">
                        <span>Berat</span>
                        <span class="text-[#FFFBF2]">{{ number_format($grams) }} gram</span>
                    </div>
                </div>

                {{-- Harga --}}
                <div class="mt-5 flex items-end justify-between border-t border-[#D8C5A6]/15 pt-5">
                    <span class="text-[11px] tracking-[0.2em] text-[#D8C5A6]/70">TOTAL</span>
                    <span class="krm-display text-3xl font-bold text-[#FFFBF2]">
                        Rp {{ number_format($this->price, 0, ',', '.') }}
                    </span>
                </div>

                @if ($this->stockOk)
                    <button type="button"
                            class="krm-display mt-5 w-full rounded-sm bg-[#2A5DB0] py-4 text-sm font-semibold tracking-[0.2em] transition hover:bg-[#2A5DB0]/85">
                        PESAN & SANGRAI
                    </button>
                    <p class="mt-3 text-center text-[10px] tracking-[0.12em] text-[#D8C5A6]/55">
                        DISANGRAI SETELAH PESANAN MASUK &middot; KIRIM DALAM 24 JAM
                    </p>
                @else
                    <div class="mt-5 rounded-sm border border-[#D8C5A6]/30 py-4 text-center text-xs tracking-[0.15em] text-[#D8C5A6]/70">
                        STOK GREEN BEAN TIDAK CUKUP
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
