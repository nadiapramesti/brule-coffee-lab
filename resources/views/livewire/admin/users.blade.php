@php
    $inp = 'w-full rounded border border-kraft/25 bg-transparent px-3 py-2.5 text-[14px] placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $tabs = ['' => __('Semua')] + collect(\App\Livewire\Admin\Users::ROLES)->all();
    $chip = ['admin' => 'border-[#E8782A]/50 text-ambertext', 'roaster' => 'border-bluelab/60 text-bluetext', 'customer' => 'border-kraft/30 text-kraft/70'];
    $desc = ['admin' => __('Akses penuh: semua menu panel, pengaturan, dan pengguna.'), 'roaster' => __('Hanya melihat dan menjalankan antrian roastery.'), 'customer' => __('Pembeli biasa: belanja dan lacak pesanan.')];
    $me = auth()->id();
@endphp
<div>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-[1.45rem] font-bold leading-tight">{{ __('Pengguna') }}</h1>
            <p class="mt-0.5 text-[12.5px] text-kraft/55">{{ __('Kelola siapa yang jadi admin, roaster, atau pembeli.') }}</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 rounded bg-bluelab px-4 py-2.5 text-[13.5px] font-semibold transition-colors hover:bg-[#234E94]">
            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M10 4v12M4 10h12"/></svg>{{ __('Tambah staf') }}
        </button>
    </div>

    @if ($notice)
        <p role="status" x-data x-init="setTimeout(() => $el.remove(), 6000)" class="mt-4 rounded border border-bluelab/50 bg-bluelab/10 px-4 py-2.5 text-[13px]">{{ $notice }}</p>
    @endif

    <section class="mt-4 rounded border border-kraft/20 bg-charcoal">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-kraft/15 px-3">
            <div class="flex flex-wrap gap-1" role="tablist">
                @foreach ($tabs as $k => $l)
                    <button role="tab" aria-selected="{{ $role === (string) $k ? 'true' : 'false' }}" wire:click="setRole('{{ $k }}')"
                            class="-mb-px border-b-2 px-3 py-3 text-[13px] transition-colors {{ $role === (string) $k ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">{{ $l }} <span class="krm-readout ml-1 rounded-[6px] bg-labwhite/[.07] px-1.5 text-[11px]">{{ $this->counts[$k] }}</span></button>
                @endforeach
            </div>
            <div class="py-2">
                <label class="sr-only" for="uq">{{ __('Cari pengguna') }}</label>
                <input id="uq" type="search" wire:model.live.debounce.350ms="search" placeholder="{{ __('Nama, email, atau telepon') }}" class="{{ $inp }} !py-2 !text-[13px] sm:w-64">
            </div>
        </div>

        @php $users = $this->users; @endphp
        <div wire:loading.class="opacity-60" class="transition-opacity">
            <div class="hidden gap-3 border-b border-kraft/10 px-4 py-2 text-[10.5px] uppercase tracking-[0.12em] text-kraft/45 krm-readout md:grid md:grid-cols-[minmax(0,2.2fr)_100px_minmax(0,1fr)_minmax(0,1fr)_70px_110px]">
                <span>{{ __('Pengguna') }}</span><span>{{ __('Role') }}</span><span>{{ __('Masuk lewat') }}</span><span>{{ __('Bergabung') }}</span><span class="text-right">{{ __('Pesanan') }}</span><span class="text-right">{{ __('Aksi') }}</span>
            </div>
            <ul class="divide-y divide-kraft/10">
                @forelse ($users as $u)
                    <li wire:key="u-{{ $u->id }}" class="grid items-center gap-x-3 gap-y-1.5 px-4 py-3 md:grid-cols-[minmax(0,2.2fr)_100px_minmax(0,1fr)_minmax(0,1fr)_70px_110px]">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-bluelab/80 font-display text-[13.5px] font-semibold">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-[13.5px] font-semibold">{{ $u->name }} @if ($u->id === $me)<span class="krm-readout ml-1 text-[10.5px] font-normal text-kraft/50">({{ __('kamu') }})</span>@endif</p>
                                <p class="truncate text-[12px] text-kraft/55">{{ $u->email }}</p>
                            </div>
                        </div>
                        <span class="w-fit whitespace-nowrap rounded-[6px] border px-2 py-0.5 text-[11px] {{ $chip[$u->role] ?? $chip['customer'] }}">{{ \App\Livewire\Admin\Users::ROLES[$u->role] ?? $u->role }}</span>
                        <p class="text-[12.5px] text-kraft/70"><span class="text-kraft/45 md:hidden">{{ __('Masuk lewat') }}: </span>{{ match ($u->provider) { 'google' => 'Google', 'facebook' => 'Facebook', default => 'Email' } }}</p>
                        <p class="krm-readout text-[12px] text-kraft/60"><span class="font-sans text-kraft/45 md:hidden">{{ __('Bergabung') }}: </span>{{ $u->created_at->translatedFormat('j M Y') }}</p>
                        <p class="krm-readout text-[12.5px] text-kraft/70 md:text-right"><span class="font-sans text-kraft/45 md:hidden">{{ __('Pesanan') }}: </span>{{ $u->orders_count }}</p>
                        <div class="flex md:justify-end">
                            @if ($u->id === $me)
                                <span class="px-3 py-1.5 text-[12px] text-kraft/35">—</span>
                            @else
                                <button wire:click="openRole({{ $u->id }})" class="rounded border border-kraft/30 px-3 py-1.5 text-[12.5px] transition-colors hover:border-kraft/70">{{ __('Ubah role') }}</button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-[13px] text-kraft/45">{{ __('Tidak ada pengguna yang cocok.') }}</li>
                @endforelse
            </ul>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-kraft/15 px-4 py-3 text-[12.5px] text-kraft/60">
            <span>{{ $users->total() ? __('Menampilkan :a–:b dari :t', ['a' => $users->firstItem(), 'b' => $users->lastItem(), 't' => $users->total()]) : '' }}</span>
            <div class="flex items-center gap-2">
                <button wire:click="previousPage" @disabled($users->onFirstPage()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Sebelumnya') }}">&larr;</button>
                <span class="krm-readout">{{ $users->currentPage() }}/{{ $users->lastPage() }}</span>
                <button wire:click="nextPage" @disabled(! $users->hasMorePages()) class="rounded border border-kraft/25 px-3 py-1.5 transition-colors enabled:hover:border-kraft/60 disabled:opacity-35" aria-label="{{ __('Berikutnya') }}">&rarr;</button>
            </div>
        </div>
    </section>

    {{-- Modal ubah role --}}
    @if ($this->editing)
        <div class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6" x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }" @keydown.escape.window="$wire.closeRole()">
            <div class="krm-fade absolute inset-0 bg-black/70 backdrop-blur-[2px]" wire:click="closeRole"></div>
            <form wire:submit="saveRole" role="dialog" aria-modal="true" aria-labelledby="ur-title" class="krm-pop relative w-full overflow-hidden rounded-t-[14px] border border-kraft/25 bg-surface shadow-[0_30px_80px_-20px_rgb(var(--shadow)/.95)] sm:max-w-[440px] sm:rounded-[14px]">
                <header class="border-b border-kraft/15 px-5 py-4">
                    <h2 id="ur-title" class="font-display text-[1.05rem] font-semibold">{{ __('Ubah role') }}</h2>
                    <p class="mt-0.5 truncate text-[12.5px] text-kraft/60">{{ $this->editing->name }} · {{ $this->editing->email }}</p>
                </header>
                <div class="space-y-2 px-5 py-4" role="radiogroup">
                    @foreach (\App\Livewire\Admin\Users::ROLES as $k => $l)
                        <label class="flex cursor-pointer items-start gap-3 rounded border px-3.5 py-3 transition-colors {{ $newRole === $k ? 'border-bluelab bg-bluelab/10' : 'border-kraft/20 hover:border-kraft/45' }}">
                            <input type="radio" wire:model.live="newRole" value="{{ $k }}" class="mt-1 h-4 w-4 border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                            <span><span class="block text-[13.5px] font-semibold">{{ $l }}</span><span class="block text-[12.5px] text-kraft/60">{{ $desc[$k] }}</span></span>
                        </label>
                    @endforeach
                    @error('newRole')<p class="text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                </div>
                <footer class="flex items-center justify-end gap-2 border-t border-kraft/15 px-5 py-3.5">
                    <button type="button" wire:click="closeRole" class="rounded px-4 py-2.5 text-[13.5px] text-kraft/70 hover:text-labwhite">{{ __('Batal') }}</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-6 py-2.5 font-display text-[14px] font-semibold hover:bg-[#234E94] disabled:opacity-60">{{ __('Simpan') }}</button>
                </footer>
            </form>
        </div>
    @endif

    {{-- Modal tambah staf --}}
    @if ($creating)
        <div class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6" x-data="{ init() { document.body.style.overflow = 'hidden'; }, destroy() { document.body.style.overflow = ''; } }" @keydown.escape.window="$wire.closeCreate()">
            <div class="krm-fade absolute inset-0 bg-black/70 backdrop-blur-[2px]" wire:click="closeCreate"></div>
            <form wire:submit="createStaff" role="dialog" aria-modal="true" aria-labelledby="uc-title" class="krm-pop relative w-full overflow-hidden rounded-t-[14px] border border-kraft/25 bg-surface shadow-[0_30px_80px_-20px_rgb(var(--shadow)/.95)] sm:max-w-[460px] sm:rounded-[14px]" novalidate>
                <header class="border-b border-kraft/15 px-5 py-4"><h2 id="uc-title" class="font-display text-[1.05rem] font-semibold">{{ __('Tambah staf') }}</h2></header>
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label for="un" class="text-[12.5px] text-kraft/75">{{ __('Nama lengkap') }}</label>
                        <input id="un" wire:model="name" autocomplete="off" placeholder="Dimas Prakoso" class="{{ $inp }} mt-1.5">
                        @error('name')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="ue" class="text-[12.5px] text-kraft/75">Email</label>
                        <input id="ue" type="email" wire:model="email" autocomplete="off" placeholder="roaster@kromatik.id" class="{{ $inp }} mt-1.5">
                        @error('email')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="ur" class="text-[12.5px] text-kraft/75">{{ __('Role') }}</label>
                            <select id="ur" wire:model="staffRole" class="{{ $inp }} mt-1.5"><option class="bg-surface" value="roaster">Roaster</option><option class="bg-surface" value="admin">Admin</option></select>
                        </div>
                        <div>
                            <label for="up" class="text-[12.5px] text-kraft/75">{{ __('Kata sandi sementara') }}</label>
                            <input id="up" type="text" wire:model="password" autocomplete="off" placeholder="{{ __('min. 8 karakter') }}" class="{{ $inp }} krm-readout mt-1.5">
                            @error('password')<p class="mt-1 text-[12px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <p class="text-[12px] leading-relaxed text-kraft/50">{{ __('Berikan kata sandi ini langsung ke orangnya, lalu minta dia menggantinya lewat halaman Profil.') }}</p>
                </div>
                <footer class="flex items-center justify-end gap-2 border-t border-kraft/15 px-5 py-3.5">
                    <button type="button" wire:click="closeCreate" class="rounded px-4 py-2.5 text-[13.5px] text-kraft/70 hover:text-labwhite">{{ __('Batal') }}</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-6 py-2.5 font-display text-[14px] font-semibold hover:bg-[#234E94] disabled:opacity-60">{{ __('Buat akun') }}</button>
                </footer>
            </form>
        </div>
    @endif
</div>
