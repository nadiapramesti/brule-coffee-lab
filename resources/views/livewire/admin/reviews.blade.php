<div>
    <div>
        <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Ulasan') }}</h1>
        <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Ulasan dari pembeli dengan pesanan selesai. Sembunyikan yang melanggar aturan; yang disembunyikan tidak tampil di toko dan tidak dihitung ke rating.') }}</p>
    </div>

    @if ($notice)
        <p role="status" x-data x-init="setTimeout(() => $el.remove(), 5000)" class="mt-4 rounded border border-bluelab/50 bg-bluelab/10 px-4 py-2.5 text-[13px]">{{ $notice }}</p>
    @endif

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        <div class="flex gap-1 border-b border-kraft/15 px-3" role="tablist">
            @foreach (['all' => __('Semua'), 'visible' => __('Tampil'), 'hidden' => __('Disembunyikan')] as $k => $l)
                <button role="tab" aria-selected="{{ $show === $k ? 'true' : 'false' }}" wire:click="$set('show', '{{ $k }}')"
                        class="-mb-px border-b-2 px-3 py-3 text-[13px] transition-colors {{ $show === $k ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">{{ $l }} <span class="krm-readout ml-1 rounded-[6px] bg-labwhite/[.07] px-1.5 text-[11px]">{{ $this->counts[$k] }}</span></button>
            @endforeach
        </div>

        <ul class="divide-y divide-kraft/10">
            @forelse ($this->reviews as $r)
                <li class="flex flex-wrap items-start justify-between gap-4 px-4 py-4 {{ $r->is_hidden ? 'opacity-60' : '' }}" wire:key="r-{{ $r->id }}">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <x-stars :value="$r->rating" size="h-3.5 w-3.5" />
                            <span class="text-[13.5px] font-semibold">{{ $r->coffee->name }}</span>
                            @if ($r->is_hidden)<span class="krm-readout rounded-[6px] border border-[#E8782A]/50 px-1.5 text-[10.5px] text-ambertext">{{ __('DISEMBUNYIKAN') }}</span>@endif
                        </div>
                        <p class="mt-1 text-[12px] text-kraft/55">{{ $r->user->name }} · {{ $r->user->email }} · {{ $r->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</p>
                        @if ($r->body)<p class="mt-2 max-w-[70ch] text-[13.5px] leading-relaxed text-kraft/80">{{ $r->body }}</p>@endif
                    </div>
                    <div class="flex gap-2 text-[13px]">
                        <button wire:click="toggle({{ $r->id }})" class="rounded border border-kraft/30 px-3 py-1.5 transition-colors hover:border-kraft/60">{{ $r->is_hidden ? __('Tampilkan') : __('Sembunyikan') }}</button>
                        <button wire:click="delete({{ $r->id }})" wire:confirm="{{ __('Hapus ulasan ini secara permanen?') }}" class="rounded border border-kraft/30 px-3 py-1.5 text-kraft/70 transition-colors hover:border-kraft/60">{{ __('Hapus') }}</button>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center text-[13.5px] text-kraft/55">{{ __('Belum ada ulasan.') }}</li>
            @endforelse
        </ul>
    </section>
</div>
