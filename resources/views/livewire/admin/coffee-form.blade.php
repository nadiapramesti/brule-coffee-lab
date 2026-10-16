@php
    $in = 'mt-2 w-full rounded border border-kraft/25 bg-transparent px-4 py-3 text-[15px] text-labwhite placeholder:text-kraft/35 focus:border-bluelab focus:outline-none focus:ring-2 focus:ring-bluelab/40';
    $lb = 'text-[13px] text-kraft/75';
    $er = 'mt-1.5 text-[12.5px] text-red-300';
    $card = 'rounded border border-kraft/20 bg-[#121212] p-5 sm:p-7';
@endphp
<x-admin-shell active="coffees">
<div class="max-w-[860px]">
    <a href="{{ route('admin.coffees') }}" wire:navigate class="text-[13.5px] text-kraft/70 underline-offset-4 hover:underline">&larr; {{ __('Kembali ke daftar') }}</a>
    <p class="krm-readout mt-5 text-[12px] tracking-[0.18em] text-kraft/60">{{ __('ADMIN') }}</p>
    <h1 class="krm-h2 mt-2 font-bold">{{ $coffeeId ? __('Ubah kopi') : __('Kopi baru') }}</h1>

    <form wire:submit="save" class="mt-8 space-y-6">
        {{-- Info dasar --}}
        <section class="{{ $card }}">
            <h2 class="font-display text-[1.05rem] font-semibold">{{ __('Info dasar') }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
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
            <div class="mt-5">
                <label for="story" class="{{ $lb }}">{{ __('Cerita kopi (Indonesia)') }}</label>
                <textarea id="story" rows="4" wire:model="story" placeholder="{{ __('Contoh: Dipetik dari kebun kecil di dataran tinggi Gayo, Aceh. Karakternya manis dan bersih.') }}" class="{{ $in }}"></textarea>
                @error('story')<p class="{{ $er }}">{{ $message }}</p>@enderror
            </div>
            <div class="mt-5">
                <label for="story_en" class="{{ $lb }}">{{ __('Cerita kopi (English)') }} <span class="text-kraft/45">· {{ __('opsional') }}</span></label>
                <textarea id="story_en" rows="4" wire:model="story_en" placeholder="Example: Picked on small farms in the Gayo highlands, Aceh. Sweet and clean in the cup." class="{{ $in }}"></textarea>
                @error('story_en')<p class="{{ $er }}">{{ $message }}</p>@enderror
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
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
            <label class="mt-5 flex cursor-pointer items-center gap-3 text-[14px]">
                <input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                {{ __('Tampilkan di toko') }}
            </label>
        </section>

        {{-- Traceability --}}
        <section class="{{ $card }}">
            <h2 class="font-display text-[1.05rem] font-semibold">{{ __('Asal-usul (traceability)') }}</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
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
            <h2 class="font-display text-[1.05rem] font-semibold">{{ __('Level sangrai & harga') }}</h2>
            <p class="mt-1 text-[13px] text-kraft/60">{{ __('Aktifkan level yang dijual. Harga per 100 gram.') }}</p>
            @error('profiles')<p class="{{ $er }}">{{ $message }}</p>@enderror

            <div class="mt-5 space-y-4">
                @foreach (\App\Models\RoastProfile::LEVELS as $lv => $label)
                    <div wire:key="lv-{{ $lv }}" x-data="{ on: @entangle("profiles.$lv.on") }" class="rounded border transition-colors" :class="on ? 'border-bluelab/50 bg-white/[.02]' : 'border-kraft/15'">
                        <label class="flex cursor-pointer items-center gap-3 px-4 py-3.5">
                            <input type="checkbox" x-model="on" class="h-4 w-4 rounded border-kraft/40 bg-transparent text-bluelab focus:ring-bluelab/40">
                            <span class="font-display text-[15px] font-semibold">{{ $label }}</span>
                        </label>
                        <div x-show="on" x-collapse x-cloak class="border-t border-kraft/15 px-4 pb-5 pt-4">
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
                            <div class="mt-5 grid gap-x-6 gap-y-3 sm:grid-cols-2">
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

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" wire:loading.attr="disabled" class="rounded bg-bluelab px-7 py-3.5 font-display text-[15px] font-semibold transition-colors hover:bg-[#234E94] disabled:opacity-60">
                <span wire:loading.remove wire:target="save">{{ __('Simpan') }}</span><span wire:loading wire:target="save">{{ __('Menyimpan…') }}</span>
            </button>
            <a href="{{ route('admin.coffees') }}" wire:navigate class="px-4 py-3.5 text-[14px] text-kraft/70 hover:text-labwhite">{{ __('Batal') }}</a>
        </div>
    </form>
</div>
</x-admin-shell>
