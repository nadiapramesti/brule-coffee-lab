@php
    $n = fn ($v) => number_format($v, 0, ',', '.');
    $inp = 'w-full rounded border border-kraft/25 bg-transparent px-3 py-2.5 text-[14px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $chip = [
        'active' => ['Aktif', 'border-bluelab/60 text-[#9DBBEF]'],
        'inactive' => ['Nonaktif', 'border-kraft/30 text-kraft/60'],
        'scheduled' => ['Terjadwal', 'border-kraft/40 text-kraft/80'],
        'expired' => ['Berakhir', 'border-[#E8782A]/50 text-[#F2A266]'],
        'exhausted' => ['Kuota habis', 'border-[#E8782A]/50 text-[#F2A266]'],
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

    <section class="mt-4 rounded border border-kraft/20 bg-[#121212]">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-kraft/15 px-3">
            <div class="flex gap-1" role="tablist">
                @foreach (['all' => __('Semua'), 'live' => __('Berjalan'), 'ended' => __('Tidak aktif')] as $k => $l)
                    <button role="tab" aria-selected="{{ $show === $k ? 'true' : 'false' }}" wire:click="$set('show', '{{ $k }}')"
                            class="-mb-px border-b-2 px-3 py-3 text-[13px] transition-colors {{ $show === $k ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">{{ $l }} <span class="krm-readout ml-1 rounded-[6px] bg-white/[.07] px-1.5 text-[11px]">{{ $this->counts[$k] }}</span></button>
                @endforeach
            </div>
            <div class="py-2">
                <label class="sr-only" for="vq">{{ __('Cari voucher') }}</label>
                <input id="vq" wire:model.live.debounce.300ms="search" placeholder="{{ __('Cari kode, cth: KOPIBARU') }}" class="{{ $inp }} !py-2 !text-[13px] sm:w-64">
            </div>
        </div>

        <div wire:loading.class="opacity-60" class="transition-opacity">
            <div class="hidden gap-3 border-b border-kraft/10 px-4 py-2 text-[10.5px] uppercase tracking-[0.12em] text-kraft/45 krm-readout lg:grid lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,1.3fr)_90px_auto]">
                <span>{{ __('Kode') }}</span><span>{{ __('Potongan') }}</span><span>{{ __('Min. belanja') }}</span><span>{{ __('Terpakai') }}</span><span>{{ __('Masa berlaku') }}</span><span>{{ __('Status') }}</span><span class="text-right">{{ __('Aksi') }}</span>
            </div>
            <ul class="divide-y divide-kraft/10">
                @forelse ($this->vouchers as $v)
                    @php $st = $v->state(); $pct = $v->quota ? min(100, round($v->used_count / $v->quota * 100)) : 0; @endphp
                    <li wire:key="v-{{ $v->id }}" class="grid items-center gap-x-3 gap-y-2 px-4 py-3 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,1.3fr)_90px_auto]">
                        <div class="flex items-start justify-between gap-3 lg:block">
                            <div class="min-w-0">
                                <p class="krm-readout text-[13.5px] font-semibold tracking-wide">{{ $v->code }}</p>
                                @if ($v->note)<p class="truncate text-[12px] text-kraft/55">{{ $v->note }}</p>@endif
                            </div>
                            <span class="whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] lg:hidden {{ $chip[$st][1] }}">{{ __($chip[$st][0]) }}</span>
                        </div>
                        <p class="text-[13px]"><span class="text-kraft/45 lg:hidden">{{ __('Potongan') }}: </span>{{ $v->benefitLabel() }}</p>
                        <p class="krm-readout text-[12.5px] text-kraft/70"><span class="font-sans text-kraft/45 lg:hidden">{{ __('Min. belanja') }}: </span>{{ $v->min_subtotal ? 'Rp' . $n($v->min_subtotal) : '—' }}</p>
                        <div class="flex items-center gap-2.5">
                            <span class="krm-readout shrink-0 text-[12.5px]">{{ $v->used_count }}{{ $v->quota ? '/' . $v->quota : '' }}</span>
                            @if ($v->quota)<div class="h-[3px] flex-1 rounded-full bg-white/10"><div class="h-[3px] rounded-full {{ $pct >= 100 ? 'bg-[#E8782A]' : 'bg-bluelab' }}" style="width: {{ $pct }}%"></div></div>@else<span class="text-[12px] text-kraft/45">{{ __('tanpa batas') }}</span>@endif
                        </div>
                        <p class="text-[12.5px] text-kraft/70">
                            @if ($v->starts_at || $v->expires_at)
                                {{ $v->starts_at ? $v->starts_at->translatedFormat('j M') : '…' }} – {{ $v->expires_at ? $v->expires_at->translatedFormat('j M Y') : '…' }}
                            @else<span class="text-kraft/45">{{ __('tanpa batas waktu') }}</span>@endif
                        </p>
                        <span class="hidden w-fit whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] lg:inline-block {{ $chip[$st][1] }}">{{ __($chip[$st][0]) }}</span>
                        <div class="flex justify-end gap-1.5">
                            <button wire:click="openForm({{ $v->id }})" class="rounded border border-kraft/30 px-3 py-1.5 text-[12.5px] transition-colors hover:border-kraft/70">{{ __('Ubah') }}</button>
                            <button wire:click="toggle({{ $v->id }})" class="rounded border border-kraft/30 px-3 py-1.5 text-[12.5px] transition-colors hover:border-kraft/70">{{ $v->is_active ? __('Matikan') : __('Aktifkan') }}</button>
                            <button wire:click="delete({{ $v->id }})" wire:confirm="{{ __('Hapus voucher :c?', ['c' => $v->code]) }}" class="grid h-[30px] w-[30px] place-items-center rounded border border-kraft/20 text-kraft/55 transition-colors hover:border-red-400/50 hover:text-red-300" aria-label="{{ __('Hapus') }}"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2M7 7l1 12h8l1-12"/></svg></button>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-[13px] text-kraft/45">{{ __('Belum ada voucher yang cocok.') }}</li>
                @endforelse
            </ul>
        </div>
    </section>

    @if ($open)
        <div class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
             x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }"
             @keydown.escape.window="$wire.closeForm()">
            <div class="krm-fade absolute inset-0 bg-black/70 backdrop-blur-[2px]" wire:click="closeForm"></div>
            <form wire:submit="save" role="dialog" aria-modal="true" aria-labelledby="vf-title" class="krm-pop relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-[14px] border border-kraft/25 bg-[#141414] shadow-[0_30px_80px_-20px_rgba(0,0,0,.95)] sm:max-w-[520px] sm:rounded-[14px]">
                <header class="flex items-center justify-between gap-3 border-b border-kraft/15 px-5 py-4">
                    <h2 id="vf-title" class="font-display text-[1.05rem] font-semibold">{{ $editingId ? __('Ubah voucher') : __('Voucher baru') }}</h2>
                    <button type="button" wire:click="closeForm" class="-mr-2 grid h-9 w-9 place-items-center rounded text-kraft/70 hover:bg-white/[.06] hover:text-labwhite" aria-label="{{ __('Tutup') }}"><svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg></button>
                </header>

                <div class="space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        <label for="vcode" class="text-[12.5px] text-kraft/75">{{ __('Kode voucher') }}</label>
                        <input id="vcode" wire:model="code" autocomplete="off" placeholder="KOPIBARU10" class="{{ $inp }} krm-readout mt-1.5 uppercase placeholder:normal-case">
                        @error('code')<p class="mt-1 text-[12px] text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="vtype" class="text-[12.5px] text-kraft/75">{{ __('Jenis potongan') }}</label>
                            <select id="vtype" wire:model.live="type" class="{{ $inp }} mt-1.5">
                                @foreach (\App\Models\Voucher::TYPES as $k => $l)<option class="bg-[#141414]" value="{{ $k }}">{{ __($l) }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="vvalue" class="text-[12.5px] text-kraft/75">{{ $type === 'percent' ? __('Persen') : __('Nominal (Rp)') }}</label>
                            <input id="vvalue" type="number" inputmode="numeric" wire:model="value" placeholder="{{ $type === 'percent' ? '10' : '25000' }}" class="{{ $inp }} krm-readout mt-1.5">
                            @error('value')<p class="mt-1 text-[12px] text-red-300">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="vmin" class="text-[12.5px] text-kraft/75">{{ __('Min. belanja (Rp)') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                            <input id="vmin" type="number" inputmode="numeric" wire:model="minSubtotal" placeholder="100000" class="{{ $inp }} krm-readout mt-1.5">
                            @error('minSubtotal')<p class="mt-1 text-[12px] text-red-300">{{ $message }}</p>@enderror
                        </div>
                        @if ($type === 'percent')
                            <div>
                                <label for="vmax" class="text-[12.5px] text-kraft/75">{{ __('Maks. potongan (Rp)') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                                <input id="vmax" type="number" inputmode="numeric" wire:model="maxDiscount" placeholder="50000" class="{{ $inp }} krm-readout mt-1.5">
                                @error('maxDiscount')<p class="mt-1 text-[12px] text-red-300">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="vquota" class="text-[12.5px] text-kraft/75">{{ __('Kuota pemakaian') }} <span class="text-kraft/45">· {{ __('kosongkan = tanpa batas') }}</span></label>
                        <input id="vquota" type="number" inputmode="numeric" wire:model="quota" placeholder="100" class="{{ $inp }} krm-readout mt-1.5">
                        @error('quota')<p class="mt-1 text-[12px] text-red-300">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="vstart" class="text-[12.5px] text-kraft/75">{{ __('Mulai berlaku') }}</label>
                            <input id="vstart" type="date" wire:model="startsAt" placeholder="2026-11-01" class="{{ $inp }} mt-1.5 [color-scheme:dark]">
                        </div>
                        <div>
                            <label for="vend" class="text-[12.5px] text-kraft/75">{{ __('Berakhir') }}</label>
                            <input id="vend" type="date" wire:model="expiresAt" placeholder="2026-12-31" class="{{ $inp }} mt-1.5 [color-scheme:dark]">
                            @error('expiresAt')<p class="mt-1 text-[12px] text-red-300">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="vnote" class="text-[12.5px] text-kraft/75">{{ __('Catatan internal') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                        <input id="vnote" wire:model="note" placeholder="{{ __('Contoh: Promo pelanggan baru bulan November') }}" class="{{ $inp }} mt-1.5">
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 text-[13.5px]"><input type="checkbox" wire:model="active" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">{{ __('Voucher aktif') }}</label>
                </div>

                <footer class="flex items-center justify-end gap-2 border-t border-kraft/15 px-5 py-3.5">
                    <button type="button" wire:click="closeForm" class="rounded px-4 py-2.5 text-[13.5px] text-kraft/70 hover:text-labwhite">{{ __('Batal') }}</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-6 py-2.5 font-display text-[14px] font-semibold hover:bg-[#234E94] disabled:opacity-60">{{ __('Simpan') }}</button>
                </footer>
            </form>
        </div>
    @endif
</div>
