<div class="mx-auto max-w-[1280px] px-6 pb-24 pt-12 md:pt-14" wire:poll.15s>
    <p class="krm-readout text-[12px] tracking-[0.18em] text-kraft/60">{{ __('RUANG KONTROL') }}</p>
    <h1 class="krm-h2 mt-2 font-bold">{{ __('Antrian roastery') }}</h1>

    <dl class="mt-8 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            [__('Menunggu bayar'), $this->stats['waiting'], ''],
            [__('Dalam produksi'), $this->stats['inProduction'], ''],
            [__('Total berat antrian'), number_format($this->stats['grams']), ' g'],
            [__('Dikirim hari ini'), $this->stats['shippedToday'], ''],
        ] as [$l, $v, $u])
            <div class="rounded border border-kraft/20 px-4 py-4">
                <dt class="text-[12.5px] text-kraft/60">{{ $l }}</dt>
                <dd class="krm-readout mt-1.5 text-[1.7rem] leading-none">{{ $v }}<span class="text-[13px] text-kraft/50">{{ $u }}</span></dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($this->columns as $status => $orders)
            <section class="rounded border border-kraft/20 bg-[#121212]">
                <header class="flex items-center justify-between border-b border-kraft/15 px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold">{{ __(\App\Models\Order::STATUS_LABELS[$status]) }}</h2>
                    <span class="krm-readout rounded-[6px] bg-white/[.07] px-2 py-0.5 text-[11.5px]">{{ $orders->count() }}</span>
                </header>
                <div class="min-h-[120px] space-y-3 p-3">
                    @forelse ($orders as $o)
                        <article wire:key="o-{{ $o->id }}" class="rounded border border-kraft/20 bg-[#181818] p-3.5">
                            <p class="krm-readout text-[11.5px] text-kraft/55">{{ $o->order_code }}</p>
                            @foreach ($o->items as $it)
                                <p class="mt-2 text-[14px] font-semibold leading-snug">{{ $it->coffee_name_snapshot }}</p>
                                <p class="mt-1 text-[12.5px] text-kraft/70">{{ $it->level_label }} · {{ __($it->grind_label) }}</p>
                                <p class="krm-readout mt-1 text-[1.15rem]">{{ number_format($it->grams) }} g</p>
                            @endforeach
                            <p class="mt-2 truncate text-[12px] text-kraft/50">{{ $o->recipient_name }}</p>

                            @if ($status === 'packing')
                                <label class="sr-only" for="resi-{{ $o->id }}">{{ __('Nomor resi') }}</label>
                                <input id="resi-{{ $o->id }}" wire:model="tracking.{{ $o->id }}" placeholder="{{ __('Contoh: JNE1234567890') }}"
                                       class="krm-readout mt-3 w-full rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13px] focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                                @error("tracking.$o->id")<p class="mt-1 text-[12px] text-red-300">{{ $message }}</p>@enderror
                                <button wire:click="ship({{ $o->id }})" class="mt-2.5 w-full rounded bg-bluelab py-2.5 text-[13.5px] font-semibold transition-colors hover:bg-[#234E94]">{{ __('Kirim') }} &rarr;</button>
                            @else
                                <button wire:click="advance({{ $o->id }})" class="mt-3 w-full rounded border border-bluelab/60 py-2.5 text-[13.5px] font-semibold text-labwhite transition-colors hover:bg-bluelab">
                                    {{ __('Lanjut ke') }} {{ __(\App\Models\Order::STATUS_LABELS[$o->nextStatus()]) }} &rarr;
                                </button>
                            @endif
                        </article>
                    @empty
                        <p class="px-1 py-6 text-center text-[12.5px] text-kraft/40">{{ __('Kosong') }}</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <h2 class="mt-14 text-[1.1rem] font-semibold">{{ __('Stok biji mentah') }}</h2>
    <ul class="mt-4 grid gap-x-10 gap-y-3 sm:grid-cols-2">
        @foreach ($this->stock as $c)
            @php $pct = min(100, round($c->stock_green_bean_gram / 20000 * 100)); @endphp
            <li>
                <div class="flex justify-between text-[13.5px]"><span>{{ $c->name }}</span><span class="krm-readout text-kraft/70">{{ number_format($c->stock_green_bean_gram) }} g</span></div>
                <div class="mt-1.5 h-1 rounded-full bg-white/10"><div class="h-1 rounded-full {{ $pct < 25 ? 'bg-[#E8782A]' : 'bg-bluelab' }}" style="width: {{ $pct }}%"></div></div>
            </li>
        @endforeach
    </ul>
</div>
