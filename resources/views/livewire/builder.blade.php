<div class="mx-auto max-w-[1180px] px-6 py-16">

    <div class="max-w-2xl">
        <h1 class="krm-h2 font-bold">
            Roast builder
        </h1>
        <p class="mt-4 max-w-[56ch] text-[15px] leading-relaxed text-kraft/80">
            Pilih bijinya, lalu geser level sangrainya. Profil rasa di sebelah
            kanan bergerak mengikuti pilihanmu, dan harganya ikut dihitung.
        </p>
    </div>

    <div class="mt-14 grid gap-14 lg:grid-cols-[minmax(0,1fr)_minmax(0,430px)]">

        {{-- ─────────── KONTROL ─────────── --}}
        <div class="space-y-14">

            {{-- Langkah 1 --}}
            <section>
                <h2 class="krm-h3 flex items-baseline gap-3 font-semibold">
                    <span class="krm-readout text-[12px] text-kraft/45">01</span>
                    Pilih biji
                </h2>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach ($this->coffees as $bean)
                        <button type="button"
                                wire:click="selectCoffee({{ $bean->id }})"
                                aria-pressed="{{ $this->coffeeId === $bean->id ? 'true' : 'false' }}"
                                class="rounded border p-4 text-left transition-colors
                                       {{ $this->coffeeId === $bean->id
                                            ? 'border-bluelab bg-bluelab/10'
                                            : 'border-kraft/20 hover:border-kraft/45' }}">
                            <span class="block font-display text-[15px] font-semibold leading-snug">
                                {{ $bean->name }}
                            </span>
                            <span class="mt-2 block text-[12.5px] text-kraft/65">
                                {{ $bean->traceability?->region }}
                            </span>
                            <span class="krm-readout mt-3 block text-[12px] text-bluelab">
                                {{ number_format($bean->cupping_score, 1) }}
                            </span>
                        </button>
                    @endforeach
                </div>

                @if ($this->coffee?->story)
                    <p class="mt-5 max-w-[60ch] border-l-2 border-bluelab/60 pl-4 text-[14px] leading-relaxed text-kraft/75">
                        {{ $this->coffee->story }}
                    </p>
                @endif
            </section>

            {{-- Langkah 2 --}}
            <section>
                <h2 class="krm-h3 flex items-baseline gap-3 font-semibold">
                    <span class="krm-readout text-[12px] text-kraft/45">02</span>
                    Level sangrai
                </h2>

                @if ($this->levels->isNotEmpty())
                    <div class="mt-5 rounded border border-kraft/20 p-6">
                        <div class="flex items-baseline justify-between gap-4">
                            <p class="font-display text-[1.6rem] font-semibold">
                                {{ $this->profile?->level_label }}
                            </p>
                            <p class="krm-readout text-[13px] text-kraft/70">
                                Agtron {{ $this->profile?->agtron }}
                            </p>
                        </div>

                        <label for="roast" class="sr-only">Level sangrai</label>
                        <input id="roast"
                               type="range"
                               min="0"
                               max="{{ $this->levels->count() - 1 }}"
                               step="1"
                               wire:model.live="levelIndex"
                               class="krm-slider mt-5">

                        <div class="mt-3 flex justify-between gap-2 text-[11.5px] text-kraft/55">
                            @foreach ($this->levels as $lv)
                                <span>{{ $lv->level_label }}</span>
                            @endforeach
                        </div>

                        @if ($this->levels->count() < 5)
                            <p class="mt-5 max-w-[52ch] text-[13px] leading-relaxed text-kraft/60">
                                Biji ini berhenti sebelum level paling gelap. Disangrai
                                lebih jauh, karakter aslinya hilang dan yang tersisa
                                cuma rasa gosong.
                            </p>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Langkah 3 --}}
            <section>
                <h2 class="krm-h3 flex items-baseline gap-3 font-semibold">
                    <span class="krm-readout text-[12px] text-kraft/45">03</span>
                    Ukuran gilingan
                </h2>

                <div class="mt-5 flex flex-wrap gap-2.5">
                    @foreach ($this->grindOptions as $key => $label)
                        <button type="button"
                                wire:click="setGrind('{{ $key }}')"
                                aria-pressed="{{ $grind === $key ? 'true' : 'false' }}"
                                class="rounded border px-4 py-2.5 text-[13.5px] transition-colors
                                       {{ $grind === $key
                                            ? 'border-bluelab bg-bluelab/10 text-labwhite'
                                            : 'border-kraft/20 text-kraft/80 hover:border-kraft/45' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- Langkah 4 --}}
            <section>
                <h2 class="krm-h3 flex items-baseline gap-3 font-semibold">
                    <span class="krm-readout text-[12px] text-kraft/45">04</span>
                    Berat
                </h2>

                <div class="mt-5 grid max-w-md grid-cols-3 gap-3">
                    @foreach ([200, 500, 1000] as $g)
                        <button type="button"
                                wire:click="setGrams({{ $g }})"
                                aria-pressed="{{ $grams === $g ? 'true' : 'false' }}"
                                class="rounded border py-5 transition-colors
                                       {{ $grams === $g
                                            ? 'border-bluelab bg-bluelab/10'
                                            : 'border-kraft/20 hover:border-kraft/45' }}">
                            <span class="krm-readout block text-[1.3rem]">
                                {{ $g >= 1000 ? '1' : $g }}
                            </span>
                            <span class="mt-0.5 block text-[12px] text-kraft/65">
                                {{ $g >= 1000 ? 'kilogram' : 'gram' }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ─────────── PANEL RASA ─────────── --}}
        <div class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded border border-kraft/20 p-6">

                <h2 class="text-[1.1rem] font-semibold">Profil rasa</h2>
                <p class="mt-1 text-[13px] text-kraft/60">
                    Bergerak tiap kali pilihanmu berubah.
                </p>

                {{-- wire:ignore: isi div ini digambar D3, bukan Blade.
                     Nilai barunya dikirim lewat event, bukan render ulang. --}}
                <div wire:ignore
                     class="mt-6"
                     x-data="flavorWheel(@js($this->flavorValues))"
                     x-init="init()">
                    <div x-ref="wheel" class="flex justify-center"></div>
                </div>

                @if ($this->profile?->tasting_notes)
                    <p class="mt-7 border-t border-kraft/15 pt-5 text-[14px] leading-relaxed">
                        {{ implode(', ', $this->profile->tasting_notes) }}
                    </p>
                @endif

                <dl class="krm-settling mt-6 space-y-2.5 border-t border-kraft/15 pt-5 text-[13px]"
                    wire:loading.delay.class="opacity-50">
                    <div class="flex justify-between gap-4">
                        <dt class="text-kraft/60">Biji</dt>
                        <dd class="text-right">{{ $this->coffee?->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-kraft/60">Sangrai</dt>
                        <dd class="text-right">{{ $this->profile?->level_label }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-kraft/60">Gilingan</dt>
                        <dd class="text-right">{{ $this->grindOptions[$grind] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-kraft/60">Berat</dt>
                        <dd class="krm-readout text-right">{{ number_format($grams) }} g</dd>
                    </div>
                </dl>

                <div class="krm-settling mt-5 flex items-end justify-between gap-4 border-t border-kraft/15 pt-5"
                     wire:loading.delay.class="opacity-50">
                    <span class="text-[13px] text-kraft/60">Total</span>
                    <span class="krm-readout text-[1.75rem] font-medium">
                        Rp{{ number_format($this->price, 0, ',', '.') }}
                    </span>
                </div>

                @if ($this->stockOk)
                    <button type="button"
                            class="mt-6 w-full rounded bg-bluelab py-4 font-display text-[15px] font-semibold transition-colors hover:bg-[#234E94]">
                        Pesan dan sangrai
                    </button>
                    <p class="mt-3 text-center text-[12px] leading-relaxed text-kraft/55">
                        Disangrai setelah pesanan masuk. Berangkat dalam 24 jam.
                    </p>
                @else
                    <p class="mt-6 rounded border border-kraft/30 px-4 py-4 text-center text-[13px] leading-relaxed text-kraft/70">
                        Stok biji mentahnya tidak cukup untuk berat ini.
                        Coba ukuran yang lebih kecil.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
