@php
    $inp = 'rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13px] focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40 [color-scheme:dark]';
    $tabs = ['' => __('Semua')] + collect(\App\Livewire\Admin\ActivityLogs::GROUPS)->map(fn ($l) => __($l))->all();
    $tone = ['order' => 'border-bluelab/60 text-bluetext', 'coffee' => 'border-kraft/40 text-kraft/80', 'stock' => 'border-kraft/40 text-kraft/80', 'voucher' => 'border-[#E8782A]/50 text-ambertext', 'user' => 'border-[#E8782A]/50 text-ambertext', 'settings' => 'border-kraft/40 text-kraft/80'];
    $logs = $this->logs;
@endphp
<div>
    <div>
        <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Log aktivitas') }}</h1>
        <p class="mt-0.5 text-[12.5px] text-kraft/70">{{ __('Jejak siapa mengubah apa di panel: pesanan, kopi, stok, voucher, pengguna, dan pengaturan.') }}</p>
    </div>

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        <div class="flex gap-1 overflow-x-auto border-b border-kraft/15 px-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden max-sm:pb-px sm:flex-wrap sm:overflow-visible" role="tablist">
            @foreach ($tabs as $k => $l)
                <button role="tab" aria-selected="{{ $group === (string) $k ? 'true' : 'false' }}" wire:click="setGroup('{{ $k }}')"
                        class="-mb-px shrink-0 whitespace-nowrap border-b-2 px-3 py-3 text-[13px] transition-colors {{ $group === (string) $k ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/70 hover:text-labwhite' }}">{{ $l }}</button>
            @endforeach
        </div>

        <div class="grid gap-3 border-b border-kraft/10 px-4 py-3 sm:flex sm:flex-wrap sm:items-center">
            <label class="sr-only" for="lb">{{ __('Dilakukan oleh') }}</label>
            <select id="lb" wire:model.live="by" class="{{ $inp }} w-full sm:w-auto">
                <option class="bg-charcoal" value="">{{ __('Semua pengguna') }}</option>
                @foreach ($actors as $a)<option class="bg-charcoal" value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
            </select>
            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 sm:flex">
                <label class="sr-only" for="lf">{{ __('Dari tanggal') }}</label>
                <input id="lf" type="date" wire:model.live="from" placeholder="2026-10-01" class="{{ $inp }} krm-readout w-full min-w-0 max-sm:px-2 max-sm:text-[12.5px] sm:w-auto">
                <span class="text-kraft/70">–</span>
                <label class="sr-only" for="lt">{{ __('Sampai tanggal') }}</label>
                <input id="lt" type="date" wire:model.live="to" placeholder="2026-10-16" class="{{ $inp }} krm-readout w-full min-w-0 max-sm:px-2 max-sm:text-[12.5px] sm:w-auto">
            </div>
        </div>

        <div wire:loading.class="opacity-60" class="transition-opacity">
            <ul class="divide-y divide-kraft/10">
                @forelse ($logs as $l)
                    @php $g = explode('.', $l->action)[0]; $order = $l->subject instanceof \App\Models\Order ? $l->subject : null; @endphp
                    <li wire:key="l-{{ $l->id }}" class="grid items-center gap-x-4 gap-y-1 px-4 py-3 md:grid-cols-[130px_minmax(0,140px)_90px_minmax(0,1fr)]">
                        <p class="krm-readout text-[12px] text-kraft/70">{{ $l->created_at->translatedFormat('j M, H:i') }}</p>
                        <p class="truncate text-[12.5px] text-kraft/75">{{ $l->user?->name ?? __('Sistem') }}</p>
                        <span class="w-fit whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] {{ $tone[$g] ?? 'border-kraft/30 text-kraft/70' }}">{{ __(\App\Livewire\Admin\ActivityLogs::GROUPS[$g] ?? ucfirst($g)) }}</span>
                        <p class="text-[13px]">{{ $l->text() }} @if ($order)<a href="{{ route('admin.orders', ['order' => $order->order_code]) }}" wire:navigate class="krm-readout ml-1 text-[12px] text-bluetext underline-offset-4 hover:underline">{{ $order->order_code }}</a>@endif</p>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-[13px] text-kraft/70">{{ __('Belum ada aktivitas yang cocok.') }}</li>
                @endforelse
            </ul>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-kraft/15 px-4 py-3 text-[12.5px] text-kraft/70">
            <span>{{ $logs->total() ? __('Menampilkan :a–:b dari :t', ['a' => $logs->firstItem(), 'b' => $logs->lastItem(), 't' => $logs->total()]) : '' }}</span>
            <div class="flex items-center gap-2">
                <button wire:click="previousPage" @disabled($logs->onFirstPage()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Sebelumnya') }}">&larr;</button>
                <span class="krm-readout">{{ $logs->currentPage() }}/{{ $logs->lastPage() }}</span>
                <button wire:click="nextPage" @disabled(! $logs->hasMorePages()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Berikutnya') }}">&rarr;</button>
            </div>
        </div>
    </section>
</div>
