<div class="mx-auto max-w-[860px] px-6 pb-24 pt-12 md:pt-16" @if($this->order && !in_array($this->order->status,['completed','cancelled'])) wire:poll.10s @endif>
    <h1 class="krm-h2 font-bold" data-reveal>{{ __('Lacak pesanan') }}</h1>

    <form wire:submit="search" class="mt-6 flex gap-2.5">
        <label for="q" class="sr-only">{{ __('Kode pesanan') }}</label>
        <input id="q" wire:model="query" placeholder="BRL-20261016-0001" autocomplete="off"
               class="krm-readout min-w-0 flex-1 rounded border border-kraft/25 bg-transparent px-4 py-3.5 text-[15px] uppercase text-labwhite placeholder:normal-case placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
        <button class="rounded bg-bluelab px-6 font-display text-[15px] font-semibold transition-colors hover:bg-[#234E94]">{{ __('Cari') }}</button>
    </form>

    @if ($code && ! $this->order)
        <p class="mt-8 rounded border border-kraft/25 px-5 py-5 text-[14px] text-kraft/75">{{ __('Pesanan dengan kode itu tidak ditemukan. Cek lagi penulisannya.') }}</p>
    @elseif ($this->order)
        @php
            $o = $this->order;
            $flow = $o->flow();
            $idx = array_search($o->status, $flow, true);
            $times = ['queued' => $o->paid_at, 'roasting' => $o->roasted_at, 'shipped' => $o->shipped_at];
        @endphp
        <div class="mt-8 overflow-hidden rounded border border-kraft/25 bg-surface">
            <div class="flex flex-wrap items-start justify-between gap-4 p-6">
                <div>
                    <p class="krm-readout text-[13px] text-kraft/60">{{ $o->order_code }}</p>
                    <p class="mt-2 font-display text-[1.6rem] font-semibold leading-none">{{ $o->status_label }}</p>
                </div>
                <p class="krm-readout text-[1.4rem]">Rp{{ number_format($o->total, 0, ',', '.') }}</p>
            </div>

            @if ($o->isCod() && ! $o->isPaid() && $o->status !== 'cancelled')
                <p class="border-t border-dashed border-kraft/30 bg-surface px-6 py-4 text-[13.5px] text-kraft/80"><span class="krm-readout mr-2 rounded-[6px] border border-kraft/35 px-1.5 py-0.5 text-[11px]">COD</span>{{ __('Siapkan uang tunai Rp:n saat paket tiba.', ['n' => number_format($o->total, 0, ',', '.')]) }}</p>
            @endif

            @if ($o->status === 'cancelled')
                <p class="border-t border-dashed border-kraft/30 px-6 py-5 text-[14px] text-kraft/70">{{ __('Pesanan ini dibatalkan.') }}</p>
            @elseif ($o->status === 'pending_payment')
                <div class="border-t border-dashed border-kraft/30 bg-surface p-6">
                    <p class="text-[14px] leading-relaxed text-kraft/75">{{ __('Biji untukmu sudah ditahan di gudang. Selesaikan pembayaran supaya masuk antrian sangrai.') }}</p>
                    @if ($this->canManage)
                        @php $gw = \App\Support\Midtrans::enabled(); @endphp
                        @if ($payNotice)<p role="status" class="mt-4 rounded border border-kraft/25 px-4 py-2.5 text-[13px] text-kraft/80">{{ $payNotice }}</p>@endif
                        <div class="mt-5 flex flex-wrap gap-3"
                             @if ($gw) x-data x-on:snap-pay.window="
                                const t = $event.detail.token;
                                const run = () => window.snap.pay(t, { onSuccess: () => $wire.syncPayment(), onPending: () => $wire.syncPayment(), onError: () => $wire.syncPayment(), onClose: () => $wire.syncPayment() });
                                if (window.snap) run(); else { const s = document.createElement('script'); s.src = '{{ \App\Support\Midtrans::snapJsUrl() }}'; s.dataset.clientKey = '{{ \App\Support\Midtrans::clientKey() }}'; s.onload = run; document.head.appendChild(s); }" @endif>
                            @if ($gw)
                                <button wire:click="startPayment" wire:loading.attr="disabled" wire:target="startPayment" class="rounded bg-bluelab px-6 py-3 font-display text-[14.5px] font-semibold transition-colors hover:bg-[#234E94] disabled:opacity-60">{{ __('Bayar sekarang') }}</button>
                                @if ($o->midtrans_order_id)
                                    <button wire:click="syncPayment" wire:loading.attr="disabled" wire:target="syncPayment" class="rounded border border-kraft/30 px-6 py-3 text-[14.5px] text-kraft/80 transition-colors hover:border-kraft/60 disabled:opacity-60">{{ __('Cek status pembayaran') }}</button>
                                @endif
                            @else
                                <button wire:click="pay" class="rounded bg-bluelab px-6 py-3 font-display text-[14.5px] font-semibold transition-colors hover:bg-[#234E94]">{{ __('Bayar sekarang (simulasi)') }}</button>
                            @endif
                            <button wire:click="cancel" wire:confirm="{{ __('Batalkan pesanan ini?') }}" class="rounded border border-kraft/30 px-6 py-3 text-[14.5px] text-kraft/80 transition-colors hover:border-kraft/60">{{ __('Batalkan') }}</button>
                        </div>
                    @else
                        <p class="mt-4 text-[13px] text-kraft/55">{{ __('Masuk dengan akun pemesan untuk membayar.') }}</p>
                    @endif
                </div>
            @else
                <ol class="border-t border-dashed border-kraft/30 bg-surface px-6 py-6">
                    @foreach ($flow as $i => $step)
                        @php $done = $idx !== false && $i <= $idx; $now = $i === $idx; @endphp
                        <li class="relative flex gap-4 pb-6 last:pb-0">
                            @unless ($loop->last)<span class="absolute left-[7px] top-5 h-full w-px {{ $idx !== false && $i < $idx ? 'bg-bluelab' : 'bg-kraft/20' }}"></span>@endunless
                            <span class="relative mt-1 h-[15px] w-[15px] shrink-0 rounded-full border-2 {{ $done ? 'border-bluelab bg-bluelab' : 'border-kraft/30 bg-surface' }}">
                                @if ($now && $step !== 'completed')<span class="absolute -inset-1 animate-ping rounded-full bg-bluelab/40"></span>@endif
                            </span>
                            <div>
                                <p class="text-[14.5px] {{ $done ? 'text-labwhite' : 'text-kraft/45' }} {{ $now ? 'font-semibold' : '' }}">{{ __(\App\Models\Order::STATUS_LABELS[$step]) }}</p>
                                @if (($times[$step] ?? null) && $done)<p class="krm-readout mt-0.5 text-[11.5px] text-kraft/55">{{ $times[$step]->timezone('Asia/Jakarta')->locale(app()->getLocale())->translatedFormat('d M Y, H.i') }}</p>@endif
                                @if ($step === 'shipped' && $o->tracking_number && $done)<p class="krm-readout mt-0.5 text-[12px] text-bluelab">{{ __('Resi') }}: {{ $o->tracking_number }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif

            <div class="border-t border-kraft/15 p-6">
                @foreach ($o->groupedItems() as $item)
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 text-[14px]">
                        <span>{{ $item->coffee_name_snapshot }}</span>
                        <span class="krm-readout text-[13px] text-kraft/70">{{ $item->descr }}{{ $item->qty > 1 ? ($item->descr ? ' × ' : '× ').$item->qty : '' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p class="mt-8 max-w-[52ch] text-[14px] leading-relaxed text-kraft/65">{{ __('Masukkan kode pesanan yang kamu terima setelah checkout.') }}</p>
        @auth
            @php $mine = auth()->user()->orders()->latest()->limit(5)->get(); @endphp
            @if ($mine->isNotEmpty())
                <h2 class="mt-10 text-[13px] text-kraft/55">{{ __('Pesanan terakhirmu') }}</h2>
                <ul class="mt-3 divide-y divide-kraft/15 rounded border border-kraft/20">
                    @foreach ($mine as $m)
                        <li><a href="{{ route('track', $m->order_code) }}" wire:navigate class="flex items-center justify-between gap-4 px-5 py-4 transition-colors hover:bg-labwhite/[.03]">
                            <span class="krm-readout text-[13px]">{{ $m->order_code }}</span>
                            <span class="text-[13px] text-kraft/70">{{ $m->status_label }}</span>
                        </a></li>
                    @endforeach
                </ul>
            @endif
        @endauth
    @endif
</div>
