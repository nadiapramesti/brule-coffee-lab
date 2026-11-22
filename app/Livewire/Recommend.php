<?php

namespace App\Livewire;

use App\Models\Coffee;
use App\Models\RoastProfile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Kuis tiga pertanyaan untuk pelanggan yang bingung memilih kopi.
 * Jawaban dicocokkan dengan nilai rasa (flavor_values) tiap roast profile,
 * lalu hasilnya menaut ke Roast Builder dengan kopi dan level yang sudah terpilih.
 */
#[Layout('layouts.brule')]
#[Title('Temukan kopimu - BRÛLÉ')]
class Recommend extends Component
{
    /** Pertanyaan 1: cara minum. Nilai = geseran level (0 = tidak bergeser, 1 = satu level lebih gelap). */
    public const DRINK = ['hitam' => 0, 'susu' => 1];

    /** Pertanyaan 2: rasa favorit => kunci flavor_values yang dihitung. */
    public const TASTE = [
        'buah'    => ['citrus', 'berry'],
        'bunga'   => ['floral'],
        'cokelat' => ['chocolate'],
        'kacang'  => ['nutty', 'spice'],
    ];

    /** Pertanyaan 3: toleransi asam => indeks level target (0 = Light ... 4 = Dark). */
    public const ACID = ['suka' => 0, 'sedikit' => 2, 'tidak' => 3];

    /** @var array<string,string> */
    public array $answers = [];

    public int $step = 1;

    /** @return array<int,array{key:string,title:string,options:array<string,string>}> */
    public function questions(): array
    {
        return [
            1 => ['key' => 'drink', 'title' => __('Biasanya kamu minum kopi seperti apa?'), 'options' => [
                'hitam' => __('Hitam, tanpa susu'),
                'susu'  => __('Pakai susu atau gula'),
            ]],
            2 => ['key' => 'taste', 'title' => __('Rasa mana yang paling kamu suka?'), 'options' => [
                'buah'    => __('Buah segar: jeruk, beri'),
                'bunga'   => __('Harum bunga, ringan seperti teh'),
                'cokelat' => __('Cokelat dan manis'),
                'kacang'  => __('Kacang dan rempah, pekat'),
            ]],
            3 => ['key' => 'acid', 'title' => __('Seberapa suka rasa asam yang segar?'), 'options' => [
                'suka'    => __('Suka, makin segar makin enak'),
                'sedikit' => __('Sedikit saja'),
                'tidak'   => __('Tidak suka, pilih yang mantap'),
            ]],
        ];
    }

    public function answer(string $value): void
    {
        $q = $this->questions()[$this->step] ?? null;

        if (! $q || ! array_key_exists($value, $q['options'])) {
            return;
        }

        $this->answers[$q['key']] = $value;
        $this->step = min($this->step + 1, 4);
        unset($this->results);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function restart(): void
    {
        $this->answers = [];
        $this->step = 1;
        unset($this->results);
    }

    /**
     * Tiga rekomendasi teratas: satu roast profile terbaik per kopi.
     *
     * @return Collection<int,array{coffee:Coffee,profile:RoastProfile,score:int,why:string}>
     */
    #[Computed]
    public function results(): Collection
    {
        if (count($this->answers) < 3) {
            return collect();
        }

        $keys = self::TASTE[$this->answers['taste']];
        $target = min(4, self::ACID[$this->answers['acid']] + self::DRINK[$this->answers['drink']]);
        $order = array_keys(RoastProfile::LEVELS);

        return Coffee::active()->with('traceability', 'roastProfiles')->orderBy('id')->get()
            ->map(function (Coffee $coffee) use ($keys, $target, $order) {
                $best = $coffee->roastProfiles->map(function (RoastProfile $p) use ($keys, $target, $order) {
                    $flavor = collect($keys)->avg(fn ($k) => (int) ($p->flavor_values[$k] ?? 0));
                    $distance = abs((int) array_search($p->level, $order, true) - $target);

                    return ['profile' => $p, 'score' => (int) round($flavor - 12 * $distance)];
                })->sortByDesc('score')->first();

                return $best ? ['coffee' => $coffee, 'profile' => $best['profile'], 'score' => $best['score']] : null;
            })
            ->filter()
            ->sortByDesc('score')
            ->take(3)
            ->values()
            ->map(fn ($r) => $r + ['why' => collect($r['profile']->tasting_notes ?? [])->take(3)->implode(', ')]);
    }

    public function render()
    {
        return view('livewire.recommend');
    }
}
