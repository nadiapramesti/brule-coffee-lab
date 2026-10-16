<div wire:poll.15s>
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
        <div>
            <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Antrian roastery') }}</h1>
            <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Diperbarui otomatis tiap 15 detik') }}</p>
        </div>
        <dl class="flex flex-wrap gap-2 text-[12px]">
            @foreach ([
                [__('Menunggu bayar'), $this->stats['waiting'], ''],
                [__('Produksi'), $this->stats['inProduction'], ''],
                [__('Berat antrian'), number_format($this->stats['grams']), ' g'],
                [__('Dikirim hari ini'), $this->stats['shippedToday'], ''],
            ] as [$l, $v, $u])
                <div class="flex items-baseline gap-2 rounded border border-kraft/20 bg-charcoal px-3 py-1.5">
                    <dt class="text-kraft/55">{{ $l }}</dt>
                    <dd class="krm-readout text-[14px]">{{ $v }}<span class="text-[11px] text-kraft/50">{{ $u }}</span></dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($this->columns as $status => $orders)
            <section class="rounded border border-kraft/20 bg-charcoal">
                <header class="flex items-center justify-between border-b border-kraft/15 px-3 py-2">
                    <h2 class="text-[12.5px] font-semibold">{{ __(\App\Models\Order::STATUS_LABELS[$status]) }}</h2>
                    <span class="krm-readout rounded-[6px] bg-labwhite/[.07] px-1.5 text-[11px]">{{ $orders->count() }}</span>
                </header>
                <div class="space-y-2 p-2">
                    @forelse ($orders as $o)
                        <article wire:key="o-{{ $o->id }}" class="rounded border border-kraft/15 bg-surface px-2.5 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="krm-readout truncate text-[10.5px] text-kraft/50">{{ $o->order_code }}</p>
                                <p class="krm-readout shrink-0 text-[12.5px]">{{ number_format($o->items->sum('grams')) }} g</p>
                            </div>
                            @foreach ($o->groupedItems() as $it)
                                <p class="mt-1 truncate text-[12.5px] font-semibold leading-snug">{{ $it->coffee_name_snapshot }}</p>
                                <p class="truncate text-[11.5px] text-kraft/60">{{ $it->level_label }} · {{ __($it->grind_label) }} · {{ number_format($it->grams) }} g{{ $it->qty > 1 ? ' × '.$it->qty : '' }}</p>
                            @endforeach
                            <p class="mt-0.5 truncate text-[11px] text-kraft/40">{{ $o->recipient_name }}</p>

                            @if ($status === 'packing')
                                <label class="sr-only" for="resi-{{ $o->id }}">{{ __('Nomor resi') }}</label>
                                <div class="mt-2 flex gap-1.5">
                                    <input id="resi-{{ $o->id }}" wire:model="tracking.{{ $o->id }}" placeholder="{{ __('Contoh: JNE1234567890') }}"
                                           class="krm-readout min-w-0 flex-1 rounded border border-kraft/25 bg-transparent px-2 py-1.5 text-[12px] placeholder:text-kraft/30 focus:border-bluelab focus:outline-none focus:ring-1 focus:ring-bluelab/50">
                                    <button wire:click="ship({{ $o->id }})" class="shrink-0 rounded bg-bluelab px-3 text-[12.5px] font-semibold transition-colors hover:bg-[#234E94]">{{ __('Kirim') }}</button>
                                </div>
                                @error("tracking.$o->id")<p class="mt-1 text-[11.5px] text-redtext">{{ $message }}</p>@enderror
                            @else
                                <button wire:click="advance({{ $o->id }})" class="mt-2 w-full rounded border border-bluelab/50 py-1.5 text-[12px] font-semibold transition-colors hover:bg-bluelab">
                                    {{ __(\App\Models\Order::STATUS_LABELS[$o->nextStatus()]) }} &rarr;
                                </button>
                            @endif
                        </article>
                    @empty
                        <p class="py-4 text-center text-[12px] text-kraft/35">{{ __('Kosong') }}</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal px-4 py-3">
        <h2 class="text-[12.5px] font-semibold">{{ __('Stok biji mentah') }}</h2>
        <ul class="mt-2.5 grid gap-x-8 gap-y-2 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->stock as $c)
                @php $pct = min(100, round($c->stock_green_bean_gram / 20000 * 100)); @endphp
                <li>
                    <div class="flex justify-between gap-2 text-[12px]"><span class="truncate">{{ $c->name }}</span><span class="krm-readout shrink-0 text-kraft/65">{{ number_format($c->stock_green_bean_gram) }} g</span></div>
                    <div class="mt-1 h-[3px] rounded-full bg-labwhite/10"><div class="h-[3px] rounded-full {{ $pct < 25 ? 'bg-[#E8782A]' : 'bg-bluelab' }}" style="width: {{ $pct }}%"></div></div>
                </li>
            @endforeach
        </ul>
    </section>
</div>
