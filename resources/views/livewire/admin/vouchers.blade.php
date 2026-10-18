@php
    $n = fn ($v) => number_format($v, 0, ',', '.');
    $inp = 'w-full rounded border border-kraft/25 bg-transparent px-3 py-2.5 text-[14px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $chip = [
        'active' => ['Aktif', 'border-bluelab/60 text-bluetext'],
        'inactive' => ['Nonaktif', 'border-kraft/30 text-kraft/60'],
        'scheduled' => ['Terjadwal', 'border-kraft/40 text-kraft/80'],
        'expired' => ['Berakhir', 'border-[#E8782A]/50 text-ambertext'],
        'exhausted' => ['Kuota habis', 'border-[#E8782A]/50 text-ambertext'],
    ];
@endphp
<div>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Voucher') }}</h1>
            <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Kode diskon untuk checkout. Divalidasi ulang di server saat pesanan dibuat.') }}</p>
        </div>
        <button wire:click="openForm" class="inline-flex items-center gap-2 rounded bg-bluelab px-4 py-2.5 text-[13.5px] font-semibold transition-colors hover:bg-[#234E94]">
            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>{{ __('Voucher baru') }}
        </button>
    </div>

    @if ($notice)
        <p role="status" x-data x-init="setTimeout(() => $el.remove(), 5000)" class="mt-4 rounded border border-bluelab/50 bg-bluelab/10 px-4 py-2.5 text-[13px]">{{ $notice }}</p>
    @endif

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-kraft/15 px-3">
            <div class="flex gap-1" role="tablist">
                @foreach (['all' => __('Semua'), 'live' => __('Berjalan'), 'ended' => __('Tidak aktif')] as $k => $l)
                    <button role="tab" aria-selected="{{ $show === $k ? 'true' : 'false' }}" wire:click="$set('show', '{{ $k }}')"
                            class="-mb-px border-b-2 px-3 py-3 text-[13px] transition-colors {{ $show === $k ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">{{ $l }} <span class="krm-readout ml-1 rounded-[6px] bg-labwhite/[.07] px-1.5 text-[11px]">{{ $this->counts[$k] }}</span></button>
                @endforeach
            </div>
            <div class="py-2">
                <label class="sr-only" for="vq">{{ __('Cari voucher') }}</label>
                <input id="vq" wire:model.live.debounce.300ms="search" placeholder="{{ __('Cari kode, cth: KOPIBARU') }}" class="{{ $inp }} !py-2 !text-[13px] sm:w-64">
            </div>
        </div>

        <div wire:loading.class="opacity-60" class="transition-opacity">
            @if ($this->vouchers->isEmpty())
                <p class="px-5 py-14 text-center text-[13px] text-kraft/45">{{ __('Belum ada voucher yang cocok.') }}</p>
            @else
                <ul class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->vouchers as $v)
                        @php
                            $st = $v->state(); $pct = $v->quota ? min(100, round($v->used_count / $v->quota * 100)) : 0;
                            $fmt = fn ($d, $y = true) => $d->translatedFormat($y ? 'j M Y' : 'j M');
                            $valid = match (true) {
                                $v->starts_at && $v->expires_at => $fmt($v->starts_at, false) . ' – ' . $fmt($v->expires_at),
                                (bool) $v->expires_at => __('s/d :d', ['d' => $fmt($v->expires_at)]),
                                (bool) $v->starts_at => __('mulai :d', ['d' => $fmt($v->starts_at)]),
                                default => __('Tanpa batas waktu'),
                            };
                        @endphp
                        <li wire:key="v-{{ $v->id }}" class="flex flex-col rounded-[10px] border border-kraft/20 bg-surface/40 p-4 {{ $st === 'active' ? '' : 'opacity-80' }}">
                            <div class="flex items-start justify-between gap-3">
                                <p class="krm-readout break-all text-[15px] font-semibold tracking-wide">{{ $v->code }}</p>
                                <span class="shrink-0 whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] {{ $chip[$st][1] }}">{{ __($chip[$st][0]) }}</span>
                            </div>
                            <p class="mt-1 font-display text-[1.15rem] font-bold leading-tight">{{ $v->benefitLabel() }}</p>
                            <p class="mt-1 min-h-[2.4em] text-[12.5px] leading-snug text-kraft/55">{{ $v->note ?: '—' }}</p>

                            <dl class="mt-3 space-y-1.5 border-t border-kraft/10 pt-3 text-[12.5px]">
                                <div class="flex justify-between gap-3"><dt class="text-kraft/50">{{ __('Min. belanja') }}</dt><dd class="krm-readout">{{ $v->min_subtotal ? 'Rp' . $n($v->min_subtotal) : '—' }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-kraft/50">{{ __('Masa berlaku') }}</dt><dd class="krm-readout text-right">{{ $valid }}</dd></div>
                                <div>
                                    <div class="flex justify-between gap-3"><dt class="text-kraft/50">{{ __('Terpakai') }}</dt><dd class="krm-readout">{{ $v->used_count }}{{ $v->quota ? ' / ' . $v->quota : ' · ' . __('tanpa batas') }}</dd></div>
                                    @if ($v->quota)<div class="mt-1.5 h-[3px] rounded-full bg-labwhite/10"><div class="h-[3px] rounded-full {{ $pct >= 100 ? 'bg-[#E8782A]' : 'bg-bluelab' }}" style="width: {{ $pct }}%"></div></div>@endif
                                </div>
                            </dl>

                            <div class="mt-4 flex items-center justify-between gap-3 border-t border-kraft/10 pt-3">
                                <label class="flex cursor-pointer items-center gap-2 text-[12.5px] text-kraft/70">
                                    <button type="button" role="switch" aria-checked="{{ $v->is_active ? 'true' : 'false' }}" aria-label="{{ $v->is_active ? __('Matikan') : __('Aktifkan') }}" wire:click="toggle({{ $v->id }})"
                                            class="relative h-[22px] w-[38px] shrink-0 rounded-full transition-colors {{ $v->is_active ? 'bg-bluelab' : 'bg-labwhite/25' }}">
                                        <span class="absolute top-[3px] h-4 w-4 rounded-full bg-paper shadow transition-all {{ $v->is_active ? 'left-[19px]' : 'left-[3px]' }}"></span>
                                    </button>
                                    {{ $v->is_active ? __('Dinyalakan') : __('Dimatikan') }}
                                </label>
                                <button wire:click="openForm({{ $v->id }})" class="rounded border border-kraft/30 px-3.5 py-1.5 text-[12.5px] transition-colors hover:border-kraft/70">{{ __('Ubah') }}</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    @if ($open)
        <div class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
             x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             @keydown.escape.window="$wire.closeForm()">
            <div class="krm-fade absolute inset-0 bg-black/70 backdrop-blur-[2px]" wire:click="closeForm"></div>
            <form wire:submit="save" role="dialog" aria-modal="true" aria-labelledby="vf-title" class="krm-pop relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-[14px] border border-kraft/25 bg-surface shadow-[0_30px_80px_-20px_rgb(var(--shadow)/.95)] sm:max-w-[520px] sm:rounded-[14px]">
                <header class="flex items-center justify-between gap-3 border-b border-kraft/15 px-5 py-4">
                    <h2 id="vf-title" class="font-display text-[1.05rem] font-semibold">{{ $editingId ? __('Ubah voucher') : __('Voucher baru') }}</h2>
                    <button type="button" wire:click="closeForm" class="-mr-2 grid h-9 w-9 place-items-center rounded text-kraft/70 hover:bg-labwhite/[.06] hover:text-labwhite" aria-label="{{ __('Tutup') }}"><svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg></button>
                </header>

                <div class="space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        <label for="vcode" class="text-[12.5px] text-kraft/75">{{ __('Kode voucher') }}</label>
                        <input id="vcode" wire:model="code" autocomplete="off" placeholder="KOPIBARU10" class="{{ $inp }} krm-readout mt-1.5 uppercase placeholder:normal-case">
                        @error('code')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="vtype" class="text-[12.5px] text-kraft/75">{{ __('Jenis potongan') }}</label>
                            <select id="vtype" wire:model.live="type" class="{{ $inp }} mt-1.5">
                                @foreach (\App\Models\Voucher::TYPES as $k => $l)<option class="bg-surface" value="{{ $k }}">{{ __($l) }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="vvalue" class="text-[12.5px] text-kraft/75">{{ $type === 'percent' ? __('Persen') : __('Nominal (Rp)') }}</label>
                            <input id="vvalue" type="number" inputmode="numeric" wire:model="value" placeholder="{{ $type === 'percent' ? '10' : '25000' }}" class="{{ $inp }} krm-readout mt-1.5">
                            @error('value')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="vmin" class="text-[12.5px] text-kraft/75">{{ __('Min. belanja (Rp)') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                            <input id="vmin" type="number" inputmode="numeric" wire:model="minSubtotal" placeholder="100000" class="{{ $inp }} krm-readout mt-1.5">
                            @error('minSubtotal')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                        @if ($type === 'percent')
                            <div>
                                <label for="vmax" class="text-[12.5px] text-kraft/75">{{ __('Maks. potongan (Rp)') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                                <input id="vmax" type="number" inputmode="numeric" wire:model="maxDiscount" placeholder="50000" class="{{ $inp }} krm-readout mt-1.5">
                                @error('maxDiscount')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="vquota" class="text-[12.5px] text-kraft/75">{{ __('Kuota pemakaian') }} <span class="text-kraft/45">· {{ __('kosongkan = tanpa batas') }}</span></label>
                        <input id="vquota" type="number" inputmode="numeric" wire:model="quota" placeholder="100" class="{{ $inp }} krm-readout mt-1.5">
                        @error('quota')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="vstart" class="text-[12.5px] text-kraft/75">{{ __('Mulai berlaku') }}</label>
                            <input id="vstart" type="date" wire:model="startsAt" placeholder="2026-11-01" class="{{ $inp }} mt-1.5 [color-scheme:dark]">
                        </div>
                        <div>
                            <label for="vend" class="text-[12.5px] text-kraft/75">{{ __('Berakhir') }}</label>
                            <input id="vend" type="date" wire:model="expiresAt" placeholder="2026-12-31" class="{{ $inp }} mt-1.5 [color-scheme:dark]">
                            @error('expiresAt')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="vnote" class="text-[12.5px] text-kraft/75">{{ __('Catatan internal') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                        <input id="vnote" wire:model="note" placeholder="{{ __('Contoh: Promo pelanggan baru bulan November') }}" class="{{ $inp }} mt-1.5">
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 text-[13.5px]"><input type="checkbox" wire:model="active" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">{{ __('Voucher aktif') }}</label>
                </div>

                <footer class="flex items-center justify-end gap-2 border-t border-kraft/15 px-5 py-3.5">
                    @if ($editingId)
                        <button type="button" wire:click="delete({{ $editingId }})" wire:confirm="{{ __('Hapus voucher ini?') }}" class="mr-auto rounded px-2 py-2.5 text-[13px] text-redtext/90 transition-colors hover:text-redtext">{{ __('Hapus voucher') }}</button>
                    @endif
                    <button type="button" wire:click="closeForm" class="rounded px-4 py-2.5 text-[13.5px] text-kraft/70 hover:text-labwhite">{{ __('Batal') }}</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-6 py-2.5 font-display text-[14px] font-semibold hover:bg-[#234E94] disabled:opacity-60">{{ __('Simpan') }}</button>
                </footer>
            </form>
        </div>
    @endif
</div>
