<div class="krm-auth mx-auto max-w-[860px] px-6 pb-24 pt-12 md:pt-16">
    @php $u = auth()->user(); @endphp

    <p class="krm-readout text-[12px] tracking-[0.18em] text-kraft/70" data-reveal>{{ __('AKUN') }}</p>
    <h1 class="krm-h2 mt-2 font-bold" data-reveal style="--d:60ms">{{ __('Profil') }}</h1>

    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 rounded border border-kraft/20 bg-surface px-5 py-4" data-reveal style="--d:120ms">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-bluelab font-display text-[1.1rem] font-semibold">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
        <div class="min-w-0 flex-1">
            <p class="truncate font-display text-[1.05rem] font-semibold">{{ $u->name }}</p>
            <p class="truncate text-[13px] text-kraft/65">{{ $u->email }}</p>
        </div>
        <span class="krm-readout rounded-[6px] border border-kraft/25 px-2 py-0.5 text-[11px] uppercase tracking-wider text-kraft/70">{{ $u->role }}</span>
        <a href="{{ route('dashboard') }}" wire:navigate class="text-[13.5px] underline-offset-4 hover:underline">{{ $u->isRoaster() ? __('Antrian') : __('Akun saya') }} &rarr;</a>
    </div>

    <div class="mt-6 space-y-5">
        @foreach (['update-profile-information-form', 'update-password-form', 'delete-user-form'] as $i => $form)
            <div class="rounded border {{ $form === 'delete-user-form' ? 'border-redtext/25' : 'border-kraft/20' }} p-6 sm:p-8" data-reveal style="--d:{{ 180 + $i * 80 }}ms">
                <div class="max-w-xl">
                    <livewire:is :component="'profile.' . $form" />
                </div>
            </div>
        @endforeach
    </div>
</div>
