<?php

namespace App\Livewire;

use App\Models\Coffee;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kromatik')]
#[Title('KROMATIK - Specialty Coffee Lab')]
class Landing extends Component
{
    #[Computed]
    public function coffees(): Collection
    {
        return Coffee::active()
            ->with(['traceability', 'roastProfiles'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Puncak untuk kromatogram di hero.
     *
     * Senyawa-senyawa ini betulan ada di kopi dan betulan dipakai
     * untuk menilai aroma di laboratorium. Retention time-nya
     * ilustratif, tapi urutannya mengikuti urutan elusi yang wajar:
     * senyawa ringan keluar duluan, yang berat belakangan.
     */
    #[Computed]
    public function peaks(): array
    {
        return [
            ['at' => 0.109, 'height' => 0.30, 'width' => 0.0042, 'rt' => 1.42,  'compound' => 'asetaldehida',    'reads' => __('Segar')],
            ['at' => 0.236, 'height' => 0.74, 'width' => 0.0052, 'rt' => 3.07,  'compound' => 'limonena',        'reads' => __('Jeruk')],
            ['at' => 0.358, 'height' => 0.41, 'width' => 0.0046, 'rt' => 4.65,  'compound' => 'etil butanoat',   'reads' => __('Beri')],
            ['at' => 0.483, 'height' => 0.63, 'width' => 0.0050, 'rt' => 6.28,  'compound' => 'linalool',        'reads' => __('Melati')],
            ['at' => 0.624, 'height' => 0.97, 'width' => 0.0068, 'rt' => 8.11,  'compound' => 'furfuriltiol',    'reads' => __('Kakao')],
            ['at' => 0.749, 'height' => 0.52, 'width' => 0.0048, 'rt' => 9.74,  'compound' => 'pirazina',        'reads' => __('Kacang')],
            ['at' => 0.915, 'height' => 0.36, 'width' => 0.0056, 'rt' => 11.90, 'compound' => '4-vinilguaiakol', 'reads' => __('Cengkeh')],
        ];
    }

    /** Teks yang digambar JS (bukan Blade), diterjemahkan di sini. */
    #[Computed]
    public function chromatogramText(): array
    {
        return [
            'axis' => __('waktu retensi (menit)'),
            'aria' => __('Kromatogram aroma kopi: '),
        ];
    }

    /** Tahapan produksi, sama persis dengan status pesanan di model Order. */
    #[Computed]
    public function stages(): array
    {
        return [
            ['name' => __('Antrian'),   'note' => __('Pesanan masuk papan roastery, biji mentah ditimbang.'), 'img' => 'steps/01-antrian-q12-dark.jpg'],
            ['name' => __('Sangrai'),   'note' => __('Satu batch untuk satu pesanan, mengikuti kurva yang kamu pilih.'), 'img' => 'steps/02-sangrai-210c.jpg'],
            ['name' => __('Giling'),    'note' => __('Digiling di ukuran alat seduhmu. Pilih biji utuh kalau punya grinder.'), 'img' => 'steps/03-giling.jpg'],
            ['name' => __('Kemas'),     'note' => __('Masuk kantong berkatup, tanggal sangrai dicetak hari itu juga.'), 'img' => 'steps/04-kemas-black-kromatik.jpg'],
            ['name' => __('Kirim'),     'note' => __('Berangkat maksimal 24 jam setelah biji keluar dari mesin.'), 'img' => 'steps/05-kirim.jpg'],
        ];
    }

    public function render()
    {
        return view('livewire.landing');
    }
}
