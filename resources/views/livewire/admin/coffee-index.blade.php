<div class="mx-auto max-w-[1100px] px-6 pb-24 pt-12 md:pt-14">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="krm-readout text-[12px] tracking-[0.18em] text-kraft/60">{{ __('ADMIN') }}</p>
            <h1 class="krm-h2 mt-2 font-bold">{{ __('Kelola kopi') }}</h1>
        </div>
        <a href="{{ route('admin.coffees.create') }}" wire:navigate
           class="rounded bg-bluelab px-5 py-3 font-display text-[14px] font-semibold transition-colors hover:bg-[#234E94]">+ {{ __('Kopi baru') }}</a>
    </div>

    @php $flash = $notice ?? session('notice'); @endphp
    @if ($flash)
        <p role="status" x-data x-init="setTimeout(() => $el.remove(), 4500)"
           class="mt-6 rounded border border-bluelab/50 bg-bluelab/10 px-4 py-3 text-[13.5px]">{{ $flash }}</p>
    @endif

    <ul class="mt-8 space-y-4">
        @foreach ($this->coffees as $c)
            @php $pct = min(100, round($c->stock_green_bean_gram / 20000 * 100)); @endphp
            <li wire:key="c-{{ $c->id }}" class="rounded border border-kraft/20 bg-[#121212] p-5 transition-colors hover:border-kraft/35 {{ $c->is_active ? '' : 'opacity-60' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-display text-[1.1rem] font-semibold leading-snug">{{ $c->name }}</h2>
                        <p class="krm-readout mt-1 text-[11.5px] text-kraft/50">{{ $c->slug }} · {{ $c->roast_profiles_count }} {{ __('level sangrai') }}@if ($c->cupping_score) · {{ __('skor') }} {{ $c->cupping_score }}@endif</p>
                    </div>
                    <span class="krm-readout rounded-[6px] border px-2 py-0.5 text-[11px] uppercase tracking-wider {{ $c->is_active ? 'border-bluelab/60 text-labwhite' : 'border-kraft/25 text-kraft/60' }}">
                        {{ $c->is_active ? __('Tampil') : __('Tersembunyi') }}
                    </span>
                </div>

                <div class="mt-4">
                    <div class="flex justify-between text-[13px]"><span class="text-kraft/65">{{ __('Stok biji mentah') }}</span><span class="krm-readout">{{ number_format($c->stock_green_bean_gram) }} g</span></div>
                    <div class="mt-1.5 h-1 rounded-full bg-white/10"><div class="h-1 rounded-full transition-all duration-500 {{ $pct < 25 ? 'bg-[#E8782A]' : 'bg-bluelab' }}" style="width: {{ $pct }}%"></div></div>
                </div>

                <div class="mt-4 flex flex-wrap items-start gap-x-3 gap-y-3">
                    <form wire:submit="addStock({{ $c->id }})" class="flex min-w-[220px] flex-1 flex-wrap gap-2 sm:flex-none">
                        <label class="sr-only" for="rs-{{ $c->id }}">{{ __('Tambah stok (gram)') }}</label>
                        <input id="rs-{{ $c->id }}" type="number" inputmode="numeric" min="1" wire:model="restock.{{ $c->id }}" placeholder="{{ __('Contoh: 5000') }}"
                               class="krm-readout w-36 rounded border border-kraft/25 bg-transparent px-3 py-2 text-[13.5px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                        <button class="rounded border border-bluelab/60 px-3.5 py-2 text-[13.5px] font-semibold transition-colors hover:bg-bluelab">+ {{ __('Tambah stok') }}</button>
                        @error("restock.$c->id")<p class="w-full text-[12px] text-red-300">{{ $message }}</p>@enderror
                    </form>

                    <div class="ml-auto flex flex-wrap gap-2">
                        <a href="{{ route('admin.coffees.edit', $c) }}" wire:navigate class="rounded border border-kraft/30 px-3.5 py-2 text-[13.5px] transition-colors hover:border-kraft/70">{{ __('Ubah') }}</a>
                        <button wire:click="toggle({{ $c->id }})" class="rounded border border-kraft/30 px-3.5 py-2 text-[13.5px] transition-colors hover:border-kraft/70">{{ $c->is_active ? __('Sembunyikan') : __('Tampilkan') }}</button>
                        <button wire:click="delete({{ $c->id }})" wire:confirm="{{ __('Hapus kopi ini beserta semua level sangrainya?') }}"
                                class="rounded border border-red-400/30 px-3.5 py-2 text-[13.5px] text-red-300 transition-colors hover:bg-red-400/10">{{ __('Hapus') }}</button>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
</div>
