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
            ['at' => 0.109, 'height' => 0.30, 'width' => 0.0042, 'rt' => 1.42,  'compound' => 'asetaldehida',    'reads' => 'Segar'],
            ['at' => 0.236, 'height' => 0.74, 'width' => 0.0052, 'rt' => 3.07,  'compound' => 'limonena',        'reads' => 'Jeruk'],
            ['at' => 0.358, 'height' => 0.41, 'width' => 0.0046, 'rt' => 4.65,  'compound' => 'etil butanoat',   'reads' => 'Beri'],
            ['at' => 0.483, 'height' => 0.63, 'width' => 0.0050, 'rt' => 6.28,  'compound' => 'linalool',        'reads' => 'Melati'],
            ['at' => 0.624, 'height' => 0.97, 'width' => 0.0068, 'rt' => 8.11,  'compound' => 'furfuriltiol',    'reads' => 'Kakao'],
            ['at' => 0.749, 'height' => 0.52, 'width' => 0.0048, 'rt' => 9.74,  'compound' => 'pirazina',        'reads' => 'Kacang'],
            ['at' => 0.915, 'height' => 0.36, 'width' => 0.0056, 'rt' => 11.90, 'compound' => '4-vinilguaiakol', 'reads' => 'Cengkeh'],
        ];
    }

    /** Tahapan produksi, sama persis dengan status pesanan di model Order. */
    #[Computed]
    public function stages(): array
    {
        return [
            ['name' => 'Antrian',   'note' => 'Pesanan masuk papan roastery, biji mentah ditimbang.'],
            ['name' => 'Sangrai',   'note' => 'Satu batch untuk satu pesanan, mengikuti kurva yang kamu pilih.'],
            ['name' => 'Giling',    'note' => 'Digiling di ukuran alat seduhmu. Pilih biji utuh kalau punya grinder.'],
            ['name' => 'Kemas',     'note' => 'Masuk kantong berkatup, tanggal sangrai dicetak hari itu juga.'],
            ['name' => 'Kirim',     'note' => 'Berangkat maksimal 24 jam setelah biji keluar dari mesin.'],
        ];
    }

    public function render()
    {
        return view('livewire.landing');
    }
}
