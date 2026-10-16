<div class="mx-auto max-w-[860px] px-6 pb-24 pt-12 md:pt-16">
    @php $u = auth()->user(); @endphp
    <div class="flex flex-wrap items-end justify-between gap-4" data-reveal>
        <div>
            <p class="krm-readout text-[12px] tracking-[0.16em] text-kraft/55">{{ __('AKUN SAYA') }}</p>
            <h1 class="krm-h2 mt-1 font-bold">{{ __('Halo, :name', ['name' => \Illuminate\Support\Str::before($u->name, ' ')]) }}</h1>
            <p class="mt-1 text-[13.5px] text-kraft/60">{{ $u->email }}</p>
        </div>
        <a href="{{ route('profile') }}" wire:navigate class="text-[13.5px] text-kraft/70 underline-offset-4 transition-colors hover:text-labwhite hover:underline">{{ __('Ubah profil') }} &rarr;</a>
    </div>

    {{-- Tab --}}
    <div class="mt-8 flex gap-6 border-b border-kraft/20" role="tablist">
        @foreach (['pesanan' => __('Pesanan'), 'alamat' => __('Alamat')] as $key => $name)
            <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    class="-mb-px border-b-2 pb-3 text-[14.5px] font-semibold transition-colors {{ $tab === $key ? 'border-bluelab text-labwhite' : 'border-transparent text-kraft/60 hover:text-labwhite' }}">
                {{ $name }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'pesanan')
        <div class="mt-6 flex flex-wrap gap-2" role="group" aria-label="{{ __('Saring pesanan') }}">
            @foreach (['semua' => __('Semua'), 'aktif' => __('Berjalan'), 'selesai' => __('Selesai'), 'batal' => __('Dibatalkan')] as $key => $name)
                <button type="button" wire:click="$set('filter', '{{ $key }}')" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}"
                        class="rounded border px-3.5 py-1.5 text-[13px] transition-colors {{ $filter === $key ? 'border-bluelab bg-bluelab text-paper' : 'border-kraft/25 text-kraft/80 hover:border-kraft/60' }}">{{ $name }}</button>
            @endforeach
        </div>

        @if ($notice)
            <p class="mt-5 rounded border border-kraft/25 px-4 py-3 text-[13.5px] text-kraft/80" role="status">{{ $notice }}</p>
        @endif

        @forelse ($this->orders as $o)
            @php
                $tone = match ($o->status) { 'completed' => 'text-[#2f7a4a]', 'cancelled' => 'text-kraft/55', 'pending_payment' => 'text-[#a8741a]', default => 'text-bluelab' };
            @endphp
            <article class="mt-4 overflow-hidden rounded border border-kraft/20 bg-surface" wire:key="o-{{ $o->id }}">
                <div class="flex flex-wrap items-start justify-between gap-3 p-5">
                    <div class="min-w-0">
                        <p class="krm-readout text-[12.5px] text-kraft/60">{{ $o->order_code }} · {{ $o->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</p>
                        <p class="mt-1.5 text-[14.5px] font-semibold">{{ $o->itemSummary() }}</p>
                        <p class="mt-1 text-[13px] font-semibold {{ $tone }}">
                            {{ $o->status_label }}@if ($o->isCod()) <span class="krm-readout ml-1.5 rounded-[6px] border border-kraft/35 px-1.5 py-0.5 text-[10.5px] font-normal text-kraft/70">COD</span>@endif
                        </p>
                    </div>
                    <p class="krm-readout text-[1.15rem]">Rp{{ number_format($o->total, 0, ',', '.') }}</p>
                </div>
                @foreach ($this->reviewable($o) as $rc)
                    <div class="border-t border-dashed border-kraft/25 px-5 py-3.5" wire:key="rv-{{ $o->id }}-{{ $rc->coffee->id }}">
                        @if ($reviewCoffee === $rc->coffee->id)
                            <form wire:submit="saveReview" class="space-y-3">
                                <p class="text-[13.5px] font-semibold">{{ __('Ulasanmu untuk :n', ['n' => $rc->coffee->name]) }}</p>
                                <div class="flex items-center gap-1" role="radiogroup" aria-label="{{ __('Jumlah bintang') }}">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="$set('rating', {{ $i }})" role="radio" aria-checked="{{ $rating === $i ? 'true' : 'false' }}" aria-label="{{ $i }}"
                                                class="rounded p-0.5 text-[#D9A441] transition-transform hover:scale-110 focus-visible:outline focus-visible:outline-2 focus-visible:outline-bluelab">
                                            <svg viewBox="0 0 20 20" class="h-7 w-7" aria-hidden="true"><path d="M10 1.8l2.4 5.2 5.7.6-4.2 3.9 1.2 5.6L10 14.2l-5.1 2.9 1.2-5.6L1.9 7.6l5.7-.6L10 1.8z" fill="{{ $rating >= $i ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
                                        </button>
                                    @endfor
                                </div>
                                @error('rating')<p class="text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                                <div>
                                    <label for="rb-{{ $o->id }}" class="sr-only">{{ __('Tulis ulasan') }}</label>
                                    <textarea id="rb-{{ $o->id }}" rows="3" wire:model="reviewBody" maxlength="600"
                                              placeholder="{{ __('Contoh: Aromanya wangi, rasa buah beri terasa jelas di seduhan V60.') }}"
                                              class="w-full rounded border border-kraft/25 bg-transparent px-4 py-3 text-[14px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40"></textarea>
                                    @error('reviewBody')<p class="mt-1 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="rounded bg-bluelab px-4 py-2 font-display text-[13.5px] font-semibold text-paper transition-colors hover:bg-[#234E94]">{{ __('Kirim ulasan') }}</button>
                                    <button type="button" wire:click="cancelReview" class="rounded border border-kraft/30 px-4 py-2 text-[13.5px] text-kraft/80 transition-colors hover:border-kraft/60">{{ __('Batal') }}</button>
                                </div>
                            </form>
                        @else
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="flex min-w-0 items-center gap-2 text-[13.5px] text-kraft/80">
                                    @if ($rc->review)<x-stars :value="$rc->review->rating" size="h-3.5 w-3.5" />@endif
                                    <span class="truncate">{{ $rc->coffee->name }}</span>
                                </p>
                                <button type="button" wire:click="openReview({{ $rc->coffee->id }})" class="text-[13px] font-semibold text-bluetext underline-offset-4 hover:underline">
                                    {{ $rc->review ? __('Ubah ulasan') : __('Beri ulasan') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
                <div class="flex flex-wrap gap-3 border-t border-dashed border-kraft/25 px-5 py-3.5">
                    <a href="{{ route('track', $o->order_code) }}" wire:navigate class="rounded border border-kraft/35 px-4 py-2 font-display text-[13.5px] font-semibold text-kraft transition-colors hover:border-kraft/70 hover:text-labwhite">
                        {{ $o->status === 'pending_payment' ? __('Bayar / lacak') : __('Lacak') }}
                    </a>
                    <button type="button" wire:click="reorder('{{ $o->order_code }}')" wire:loading.attr="disabled"
                            class="rounded bg-bluelab px-4 py-2 font-display text-[13.5px] font-semibold text-paper transition-colors hover:bg-[#234E94] disabled:opacity-60">
                        {{ __('Beli lagi') }}
                    </button>
                </div>
            </article>
        @empty
            <div class="mt-6 rounded border border-dashed border-kraft/30 px-6 py-12 text-center">
                <p class="font-display text-[1.1rem] font-semibold">{{ $filter === 'semua' ? __('Belum ada pesanan') : __('Tidak ada pesanan di kategori ini') }}</p>
                <p class="mx-auto mt-2 max-w-[40ch] text-[14px] text-kraft/65">{{ __('Pilih biji, tentukan level sangrainya, dan pesanan pertamamu muncul di sini.') }}</p>
                <a href="{{ route('builder') }}" wire:navigate class="mt-5 inline-block rounded bg-bluelab px-5 py-2.5 font-display text-[14px] font-semibold text-paper transition-colors hover:bg-[#234E94]">{{ __('Racik kopimu') }}</a>
            </div>
        @endforelse

        @if ($this->orders->hasPages())
            <div class="mt-6 flex items-center justify-between text-[13.5px] text-kraft/70">
                <button type="button" wire:click="previousPage" @disabled($this->orders->onFirstPage()) class="rounded border border-kraft/25 px-4 py-2 transition-colors enabled:hover:border-kraft/60 disabled:opacity-40">&larr; {{ __('Sebelumnya') }}</button>
                <span class="krm-readout text-[12px]">{{ $this->orders->currentPage() }} / {{ $this->orders->lastPage() }}</span>
                <button type="button" wire:click="nextPage" @disabled(! $this->orders->hasMorePages()) class="rounded border border-kraft/25 px-4 py-2 transition-colors enabled:hover:border-kraft/60 disabled:opacity-40">{{ __('Berikutnya') }} &rarr;</button>
            </div>
        @endif
    @else
        <div class="mt-6">
            <p class="max-w-[56ch] text-[14px] leading-relaxed text-kraft/70">{{ __('Alamat yang tersimpan di sini bisa dipilih sekali klik saat checkout. Alamat utama terisi otomatis.') }}</p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @foreach ($this->addresses as $a)
                    <div class="flex flex-col rounded border bg-surface p-5 {{ $a->is_default ? 'border-bluelab/60' : 'border-kraft/20' }}" wire:key="a-{{ $a->id }}">
                        <div class="flex items-center gap-2">
                            <p class="font-display text-[1rem] font-semibold">{{ $a->label }}</p>
                            @if ($a->is_default)<span class="krm-readout rounded-[6px] bg-bluelab/15 px-1.5 py-0.5 text-[10.5px] text-bluelab">{{ __('UTAMA') }}</span>@endif
                        </div>
                        <p class="mt-2 text-[13.5px]">{{ $a->recipient }} · <span class="krm-readout text-[12.5px]">{{ $a->phone }}</span></p>
                        <p class="mt-1 flex-1 text-[13.5px] leading-relaxed text-kraft/70">{{ $a->address }}</p>
                        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-[13px]">
                            <button type="button" wire:click="editAddress({{ $a->id }})" class="text-kraft/75 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Ubah') }}</button>
                            @unless ($a->is_default)
                                <button type="button" wire:click="setDefault({{ $a->id }})" class="text-kraft/75 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Jadikan utama') }}</button>
                            @endunless
                            <button type="button" wire:click="deleteAddress({{ $a->id }})" wire:confirm="{{ __('Hapus alamat ini?') }}" class="text-kraft/55 underline-offset-4 hover:text-labwhite hover:underline">{{ __('Hapus') }}</button>
                        </div>
                    </div>
                @endforeach

                @if (! $formOpen && $this->addresses->count() < \App\Models\Address::MAX_PER_USER)
                    <button type="button" wire:click="newAddress"
                            class="grid min-h-[150px] place-items-center rounded border border-dashed border-kraft/35 p-5 text-[14px] font-semibold text-kraft/75 transition-colors hover:border-kraft/70 hover:text-labwhite">
                        + {{ __('Tambah alamat') }}
                    </button>
                @endif
            </div>

            @if ($formOpen)
                <form wire:submit="saveAddress" class="mt-6 space-y-5 rounded border border-kraft/25 bg-surface p-6">
                    <p class="font-display text-[1.05rem] font-semibold">{{ $editing ? __('Ubah alamat') : __('Alamat baru') }}</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="a-label" class="text-[13px] text-kraft/75">{{ __('Nama alamat') }}</label>
                            <input id="a-label" wire:model="label" placeholder="{{ __('Contoh: Rumah, Kantor') }}" maxlength="40"
                                   class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                            @error('label')<p class="mt-1.5 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="a-rec" class="text-[13px] text-kraft/75">{{ __('Nama penerima') }}</label>
                            <input id="a-rec" wire:model="recipient" placeholder="{{ __('Contoh: Nadia Pramesti') }}" autocomplete="name"
                                   class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                            @error('recipient')<p class="mt-1.5 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="a-phone" class="text-[13px] text-kraft/75">{{ __('Nomor WhatsApp') }}</label>
                            <input id="a-phone" wire:model="phone" inputmode="tel" placeholder="0812xxxxxxxx" autocomplete="tel"
                                   class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40">
                            @error('phone')<p class="mt-1.5 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="a-addr" class="text-[13px] text-kraft/75">{{ __('Alamat lengkap') }}</label>
                            <textarea id="a-addr" rows="3" wire:model="address" placeholder="{{ __('Contoh: Jl. Dago No. 25, Coblong, Kota Bandung 40135') }}"
                                      class="mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40"></textarea>
                            @error('address')<p class="mt-1.5 text-[12.5px] text-redtext">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="rounded bg-bluelab px-5 py-2.5 font-display text-[14px] font-semibold text-paper transition-colors hover:bg-[#234E94]">{{ __('Simpan') }}</button>
                        <button type="button" wire:click="cancelForm" class="rounded border border-kraft/30 px-5 py-2.5 text-[14px] text-kraft/80 transition-colors hover:border-kraft/60">{{ __('Batal') }}</button>
                    </div>
                </form>
            @endif
        </div>
    @endif
</div>
