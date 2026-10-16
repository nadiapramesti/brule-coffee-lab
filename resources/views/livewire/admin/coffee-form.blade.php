@php
    $in = 'mt-1.5 w-full rounded border border-kraft/25 bg-transparent px-3 py-2.5 text-[14px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $lb = 'text-[12.5px] text-kraft/75';
    $er = 'mt-1 text-[12px] text-red-300';
    $card = 'rounded border border-kraft/15 p-4';
@endphp
<form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
    <header class="flex shrink-0 items-center justify-between gap-3 border-b border-kraft/15 px-5 py-3.5">
        <div>
            <h2 id="form-title" class="font-display text-[1.1rem] font-semibold leading-tight">{{ $coffeeId ? __('Ubah kopi') : __('Kopi baru') }}</h2>
            @if ($coffeeId)<p class="krm-readout text-[11px] text-kraft/50">{{ $slug }}</p>@endif
        </div>
        <button type="button" wire:click="$dispatch('close-coffee-form')" class="-mr-2 grid h-9 w-9 place-items-center rounded text-kraft/70 transition-colors hover:bg-white/[.06] hover:text-labwhite" aria-label="{{ __('Tutup') }}">
            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg>
        </button>
    </header>

    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-5 py-4">
        {{-- Info dasar --}}
        <section class="{{ $card }}">
            <h2 class="font-display text-[0.95rem] font-semibold">{{ __('Info dasar') }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="name" class="{{ $lb }}">{{ __('Nama kopi') }}</label>
                    <input id="name" wire:model.live.debounce.400ms="name" placeholder="Ethiopia Yirgacheffe Washed" class="{{ $in }}">
                    @error('name')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="slug" class="{{ $lb }}">Slug (URL)</label>
                    <input id="slug" wire:model="slug" @disabled($coffeeId) placeholder="ethiopia-yirgacheffe-washed" class="{{ $in }} krm-readout text-[14px] disabled:opacity-50">
                    @error('slug')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-3">
                <label for="story" class="{{ $lb }}">{{ __('Cerita kopi (Indonesia)') }}</label>
                <textarea id="story" rows="3" wire:model="story" placeholder="{{ __('Contoh: Dipetik dari kebun kecil di dataran tinggi Gayo, Aceh. Karakternya manis dan bersih.') }}" class="{{ $in }}"></textarea>
                @error('story')<p class="{{ $er }}">{{ $message }}</p>@enderror
            </div>
            <div class="mt-3">
                <label for="story_en" class="{{ $lb }}">{{ __('Cerita kopi (English)') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                <textarea id="story_en" rows="3" wire:model="story_en" placeholder="Example: Picked on small farms in the Gayo highlands, Aceh. Sweet and clean in the cup." class="{{ $in }}"></textarea>
                @error('story_en')<p class="{{ $er }}">{{ $message }}</p>@enderror
            </div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="stock" class="{{ $lb }}">{{ __('Stok biji mentah (gram)') }}</label>
                    <input id="stock" type="number" inputmode="numeric" wire:model="stock" placeholder="12000" class="{{ $in }} krm-readout">
                    @error('stock')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="cupping" class="{{ $lb }}">{{ __('Skor cupping') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                    <input id="cupping" type="number" step="0.1" inputmode="decimal" wire:model="cupping" placeholder="88.5" class="{{ $in }} krm-readout">
                    @error('cupping')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
            </div>
            <label class="mt-3 flex cursor-pointer items-center gap-3 text-[14px]">
                <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                {{ __('Tampilkan di toko') }}
            </label>
        </section>

        {{-- Traceability --}}
        <section class="{{ $card }}">
            <h2 class="font-display text-[0.95rem] font-semibold">{{ __('Asal-usul (traceability)') }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['farmer', __('Petani / koperasi'), 'text', 'Koperasi Tani Gayo Mandiri'],
                    ['region', __('Daerah'), 'text', 'Takengon, Aceh'],
                    ['altitude', __('Ketinggian (masl)'), 'number', '1450'],
                    ['varietal', __('Varietas'), 'text', 'Ateng, Typica'],
                    ['harvest_date', __('Tanggal panen'), 'date', ''],
                    ['q_grader', __('Q Grader'), 'text', 'Budi Santoso'],
                ] as [$f, $l, $t, $ph])
                    <div>
                        <label for="{{ $f }}" class="{{ $lb }}">{{ $l }}@if (in_array($f, ['harvest_date', 'q_grader'])) <span class="text-kraft/45">· {{ __('opsional') }}</span>@endif</label>
                        <input id="{{ $f }}" type="{{ $t }}" wire:model="{{ $f }}" @if ($ph) placeholder="{{ $ph }}" @endif class="{{ $in }}">
                        @error($f)<p class="{{ $er }}">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <div>
                    <label for="process" class="{{ $lb }}">{{ __('Proses') }}</label>
                    <select id="process" wire:model="process" class="{{ $in }}">
                        @foreach (\App\Livewire\Admin\CoffeeForm::PROCESSES as $p)<option class="bg-[#121212]" value="{{ $p }}">{{ $p }}</option>@endforeach
                    </select>
                    @error('process')<p class="{{ $er }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- Level sangrai --}}
        <section class="{{ $card }}">
            <h2 class="font-display text-[0.95rem] font-semibold">{{ __('Level sangrai & harga') }}</h2>
            <p class="mt-1 text-[12px] text-kraft/60">{{ __('Aktifkan level yang dijual. Harga per 100 gram.') }}</p>
            @error('profiles')<p class="{{ $er }}">{{ $message }}</p>@enderror

            <div class="mt-3 space-y-2.5">
                @foreach (\App\Models\RoastProfile::LEVELS as $lv => $label)
                    <div wire:key="lv-{{ $lv }}" x-data="{ on: @entangle("profiles.$lv.on") }" class="rounded border transition-colors" :class="on ? 'border-bluelab/50 bg-white/[.02]' : 'border-kraft/15'">
                        <label class="flex cursor-pointer items-center gap-3 px-3.5 py-2.5">
                            <input type="checkbox" x-model="on" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                            <span class="font-display text-[15px] font-semibold">{{ $label }}</span>
                        </label>
                        <div x-show="on" x-collapse x-cloak class="border-t border-kraft/15 px-3.5 pb-4 pt-3">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="pr-{{ $lv }}" class="{{ $lb }}">{{ __('Harga per 100 g (Rp)') }}</label>
                                    <input id="pr-{{ $lv }}" type="number" inputmode="numeric" wire:model="profiles.{{ $lv }}.price" placeholder="45000" class="{{ $in }} krm-readout">
                                    @error("profiles.$lv.price")<p class="{{ $er }}">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="ag-{{ $lv }}" class="{{ $lb }}">Agtron <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                                    <input id="ag-{{ $lv }}" type="number" inputmode="numeric" wire:model="profiles.{{ $lv }}.agtron" placeholder="75" class="{{ $in }} krm-readout">
                                    @error("profiles.$lv.agtron")<p class="{{ $er }}">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="nt-{{ $lv }}" class="{{ $lb }}">{{ __('Tasting notes (pisahkan dengan koma)') }}</label>
                                <input id="nt-{{ $lv }}" wire:model="profiles.{{ $lv }}.notes" placeholder="Jasmine, Blueberry, Bergamot" class="{{ $in }}">
                                @error("profiles.$lv.notes")<p class="{{ $er }}">{{ $message }}</p>@enderror
                            </div>
                            <div class="mt-4 grid gap-x-6 gap-y-2.5 sm:grid-cols-2">
                                @foreach (\App\Livewire\Admin\CoffeeForm::FLAVORS as $fl)
                                    <div x-data="{ v: @entangle("profiles.$lv.flavors.$fl") }">
                                        <div class="flex justify-between text-[12.5px] text-kraft/70"><label for="fl-{{ $lv }}-{{ $fl }}" class="capitalize">{{ $fl }}</label><span class="krm-readout" x-text="v"></span></div>
                                        <input id="fl-{{ $lv }}-{{ $fl }}" type="range" min="0" max="100" x-model.number="v" class="mt-1.5 w-full accent-[#2A5DB0]">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

    </div>

    <footer class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-kraft/15 bg-[#141414] px-5 py-3.5">
        <p class="hidden text-[12px] text-kraft/45 sm:block">{{ __('Level yang sudah pernah dipesan tidak bisa dimatikan.') }}</p>
        <div class="ml-auto flex items-center gap-2">
            <button type="button" wire:click="$dispatch('close-coffee-form')" class="rounded px-4 py-2.5 text-[13.5px] text-kraft/70 transition-colors hover:text-labwhite">{{ __('Batal') }}</button>
            <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-6 py-2.5 font-display text-[14px] font-semibold transition-colors hover:bg-[#234E94] disabled:opacity-60">
                <span wire:loading.remove wire:target="save">{{ __('Simpan') }}</span><span wire:loading wire:target="save">{{ __('Menyimpan…') }}</span>
            </button>
        </div>
    </footer>
</form>
