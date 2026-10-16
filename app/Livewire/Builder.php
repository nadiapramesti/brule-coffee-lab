<?php

namespace App\Livewire;

use App\Models\Coffee;
use App\Models\OrderItem;
use App\Models\RoastProfile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kromatik')]
#[Title('Roast Builder - KROMATIK')]
class Builder extends Component
{
    public ?int $coffeeId = null;

    /** Index ke dalam daftar level yang tersedia untuk kopi terpilih. */
    public int $levelIndex = 0;

    public string $grind = 'whole_bean';

    public int $grams = 200;

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
        return Coffee::active()->orderBy('id')->get();
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

        return array_merge($empty, $this->profile?->flavor_values ?? []);
    }

    #[Computed]
    public function price(): int
    {
        return $this->profile?->priceFor($this->grams) ?? 0;
    }

    #[Computed]
    public function stockOk(): bool
    {
        return (bool) $this->coffee?->hasStockFor($this->grams);
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
        unset($this->levels, $this->profile, $this->coffee);
        $this->levelIndex = $this->safeIndex();

        $this->pushToWheel();
    }

    public function updatedLevelIndex(): void
    {
        $this->levelIndex = $this->safeIndex();
        $this->pushToWheel();
    }

    public function setGrind(string $grind): void
    {
        if (array_key_exists($grind, OrderItem::GRINDS)) {
            $this->grind = $grind;
        }
    }

    public function setGrams(int $grams): void
    {
        if (in_array($grams, [200, 500, 1000], true)) {
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
