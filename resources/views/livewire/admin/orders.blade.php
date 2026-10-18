@php
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $orders = $this->orders;
    $o = $this->current;
    $exportQuery = array_filter(['tab' => $tab, 'q' => $search, 'from' => $from, 'to' => $to]);
    $inp = 'rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
@endphp
<div>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Pesanan') }}</h1>
            <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Semua pesanan toko. Klik baris untuk melihat detail dan mengubah status.') }}</p>
        </div>
        <x-download-menu :xlsx="route('admin.orders.export', $exportQuery + ['format' => 'xlsx'])" :pdf="route('admin.orders.export', $exportQuery + ['format' => 'pdf'])" />
    </div>

    @if ($notice)
        <p role="status" x-data x-init="setTimeout(() => $el.remove(), 4000)" class="mt-4 rounded border border-bluelab/50 bg-bluelab/10 px-4 py-2.5 text-[13px]">{{ $notice }}</p>
    @endif

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        {{-- Filter --}}
        <div class="grid gap-3 px-4 pt-4 sm:flex sm:flex-wrap sm:items-end">
            <div class="relative min-w-[220px] flex-1 sm:max-w-sm">
                <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-kraft/45" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4-4"/></svg>
                <label class="sr-only" for="q">{{ __('Cari pesanan') }}</label>
                <input id="q" type="search" wire:model.live.debounce.350ms="search" placeholder="{{ __('Kode, nama, telepon, atau resi') }}" class="{{ $inp }} w-full pl-9">
            </div>
            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 sm:flex sm:flex-wrap">
                <label class="sr-only" for="from">{{ __('Dari tanggal') }}</label>
                <input id="from" type="date" wire:model.live="from" class="{{ $inp }} krm-readout w-full min-w-0 max-sm:px-2 max-sm:text-[12.5px] sm:w-auto" style="color-scheme: dark">
                <span class="text-kraft/40" aria-hidden="true">–</span>
                <label class="sr-only" for="to">{{ __('Sampai tanggal') }}</label>
                <input id="to" type="date" wire:model.live="to" class="{{ $inp }} krm-readout w-full min-w-0 max-sm:px-2 max-sm:text-[12.5px] sm:w-auto" style="color-scheme: dark">
            </div>
            <div class="grid grid-cols-3 gap-1.5 sm:flex sm:gap-1">
                @foreach (['today' => __('Hari ini'), '7' => '7 ' . __('hari'), '30' => '30 ' . __('hari')] as $k => $l)
                    <button type="button" wire:click="preset('{{ $k }}')" class="whitespace-nowrap rounded border border-kraft/25 px-2.5 py-2 text-center text-[12px] text-kraft/75 transition-colors hover:border-kraft/60 hover:text-labwhite">{{ $l }}</button>
                @endforeach
                @if ($from || $to)<button type="button" wire:click="preset('all')" class="px-2 py-2 text-[12px] text-kraft/55 underline-offset-4 hover:underline max-sm:col-span-3">{{ __('Reset') }}</button>@endif
            </div>
        </div>

        <div class="mt-3 flex gap-x-1 overflow-x-auto border-b border-kraft/15 px-3 [scrollbar-width:none] max-sm:pb-px sm:flex-wrap sm:overflow-visible [&::-webkit-scrollbar]:hidden" role="tablist">
            @foreach (\App\Models\Order::TABS as $key => [$label])
                <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="setTab('{{ $key }}')"
                        class="-mb-px flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-[13px] transition-colors {{ $tab === $key ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">
                    {{ __($label) }}<span class="krm-readout rounded-[6px] bg-labwhite/[.07] px-1.5 text-[11px]">{{ $this->tabCounts[$key] }}</span>
                </button>
            @endforeach
        </div>

        <div wire:loading.class="opacity-60" class="transition-opacity">
            <table class="hidden w-full text-left text-[13.5px] md:table">
                <thead class="krm-readout text-[11px] uppercase tracking-[0.12em] text-kraft/45">
                    <tr class="border-b border-kraft/10"><th class="px-4 py-2.5 font-normal">{{ __('Kode') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Pelanggan') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Item') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Waktu') }}</th><th class="px-3 py-2.5 text-right font-normal">{{ __('Total') }}</th><th class="px-4 py-2.5 font-normal">{{ __('Status') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-kraft/10">
                    @foreach ($orders as $row)
                        <tr wire:key="r-{{ $row->id }}" wire:click="show('{{ $row->order_code }}')" class="cursor-pointer transition-colors hover:bg-labwhite/[.03] {{ $open === $row->order_code ? 'bg-bluelab/10' : '' }}">
                            <td class="krm-readout whitespace-nowrap px-4 py-3 text-[12.5px]">{{ $row->order_code }}@if ($row->hasAddons()) <span class="ml-1.5 rounded-[6px] border border-bluelab/50 px-1.5 py-px font-sans text-[10.5px] text-bluelab" title="{{ $row->addonLabel() }}">{{ __('Hadiah') }}</span>@endif</td>
                            <td class="max-w-[170px] truncate px-3 py-3">{{ $row->user?->name ?? $row->recipient_name }}</td>
                            <td class="max-w-[260px] truncate px-3 py-3 text-kraft/65">{{ $row->groupedItems()->map(fn ($i) => $i->short)->implode(', ') }}</td>
                            <td class="krm-readout whitespace-nowrap px-3 py-3 text-[12px] text-kraft/55">{{ $row->created_at->translatedFormat('j M, H:i') }}</td>
                            <td class="krm-readout whitespace-nowrap px-3 py-3 text-right">{{ $rp($row->total) }}</td>
                            <td class="px-4 py-3"><span class="whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11.5px] {{ $row->statusTone() }}">{{ $row->status_label }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="divide-y divide-kraft/10 md:hidden">
                @foreach ($orders as $row)
                    <li wire:key="m-{{ $row->id }}">
                        <button type="button" wire:click="show('{{ $row->order_code }}')" class="block w-full px-4 py-3.5 text-left">
                            <div class="flex items-center justify-between gap-3">
                                <span class="krm-readout text-[12.5px]">{{ $row->order_code }}</span>
                                <span class="rounded-[6px] border px-2 py-0.5 text-[11.5px] {{ $row->statusTone() }}">{{ $row->status_label }}</span>
                            </div>
                            <p class="mt-1 truncate text-[13.5px]">{{ $row->user?->name ?? $row->recipient_name }}</p>
                            <div class="mt-0.5 flex justify-between text-[12px] text-kraft/55"><span>{{ $row->created_at->translatedFormat('j M, H:i') }}</span><span class="krm-readout text-labwhite">{{ $rp($row->total) }}</span></div>
                        </button>
                    </li>
                @endforeach
            </ul>

            @if ($orders->isEmpty())
                <p class="px-5 py-12 text-center text-[13px] text-kraft/45">{{ __('Tidak ada pesanan yang cocok dengan filter ini.') }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-kraft/15 px-4 py-3 text-[12.5px] text-kraft/60">
            <span>{{ $orders->total() ? __('Menampilkan :a–:b dari :t', ['a' => $orders->firstItem(), 'b' => $orders->lastItem(), 't' => $orders->total()]) . ' · ' . __('Nilai') . ' ' . $rp($this->sum) : '' }}</span>
            <div class="flex items-center gap-2">
                <button wire:click="previousPage" @disabled($orders->onFirstPage()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Sebelumnya') }}">&larr;</button>
                <span class="krm-readout">{{ $orders->currentPage() }}/{{ $orders->lastPage() }}</span>
                <button wire:click="nextPage" @disabled(! $orders->hasMorePages()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Berikutnya') }}">&rarr;</button>
            </div>
        </div>
    </section>

    {{-- Panel detail (slide-over) --}}
    @if ($o)
        <div class="fixed inset-0 z-[70] flex justify-end"
             x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             @keydown.escape.window="$wire.close()">
            <div class="krm-fade absolute inset-0 bg-black/60 backdrop-blur-[2px]" wire:click="close"></div>
            <aside role="dialog" aria-modal="true" aria-labelledby="od-title" class="krm-slide relative flex h-full w-full max-w-[540px] flex-col border-l border-kraft/25 bg-surface shadow-[-30px_0_80px_-20px_rgb(var(--shadow)/.9)]">
                <header class="flex shrink-0 items-start justify-between gap-3 border-b border-kraft/15 px-5 py-4">
                    <div class="min-w-0">
                        <p class="krm-readout text-[11px] uppercase tracking-[0.16em] text-kraft/50">{{ __('Pesanan') }}</p>
                        <h2 id="od-title" class="krm-readout text-[1.15rem] font-semibold">{{ $o->order_code }}</h2>
                        <p class="mt-0.5 text-[12px] text-kraft/55">{{ $o->created_at->translatedFormat('j F Y, H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-[6px] border px-2 py-0.5 text-[11.5px] {{ $o->statusTone() }}">{{ $o->status_label }}</span>
                        <button type="button" wire:click="close" class="-mr-2 grid h-9 w-9 place-items-center rounded text-kraft/70 hover:bg-labwhite/[.06] hover:text-labwhite" aria-label="{{ __('Tutup') }}">
                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg>
                        </button>
                    </div>
                </header>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain px-5 py-5">
                    {{-- Tahapan --}}
                    @if ($o->status === 'cancelled')
                        <p class="rounded border border-kraft/25 bg-labwhite/[.03] px-4 py-3 text-[13px] text-kraft/70">{{ __('Dibatalkan') }} {{ $o->cancelled_at?->translatedFormat('j M Y, H:i') }}. {{ __('Stok biji dikembalikan.') }}</p>
                    @else
                        @php
                            $flow = ['pending_payment' => $o->created_at] + collect($o->flow())->mapWithKeys(fn ($s) => [$s => match ($s) { 'queued' => $o->paid_at, 'roasting' => $o->roasted_at, 'shipped' => $o->shipped_at, 'completed' => $o->completed_at, default => null }])->all();
                            $order = array_keys($flow); $idx = array_search($o->status, $order, true);
                        @endphp
                        <ol class="flex items-start" aria-label="{{ __('Tahapan pesanan') }}">
                            @foreach ($flow as $s => $at)
                                @php $done = array_search($s, $order, true) <= $idx; @endphp
                                <li class="relative flex-1 text-center">
                                    @unless ($loop->first)<span class="absolute right-1/2 top-[7px] h-px w-full {{ $done ? 'bg-bluelab' : 'bg-kraft/20' }}"></span>@endunless
                                    <span class="relative mx-auto block h-[15px] w-[15px] rounded-full border-2 {{ $done ? 'border-bluelab bg-bluelab' : 'border-kraft/30 bg-surface' }} {{ $s === $o->status ? 'ring-4 ring-bluelab/25' : '' }}"></span>
                                    <span class="mt-1.5 block text-[10px] leading-tight {{ $done ? 'text-labwhite' : 'text-kraft/40' }}">{{ __(\App\Models\Order::STATUS_LABELS[$s]) }}</span>
                                    @if ($at)<span class="krm-readout block text-[9.5px] text-kraft/45">{{ $at->translatedFormat('j M H:i') }}</span>@endif
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    {{-- Aksi --}}
                    <section class="rounded border border-kraft/20 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-[12.5px] font-semibold">{{ __('Tindakan') }}</h3>
                            <span class="krm-readout rounded-[6px] border px-2 py-0.5 text-[11px] {{ $o->isCod() ? 'border-[#E8782A]/50 text-ambertext' : 'border-kraft/30 text-kraft/70' }}">{{ $o->isCod() ? 'COD' : __('Online') }} · {{ $o->isPaid() ? __('Lunas') : __('Belum lunas') }}</span>
                        </div>
                        <div class="mt-3 space-y-3">
                            @if ($o->status === 'pending_payment')
                                <button wire:click="markPaid" class="w-full rounded bg-bluelab py-2.5 text-[13.5px] font-semibold transition-colors hover:bg-[#234E94]">{{ __('Tandai sudah dibayar') }}</button>
                            @elseif (in_array($o->status, ['queued', 'roasting', 'grinding']))
                                <button wire:click="advance" class="w-full rounded bg-bluelab py-2.5 text-[13.5px] font-semibold transition-colors hover:bg-[#234E94]">{{ __('Lanjut ke') }} {{ __(\App\Models\Order::STATUS_LABELS[$o->nextStatus()]) }} &rarr;</button>
                            @elseif ($o->status === 'packing')
                                <div class="flex gap-2">
                                    <label class="sr-only" for="resi">{{ __('Nomor resi') }}</label>
                                    <input id="resi" wire:model="tracking" wire:keydown.enter="ship" placeholder="{{ __('Contoh: JNE1234567890') }}" class="{{ $inp }} krm-readout min-w-0 flex-1">
                                    <button wire:click="ship" class="shrink-0 rounded bg-bluelab px-4 text-[13.5px] font-semibold hover:bg-[#234E94]">{{ __('Kirim') }}</button>
                                </div>
                                @error('tracking')<p class="text-[12px] text-redtext">{{ $message }}</p>@enderror
                            @elseif ($o->status === 'shipped')
                                <button wire:click="complete" class="w-full rounded bg-bluelab py-2.5 text-[13.5px] font-semibold transition-colors hover:bg-[#234E94]">{{ $o->isCod() && ! $o->isPaid() ? __('Tandai selesai (paket diterima, uang COD masuk)') : __('Tandai selesai (sudah diterima)') }}</button>
                            @else
                                <p class="text-[13px] text-kraft/55">{{ __('Tidak ada tindakan untuk status ini.') }}</p>
                            @endif

                            @if (in_array($o->status, ['pending_payment', 'queued']))
                                @if (! $confirmCancel)
                                    <button wire:click="$set('confirmCancel', true)" class="w-full rounded border border-redtext/30 py-2 text-[13px] text-redtext transition-colors hover:bg-redtext/10">{{ __('Batalkan pesanan') }}</button>
                                @else
                                    <div class="rounded border border-redtext/30 bg-redtext/[.05] p-3">
                                        <label for="why" class="text-[12.5px] text-kraft/75">{{ __('Alasan pembatalan') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                                        <input id="why" wire:model="cancelReason" placeholder="{{ __('Contoh: stok biji habis / permintaan pembeli') }}" class="{{ $inp }} mt-1.5 w-full">
                                        <div class="mt-2.5 flex gap-2">
                                            <button wire:click="cancel" class="flex-1 rounded bg-redtext/80 py-2 text-[13px] font-semibold hover:bg-red-500">{{ __('Ya, batalkan & kembalikan stok') }}</button>
                                            <button wire:click="$set('confirmCancel', false)" class="rounded px-3 text-[13px] text-kraft/70 hover:text-labwhite">{{ __('Batal') }}</button>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </section>

                    {{-- Pelanggan & pengiriman --}}
                    <section class="rounded border border-kraft/20 p-4 text-[13.5px]">
                        <h3 class="text-[12.5px] font-semibold">{{ __('Penerima & pengiriman') }}</h3>
                        <p class="mt-2.5 font-semibold">{{ $o->recipient_name }}</p>
                        <p class="krm-readout text-[12.5px] text-kraft/70">{{ $o->recipient_phone }}
                            <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $o->recipient_phone)) }}" target="_blank" rel="noopener" class="ml-2 font-sans text-[12px] text-bluetext underline-offset-4 hover:underline">WhatsApp ↗</a></p>
                        <p class="mt-2 whitespace-pre-line text-kraft/80">{{ $o->shipping_address }}</p>
                        @if ($o->user)<p class="mt-3 border-t border-kraft/10 pt-2.5 text-[12px] text-kraft/55">{{ __('Akun') }}: {{ $o->user->name }} · {{ $o->user->email }}</p>@endif
                        @if ($o->tracking_number)<p class="mt-1 text-[12px] text-kraft/55">{{ __('Resi') }}: <span class="krm-readout text-labwhite">{{ $o->tracking_number }}</span></p>@endif
                    </section>

                    {{-- Item --}}
                    <section class="rounded border border-kraft/20">
                        <h3 class="px-4 pt-4 text-[12.5px] font-semibold">{{ __('Item') }}</h3>
                        <ul class="divide-y divide-kraft/10 px-4">
                            @foreach ($o->groupedItems() as $it)
                                <li class="flex items-start justify-between gap-4 py-3">
                                    <div class="min-w-0">
                                        <p class="text-[13.5px] font-semibold">{{ $it->coffee_name_snapshot }}</p>
                                        <p class="text-[12px] text-kraft/60">{{ $it->descr }}@if ($it->qty > 1)<span class="krm-readout">{{ $it->descr ? ' · ' : '' }}× {{ $it->qty }}</span>@endif</p>
                                    </div>
                                    <span class="krm-readout shrink-0 text-[13px]">{{ $rp($it->line_total) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <dl class="space-y-1.5 border-t border-kraft/15 px-4 py-3 text-[13px]">
                            <div class="flex justify-between text-kraft/65"><dt>{{ __('Subtotal') }}</dt><dd class="krm-readout">{{ $rp($o->subtotal) }}</dd></div>
                            @if ($o->discount ?? 0)<div class="flex justify-between text-kraft/65"><dt>{{ __('Diskon') }}@if ($o->voucher_code) ({{ $o->voucher_code }})@endif</dt><dd class="krm-readout">- {{ $rp($o->discount) }}</dd></div>@endif
                            <div class="flex justify-between text-kraft/65"><dt>{{ __('Ongkir') }}</dt><dd class="krm-readout">{{ $o->shipping_cost ? $rp($o->shipping_cost) : __('Gratis') }}</dd></div>
                            @if ($o->addon_cost)<div class="flex justify-between text-kraft/65"><dt>{{ __('Tambahan') }} ({{ $o->addonLabel() }})</dt><dd class="krm-readout">{{ $rp($o->addon_cost) }}</dd></div>@endif
                            <div class="flex justify-between border-t border-kraft/10 pt-2 text-[14px] font-semibold"><dt>{{ __('Total') }}</dt><dd class="krm-readout">{{ $rp($o->total) }}</dd></div>
                        </dl>
                    </section>

                    @if ($o->hasAddons())
                        <section class="rounded border border-bluelab/40 bg-bluelab/[.07] p-4">
                            <h3 class="text-[12.5px] font-semibold">{{ __('Untuk tim roastery') }}</h3>
                            <ul class="mt-2 space-y-1.5 text-[13px]">
                                @if ($o->gift_wrap)<li>{{ __('Pakai kemasan hadiah (kotak kraft + pita).') }}</li>@endif
                                @if (filled($o->card_message))<li>{{ __('Selipkan kartu ucapan:') }} <span class="italic">&ldquo;{{ $o->card_message }}&rdquo;</span></li>@endif
                            </ul>
                        </section>
                    @endif

                    {{-- Catatan --}}
                    <section class="rounded border border-kraft/20 p-4">
                        <label for="note" class="text-[12.5px] font-semibold">{{ __('Catatan internal') }}</label>
                        <textarea id="note" rows="2" wire:model="note" placeholder="{{ __('Contoh: pembeli minta digiling lebih halus, hubungi sebelum kirim') }}" class="{{ $inp }} mt-2 w-full"></textarea>
                        @error('note')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                        <button wire:click="saveNote" class="mt-2 rounded border border-kraft/30 px-3.5 py-1.5 text-[12.5px] transition-colors hover:border-kraft/70">{{ __('Simpan catatan') }}</button>
                    </section>

                    {{-- Riwayat --}}
                    <section>
                        <h3 class="text-[12.5px] font-semibold">{{ __('Riwayat aktivitas') }}</h3>
                        <ul class="mt-3 space-y-3 border-l border-kraft/20 pl-4">
                            @forelse ($o->logs as $log)
                                <li class="relative text-[12.5px]">
                                    <span class="absolute -left-[21px] top-1.5 h-2 w-2 rounded-full bg-bluelab"></span>
                                    <p class="text-kraft/90">{{ $log->text() }}</p>
                                    <p class="text-[11.5px] text-kraft/45">{{ $log->created_at->translatedFormat('j M Y, H:i') }}@if ($log->user) · {{ $log->user->name }}@endif</p>
                                </li>
                            @empty
                                <li class="text-[12.5px] text-kraft/40">{{ __('Belum ada catatan aktivitas.') }}</li>
                            @endforelse
                        </ul>
                    </section>
                </div>

                <footer class="flex shrink-0 items-center justify-between gap-3 border-t border-kraft/15 px-5 py-3">
                    @if ($o->canPrint())
                        <a href="{{ route('admin.orders.print', $o) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-[13px] text-kraft/75 underline-offset-4 hover:text-labwhite hover:underline">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V4h10v5M7 17H5v-6h14v6h-2M7 14h10v6H7z"/></svg>{{ __('Cetak invoice & label') }}
                        </a>
                    @else
                        <span class="text-[12.5px] text-kraft/50">{{ $o->status === 'cancelled' ? __('Pesanan dibatalkan, tidak ada invoice.') : __('Invoice bisa dicetak setelah pembayaran lunas.') }}</span>
                    @endif
                    <a href="{{ route('track', $o->order_code) }}" target="_blank" rel="noopener" class="text-[13px] text-kraft/60 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Lihat halaman pelacakan') }} ↗</a>
                </footer>
            </aside>
        </div>
    @endif
</div>
