<?php

namespace App\Livewire;

use App\Models\Coffee;
use App\Models\OrderItem;
use App\Models\RoastProfile;
use App\Support\Blend;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.brule')]
#[Title('Roast Builder - BRÛLÉ')]
class Builder extends Component
{
    public ?int $coffeeId = null;

    /** Index ke dalam daftar level yang tersedia untuk kopi terpilih. */
    public int $levelIndex = 0;

    public string $grind = 'whole_bean';

    public int $grams = 200;

    /** Biji kedua untuk blend (null = biji tunggal) dan porsi biji pertama (%). */
    public ?int $blendCoffeeId = null;

    public int $ratio = 70;

    public function mount(): void
    {
        $first = Coffee::active()
            ->when(request()->query('kopi'), fn ($q, $slug) => $q->where('slug', $slug))
            ->orderBy('id')->first()
            ?? Coffee::active()->orderBy('id')->first();

        $this->coffeeId = $first?->id;
        $this->levelIndex = (int) floor(max(0, $this->levels->count() - 1) / 2);
    }

    // =========================================================
    // Computed
    // =========================================================

    #[Computed]
    public function coffees(): Collection
    {
        return Coffee::active()->with('traceability')->orderBy('id')->get();
    }

    #[Computed]
    public function coffee(): ?Coffee
    {
        return $this->coffeeId
            ? Coffee::with('traceability')->find($this->coffeeId)
            : null;
    }

    /** Roast profile kopi ini, diurutkan dari paling terang ke paling gelap. */
    #[Computed]
    public function levels(): Collection
    {
        if (! $this->coffeeId) {
            return collect();
        }

        $order = array_keys(RoastProfile::LEVELS);

        return RoastProfile::where('coffee_id', $this->coffeeId)
            ->get()
            ->sortBy(fn ($p) => array_search($p->level, $order, true))
            ->values();
    }

    #[Computed]
    public function profile(): ?RoastProfile
    {
        return $this->levels->get($this->safeIndex());
    }

    #[Computed]
    public function flavorValues(): array
    {
        $empty = [
            'floral' => 0, 'citrus' => 0, 'berry' => 0,
            'chocolate' => 0, 'nutty' => 0, 'spice' => 0,
        ];
        $a = array_merge($empty, $this->profile?->flavor_values ?? []);

        if (! ($b = $this->blendProfile)) {
            return $a;
        }
        $b = array_merge($empty, $b->flavor_values ?? []);

        // Rata-rata berbobot sesuai porsi biji.
        return collect($a)->map(fn ($v, $k) => (int) round($v * $this->ratio / 100 + $b[$k] * (100 - $this->ratio) / 100))->all();
    }

    /** Biji lain yang levelnya sama dengan pilihan sekarang, jadi bisa disangrai dan dicampur bareng. */
    #[Computed]
    public function blendOptions(): Collection
    {
        if (! $this->profile) {
            return collect();
        }

        return RoastProfile::with('coffee.traceability')->where('level', $this->profile->level)
            ->where('coffee_id', '!=', $this->coffeeId)
            ->whereHas('coffee', fn ($q) => $q->where('is_active', true))
            ->get()->sortBy('coffee_id')->values();
    }

    /** Profil biji kedua; null bila bukan blend atau pilihannya tidak cocok lagi. */
    #[Computed]
    public function blendProfile(): ?RoastProfile
    {
        if (! $this->blendCoffeeId || ! Blend::validRatio($this->ratio)) {
            return null;
        }

        return $this->blendOptions->firstWhere('coffee_id', $this->blendCoffeeId);
    }

    #[Computed]
    public function price(): int
    {
        if (! $this->profile) {
            return 0;
        }

        return $this->blendProfile
            ? Blend::price($this->profile, $this->blendProfile, $this->grams, $this->ratio)
            : $this->profile->priceFor($this->grams);
    }

    #[Computed]
    public function stockOk(): bool
    {
        if (! $this->coffee) {
            return false;
        }
        if (! $this->blendProfile) {
            return $this->coffee->hasStockFor($this->grams);
        }
        [$ga, $gb] = Blend::split($this->grams, $this->ratio);

        return $this->coffee->hasStockFor($ga) && (bool) $this->blendProfile->coffee->hasStockFor($gb);
    }

    /** Catatan rasa: dua dari tiap biji untuk blend, supaya chip tidak menumpuk. */
    #[Computed]
    public function notes(): array
    {
        $a = (array) ($this->profile?->tasting_notes ?? []);
        if (! $this->blendProfile) {
            return $a;
        }

        return collect(array_slice($a, 0, 2))->merge(array_slice((array) ($this->blendProfile->tasting_notes ?? []), 0, 2))->unique()->values()->all();
    }

    /** Nama sumbu flavor wheel dalam bahasa aktif (digambar D3). */
    #[Computed]
    public function wheelLabels(): array
    {
        return [
            'citrus'    => __('Jeruk'),
            'floral'    => __('Bunga'),
            'berry'     => __('Beri'),
            'chocolate' => __('Cokelat'),
            'nutty'     => __('Kacang'),
            'spice'     => __('Rempah'),
        ];
    }

    #[Computed]
    public function grindOptions(): array
    {
        return OrderItem::GRINDS;
    }

    // =========================================================
    // Actions
    // =========================================================

    public function selectCoffee(int $id): void
    {
        $this->coffeeId = $id;

        // Ganti kopi bisa bikin jumlah level berubah, jadi index-nya
        // dijepit ulang supaya tidak menunjuk ke profil yang tidak ada.
        unset($this->levels, $this->profile, $this->coffee, $this->blendOptions, $this->blendProfile);
        $this->levelIndex = $this->safeIndex();
        $this->syncBlend();

        $this->pushToWheel();
    }

    public function updatedLevelIndex(): void
    {
        $this->levelIndex = $this->safeIndex();
        unset($this->profile, $this->blendOptions, $this->blendProfile);
        $this->syncBlend();
        $this->pushToWheel();
    }

    /** Pilih biji kedua (id kopi) atau null untuk kembali ke biji tunggal. */
    public function setBlend(?int $coffeeId): void
    {
        $this->blendCoffeeId = $coffeeId;
        unset($this->blendProfile);
        $this->syncBlend();
        $this->pushToWheel();
    }

    public function setRatio(int $ratio): void
    {
        if (Blend::validRatio($ratio)) {
            $this->ratio = $ratio;
            unset($this->blendProfile);
            $this->pushToWheel();
        }
    }

    /** Buang pilihan blend yang tidak cocok lagi setelah biji atau level berubah. */
    private function syncBlend(): void
    {
        if ($this->blendCoffeeId && ! $this->blendOptions->firstWhere('coffee_id', $this->blendCoffeeId)) {
            $this->blendCoffeeId = null;
        }
        unset($this->blendProfile);
    }

    /** Masukkan racikan ini ke keranjang. Harga tidak disimpan; keranjang menghitung ulang dari database. */
    public function addToCart()
    {
        abort_if(auth()->user()?->isStaff(), 403, __('Akun admin dan roaster tidak untuk belanja.'));
        abort_unless($this->profile && $this->stockOk, 422);

        $blend = $this->blendProfile;
        if (! \App\Support\Cart::add($this->profile->id, $this->grams, $this->grind, $blend?->id, $blend ? $this->ratio : null)) {
            $this->addError('cart', __('Keranjang penuh (maks :n item). Selesaikan checkout dulu.', ['n' => \App\Support\Cart::MAX_LINES]));

            return null;
        }

        return $this->redirectRoute('cart', navigate: true);
    }

    public function setLevel(int $index): void
    {
        $this->levelIndex = $index;
        $this->updatedLevelIndex();
    }

    /** Warna biji sangrai dari nilai Agtron: makin kecil makin gelap. */
    public function beanColor(?int $agtron): string
    {
        $t = max(0, min(1, (($agtron ?? 70) - 35) / 60));   // 0 = gelap, 1 = terang
        $dark = [43, 24, 16];
        $light = [205, 164, 108];
        $c = array_map(fn ($d, $l) => (int) round($d + ($l - $d) * $t), $dark, $light);

        return sprintf('rgb(%d,%d,%d)', ...$c);
    }

    public function setGrind(string $grind): void
    {
        if (array_key_exists($grind, OrderItem::GRINDS)) {
            $this->grind = $grind;
        }
    }

    public function setGrams(int $grams): void
    {
        if (in_array($grams, \App\Support\Cart::GRAMS, true)) {
            $this->grams = $grams;
        }
    }

    // =========================================================
    // Helper
    // =========================================================

    private function safeIndex(): int
    {
        $max = max(0, $this->levels->count() - 1);

        return max(0, min($this->levelIndex, $max));
    }

    private function pushToWheel(): void
    {
        $this->dispatch('flavor-updated', values: $this->flavorValues);
    }

    public function render()
    {
        return view('livewire.builder');
    }
}
