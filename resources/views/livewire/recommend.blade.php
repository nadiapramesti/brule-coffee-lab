<div class="mx-auto max-w-[760px] px-6 pb-24 pt-12 md:pt-16">
    <p class="krm-readout text-[11px] tracking-[0.18em] text-kraft/70">{{ __('TEMUKAN KOPIMU') }}</p>
    <h1 class="krm-h2 mt-2 font-bold">{{ __('Bingung pilih biji? Jawab tiga pertanyaan.') }}</h1>
    <p class="mt-3 max-w-[58ch] text-[14.5px] text-kraft/75">{{ __('Kami cocokkan seleramu dengan profil rasa tiap sangrai, lalu kamu tinggal lanjut ke Roast Builder.') }}</p>

    @if ($step <= 3)
        @php $q = $this->questions()[$step]; @endphp
        <section class="mt-10" aria-labelledby="pertanyaan">
            <div class="flex items-center gap-2" role="progressbar" aria-valuemin="1" aria-valuemax="3" aria-valuenow="{{ $step }}" aria-label="{{ __('Langkah :n dari 3', ['n' => $step]) }}">
                @foreach ([1, 2, 3] as $n)
                    <span class="h-1.5 flex-1 rounded-full {{ $n <= $step ? 'bg-bluelab' : 'bg-kraft/20' }}"></span>
                @endforeach
            </div>
            <p class="krm-readout mt-4 text-[12px] text-kraft/70">{{ __('Langkah :n dari 3', ['n' => $step]) }}</p>
            <h2 id="pertanyaan" class="mt-2 font-display text-[22px] font-semibold">{{ $q['title'] }}</h2>

            <div class="mt-6 grid gap-3">
                @foreach ($q['options'] as $value => $label)
                    <button type="button" wire:click="answer('{{ $value }}')" wire:key="opt-{{ $step }}-{{ $value }}"
                            class="rounded border border-kraft/30 bg-surface px-5 py-4 text-left text-[15px] transition-colors hover:border-bluelab hover:bg-bluelab/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-bluelab/60">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($step > 1)
                <button type="button" wire:click="back" class="mt-6 rounded border border-kraft/30 px-5 py-2.5 text-[14px] text-kraft/85 transition-colors hover:border-kraft/60">{{ __('Kembali') }}</button>
            @endif
        </section>
    @else
        <section class="mt-10" aria-live="polite">
            <h2 class="font-display text-[22px] font-semibold">{{ __('Cocok buat kamu') }}</h2>

            @if ($this->results->isEmpty())
                <p class="mt-4 text-[14px] text-kraft/70">{{ __('Belum ada kopi yang tersedia saat ini.') }}</p>
            @else
                <ol class="mt-6 grid gap-4">
                    @foreach ($this->results as $i => $r)
                        <li wire:key="r-{{ $r['coffee']->id }}" class="rounded border {{ $i === 0 ? 'border-bluelab' : 'border-kraft/25' }} bg-surface p-5">
                            @if ($i === 0)<span class="krm-readout rounded-[6px] bg-bluelab px-2 py-0.5 text-[11px] text-white">{{ __('PALING COCOK') }}</span>@endif
                            <h3 class="mt-2 font-display text-[18px] font-semibold">{{ $r['coffee']->name }}</h3>
                            <p class="krm-readout mt-1 text-[12px] text-kraft/70">{{ \App\Models\RoastProfile::LEVELS[$r['profile']->level] }} · Rp {{ number_format($r['profile']->price_per_100g, 0, ',', '.') }} / 100 g</p>
                            @if ($r['why'])<p class="mt-2 text-[14px] text-kraft/80">{{ __('Rasa:') }} {{ $r['why'] }}</p>@endif
                            <a href="{{ route('builder', ['kopi' => $r['coffee']->slug, 'level' => $r['profile']->level]) }}"
                               class="mt-4 inline-block rounded bg-bluelab px-5 py-2.5 font-display text-[14px] font-semibold text-white transition-colors hover:bg-[#234E94]">{{ __('Racik kopi ini') }}</a>
                        </li>
                    @endforeach
                </ol>
            @endif

            <button type="button" wire:click="restart" class="mt-6 rounded border border-kraft/30 px-5 py-2.5 text-[14px] text-kraft/85 transition-colors hover:border-kraft/60">{{ __('Ulangi kuis') }}</button>
        </section>
    @endif
</div>
