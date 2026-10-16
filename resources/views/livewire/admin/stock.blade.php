@php
    $n = fn ($v) => number_format($v, 0, ',', '.');
    $t = $this->totals;
    $inp = 'rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $chip = ['ok' => ['Aman', 'border-bluelab/60 text-bluetext'], 'low' => ['Menipis', 'border-[#E8782A]/50 text-ambertext'], 'out' => ['Habis', 'border-redtext/40 text-redtext']];
@endphp
<div>
    <div>
        <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Stok biji mentah') }}</h1>
        <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Saldo tiap kopi, laju pemakaian, dan riwayat setiap pergerakan stok.') }}</p>
    </div>

    @if ($notice)
        <p role="status" x-data x-init="setTimeout(() => $el.remove(), 4000)" class="mt-4 rounded border border-bluelab/50 bg-bluelab/10 px-4 py-2.5 text-[13px]">{{ $notice }}</p>
    @endif

    <dl class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            [__('Total biji mentah'), number_format($t['kg'], 1, ',', '.') . ' kg', null, false],
            [__('Setara hasil sangrai'), number_format($t['roasted'], 1, ',', '.') . ' kg', __('susut ±15%'), false],
            [__('Stok menipis'), $t['low'], '< ' . $n(\App\Models\Setting::int('low_stock_gram')) . ' g', $t['low'] > 0],
            [__('Stok habis'), $t['out'], null, $t['out'] > 0],
        ] as [$l, $v, $sub, $warn])
            <div class="rounded border px-4 py-3 {{ $warn ? 'border-[#E8782A]/45 bg-[#E8782A]/[.06]' : 'border-kraft/20 bg-charcoal' }}">
                <dt class="text-[12px] text-kraft/60">{{ $l }}</dt>
                <dd class="krm-readout mt-1 text-[1.4rem] leading-none {{ $warn ? 'text-ambertext' : '' }}">{{ $v }}</dd>
                <p class="mt-1.5 min-h-[1rem] text-[11.5px] text-kraft/45">{{ $sub }}</p>
            </div>
        @endforeach
    </dl>

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        <div class="flex gap-1 border-b border-kraft/15 px-3" role="tablist">
            @foreach (['overview' => __('Ringkasan stok'), 'history' => __('Riwayat pergerakan')] as $k => $label)
                <button type="button" role="tab" aria-selected="{{ $tab === $k ? 'true' : 'false' }}" wire:click="setTab('{{ $k }}')"
                        class="-mb-px border-b-2 px-3 py-3 text-[13px] transition-colors {{ $tab === $k ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">{{ $label }}</button>
            @endforeach
        </div>

        @if ($tab === 'overview')
            <div class="hidden gap-3 border-b border-kraft/10 px-4 py-2 text-[10.5px] uppercase tracking-[0.12em] text-kraft/45 md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)_84px_auto] krm-readout">
                <span>{{ __('Kopi') }}</span><span>{{ __('Stok (g)') }}</span><span class="text-right">{{ __('Terpakai 30 hari') }}</span><span class="text-right">{{ __('Perkiraan habis') }}</span><span>{{ __('Status') }}</span><span class="text-right">{{ __('Aksi') }}</span>
            </div>
            <ul class="divide-y divide-kraft/10">
                @foreach ($this->rows as $r)
                    @php $c = $r->coffee; $pct = min(100, round($c->stock_green_bean_gram / 20000 * 100)); @endphp
                    <li wire:key="s-{{ $c->id }}" class="grid items-center gap-x-3 gap-y-2 px-4 py-3 md:grid-cols-[minmax(0,2fr)_minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)_84px_auto] {{ $c->is_active ? '' : 'opacity-60' }}">
                        <div class="flex items-start justify-between gap-3 md:block">
                            <p class="truncate text-[13.5px] font-semibold">{{ $c->name }}</p>
                            <span class="whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] md:hidden {{ $chip[$r->status][1] }}">{{ __($chip[$r->status][0]) }}</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="krm-readout w-[3.8rem] shrink-0 text-[12.5px] {{ $r->status !== 'ok' ? 'text-ambertext' : '' }}">{{ $n($c->stock_green_bean_gram) }}</span>
                            <div class="h-[3px] flex-1 rounded-full bg-labwhite/10"><div class="h-[3px] rounded-full {{ $r->status !== 'ok' ? 'bg-[#E8782A]' : 'bg-bluelab' }}" style="width: {{ $pct }}%"></div></div>
                        </div>
                        <p class="krm-readout text-[12.5px] text-kraft/70 md:text-right"><span class="text-kraft/45 md:hidden">{{ __('Terpakai 30 hari') }}: </span>{{ $r->used ? $n($r->used) . ' g' : '—' }}</p>
                        <p class="text-[12.5px] md:text-right {{ $r->daysLeft !== null && $r->daysLeft < 14 ? 'text-ambertext' : 'text-kraft/70' }}"><span class="text-kraft/45 md:hidden">{{ __('Perkiraan habis') }}: </span>{{ $r->daysLeft !== null ? '±' . $r->daysLeft . ' ' . __('hari') : '—' }}</p>
                        <span class="hidden w-fit whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] md:inline-block {{ $chip[$r->status][1] }}">{{ __($chip[$r->status][0]) }}</span>
                        <div class="flex justify-end">
                            <button wire:click="openAdjust({{ $c->id }}, 'restock')" class="rounded border border-bluelab/50 px-3 py-1.5 text-[12.5px] font-semibold text-bluetext transition-colors hover:bg-bluelab hover:text-paper">{{ __('Atur stok') }}</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                <label class="sr-only" for="kopi">{{ __('Kopi') }}</label>
                <select id="kopi" wire:model.live="coffeeFilter" class="{{ $inp }}">
                    <option class="bg-charcoal" value="">{{ __('Semua kopi') }}</option>
                    @foreach ($coffees as $c)<option class="bg-charcoal" value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
                <label class="sr-only" for="jenis">{{ __('Jenis') }}</label>
                <select id="jenis" wire:model.live="typeFilter" class="{{ $inp }}">
                    <option class="bg-charcoal" value="">{{ __('Semua jenis') }}</option>
                    @foreach (\App\Models\StockMovement::TYPES as $k => $l)<option class="bg-charcoal" value="{{ $k }}">{{ __($l) }}</option>@endforeach
                </select>
            </div>

            @php $mv = $this->movements; @endphp
            <div wire:loading.class="opacity-60" class="transition-opacity">
                <table class="hidden w-full text-left text-[13px] md:table">
                    <thead class="krm-readout text-[11px] uppercase tracking-[0.12em] text-kraft/45">
                        <tr class="border-y border-kraft/10"><th class="px-4 py-2.5 font-normal">{{ __('Waktu') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Kopi') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Jenis') }}</th><th class="px-3 py-2.5 text-right font-normal">{{ __('Perubahan') }}</th><th class="px-3 py-2.5 text-right font-normal">{{ __('Saldo') }}</th><th class="px-3 py-2.5 font-normal">{{ __('Catatan') }}</th><th class="px-4 py-2.5 font-normal">{{ __('Oleh') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-kraft/10">
                        @foreach ($mv as $m)
                            <tr wire:key="m-{{ $m->id }}">
                                <td class="krm-readout whitespace-nowrap px-4 py-2.5 text-[12px] text-kraft/60">{{ $m->created_at->translatedFormat('j M, H:i') }}</td>
                                <td class="max-w-[200px] truncate px-3 py-2.5">{{ $m->coffee?->name }}</td>
                                <td class="px-3 py-2.5"><span class="whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] {{ $m->tone() }}">{{ $m->type_label }}</span></td>
                                <td class="krm-readout whitespace-nowrap px-3 py-2.5 text-right {{ $m->grams >= 0 ? 'text-bluetext' : 'text-ambertext' }}">{{ $m->grams >= 0 ? '+' : '−' }}{{ $n(abs($m->grams)) }} g</td>
                                <td class="krm-readout whitespace-nowrap px-3 py-2.5 text-right text-kraft/70">{{ $n($m->balance_after) }}</td>
                                <td class="max-w-[220px] truncate px-3 py-2.5 text-[12.5px] text-kraft/60">
                                    @if ($m->order)<a href="{{ route('admin.orders', ['order' => $m->order->order_code]) }}" wire:navigate class="krm-readout underline-offset-4 hover:underline">{{ $m->order->order_code }}</a>@else{{ $m->reason ? __($m->reason) : '—' }}@endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-[12.5px] text-kraft/60">{{ $m->user?->name ?? __('Sistem') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <ul class="divide-y divide-kraft/10 border-t border-kraft/10 md:hidden">
                    @foreach ($mv as $m)
                        <li wire:key="mm-{{ $m->id }}" class="px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] {{ $m->tone() }}">{{ $m->type_label }}</span>
                                <span class="krm-readout text-[13px] {{ $m->grams >= 0 ? 'text-bluetext' : 'text-ambertext' }}">{{ $m->grams >= 0 ? '+' : '−' }}{{ $n(abs($m->grams)) }} g</span>
                            </div>
                            <p class="mt-1 truncate text-[13px]">{{ $m->coffee?->name }}</p>
                            <p class="text-[11.5px] text-kraft/50">{{ $m->created_at->translatedFormat('j M, H:i') }} · {{ __('Saldo') }} {{ $n($m->balance_after) }} g @if ($m->order)· {{ $m->order->order_code }}@elseif ($m->reason)· {{ __($m->reason) }}@endif</p>
                        </li>
                    @endforeach
                </ul>

                @if ($mv->isEmpty())<p class="px-5 py-12 text-center text-[13px] text-kraft/45">{{ __('Belum ada pergerakan stok.') }}</p>@endif
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-kraft/15 px-4 py-3 text-[12.5px] text-kraft/60">
                <span>{{ $mv->total() ? __('Menampilkan :a–:b dari :t', ['a' => $mv->firstItem(), 'b' => $mv->lastItem(), 't' => $mv->total()]) : '' }}</span>
                <div class="flex items-center gap-2">
                    <button wire:click="previousPage" @disabled($mv->onFirstPage()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Sebelumnya') }}">&larr;</button>
                    <span class="krm-readout">{{ $mv->currentPage() }}/{{ $mv->lastPage() }}</span>
                    <button wire:click="nextPage" @disabled(! $mv->hasMorePages()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Berikutnya') }}">&rarr;</button>
                </div>
            </div>
        @endif
    </section>

    {{-- Modal penyesuaian stok --}}
    @if ($this->target)
        @php $tc = $this->target; @endphp
        <div class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
             x-data="{ init() { document.body.style.overflow = 'hidden'; this.$nextTick(() => this.$el.querySelector('#amount')?.focus()); }, destroy() { document.body.style.overflow = ''; } }"
             @keydown.escape.window="$wire.closeAdjust()">
            <div class="krm-fade absolute inset-0 bg-black/70 backdrop-blur-[2px]" wire:click="closeAdjust"></div>
            <form wire:submit="save" role="dialog" aria-modal="true" aria-labelledby="adj-title" class="krm-pop relative w-full overflow-hidden rounded-t-[14px] border border-kraft/25 bg-surface shadow-[0_30px_80px_-20px_rgb(var(--shadow)/.95)] sm:max-w-[440px] sm:rounded-[14px]">
                <header class="flex items-start justify-between gap-3 border-b border-kraft/15 px-5 py-4">
                    <div>
                        <h2 id="adj-title" class="font-display text-[1.05rem] font-semibold leading-tight">{{ __('Atur stok') }}</h2>
                        <p class="mt-0.5 text-[12.5px] text-kraft/60">{{ $tc->name }} · {{ __('saldo') }} <span class="krm-readout">{{ $n($tc->stock_green_bean_gram) }} g</span></p>
                    </div>
                    <button type="button" wire:click="closeAdjust" class="-mr-2 grid h-9 w-9 place-items-center rounded text-kraft/70 hover:bg-labwhite/[.06] hover:text-labwhite" aria-label="{{ __('Tutup') }}"><svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg></button>
                </header>

                <div class="space-y-4 px-5 py-4">
                    <div class="grid grid-cols-3 gap-1 rounded border border-kraft/20 p-0.5 text-[12.5px]" role="radiogroup">
                        @foreach (['restock' => __('Tambah'), 'count' => __('Koreksi hitung'), 'waste' => __('Kurangi')] as $k => $l)
                            <button type="button" role="radio" aria-checked="{{ $mode === $k ? 'true' : 'false' }}" wire:click="$set('mode', '{{ $k }}')" class="rounded-[8px] px-2 py-2 transition-colors {{ $mode === $k ? 'bg-bluelab text-paper' : 'text-kraft/70 hover:text-labwhite' }}">{{ $l }}</button>
                        @endforeach
                    </div>

                    <div>
                        <label for="amount" class="text-[12.5px] text-kraft/75">
                            {{ match ($mode) { 'restock' => __('Jumlah yang masuk (gram)'), 'waste' => __('Jumlah yang dikurangi (gram)'), default => __('Hasil hitung stok sebenarnya (gram)') } }}
                        </label>
                        <input id="amount" type="number" inputmode="numeric" wire:model="amount" placeholder="{{ $mode === 'count' ? '12000' : '5000' }}" class="{{ $inp }} krm-readout mt-1.5 w-full py-2.5 text-[14px]">
                        @error('amount')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                        @if ($mode === 'count' && $amount !== '' && is_numeric($amount))
                            @php $d = (int) $amount - $tc->stock_green_bean_gram; @endphp
                            <p class="mt-1.5 text-[12px] text-kraft/55">{{ __('Selisih') }}: <span class="krm-readout {{ $d >= 0 ? 'text-bluetext' : 'text-ambertext' }}">{{ $d >= 0 ? '+' : '−' }}{{ $n(abs($d)) }} g</span></p>
                        @endif
                    </div>

                    <div>
                        <label for="reason" class="text-[12.5px] text-kraft/75">{{ __('Catatan') }} <span class="text-kraft/45">· {{ $mode === 'waste' ? __('wajib') : __('opsional') }}</span></label>
                        <input id="reason" wire:model="reason" placeholder="{{ match ($mode) { 'restock' => __('Contoh: Beli dari Koperasi Gayo, invoice 0231'), 'waste' => __('Contoh: Biji berjamur / tumpah saat sangrai'), default => __('Contoh: Hasil stock opname akhir bulan') } }}" class="{{ $inp }} mt-1.5 w-full py-2.5 text-[14px]">
                        @error('reason')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                </div>

                <footer class="flex items-center justify-end gap-2 border-t border-kraft/15 px-5 py-3.5">
                    <button type="button" wire:click="closeAdjust" class="rounded px-4 py-2.5 text-[13.5px] text-kraft/70 hover:text-labwhite">{{ __('Batal') }}</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-6 py-2.5 font-display text-[14px] font-semibold hover:bg-[#234E94] disabled:opacity-60">{{ __('Simpan') }}</button>
                </footer>
            </form>
        </div>
    @endif
</div>
