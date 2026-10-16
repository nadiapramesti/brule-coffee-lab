<?php

namespace App\Livewire\Admin;

use App\Models\Coffee;
use App\Models\RoastProfile;
use App\Models\Traceability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CoffeeForm extends Component
{
    public const FLAVORS = ['floral', 'citrus', 'berry', 'chocolate', 'nutty', 'spice'];
    public const PROCESSES = ['Washed', 'Honey', 'Natural', 'Anaerobic', 'Wet-Hulled'];

    public ?int $coffeeId = null;

    public string $name = '';
    public string $slug = '';
    public string $story = '';
    public string $story_en = '';
    public string $stock = '0';
    public string $cupping = '';
    public bool $is_active = true;

    public string $farmer = '';
    public string $region = '';
    public string $altitude = '';
    public string $varietal = '';
    public string $process = 'Washed';
    public string $harvest_date = '';
    public string $q_grader = '';

    /** @var array<string,array> per level: on, price, agtron, flavors[], notes */
    public array $profiles = [];

    public function mount(?Coffee $coffee = null): void
    {
        foreach (array_keys(RoastProfile::LEVELS) as $level) {
            $this->profiles[$level] = [
                'on' => false, 'price' => '', 'agtron' => '',
                'flavors' => array_fill_keys(self::FLAVORS, 50), 'notes' => '',
            ];
        }

        if (! $coffee?->exists) {
            return;
        }

        $coffee->load('traceability', 'roastProfiles');
        $this->coffeeId = $coffee->id;
        $this->name = $coffee->name;
        $this->slug = $coffee->slug;
        $this->story = (string) $coffee->story;
        $this->story_en = (string) $coffee->story_en;
        $this->stock = (string) $coffee->stock_green_bean_gram;
        $this->cupping = $coffee->cupping_score !== null ? (string) $coffee->cupping_score : '';
        $this->is_active = $coffee->is_active;

        if ($t = $coffee->traceability) {
            $this->farmer = $t->farmer;
            $this->region = $t->region;
            $this->altitude = (string) $t->altitude;
            $this->varietal = $t->varietal;
            $this->process = $t->process;
            $this->harvest_date = $t->harvest_date?->format('Y-m-d') ?? '';
            $this->q_grader = (string) $t->q_grader;
        }

        foreach ($coffee->roastProfiles as $p) {
            $this->profiles[$p->level] = [
                'on' => true,
                'price' => (string) $p->price_per_100g,
                'agtron' => (string) $p->agtron,
                'flavors' => array_merge(array_fill_keys(self::FLAVORS, 0), $p->flavor_values ?? []),
                'notes' => implode(', ', $p->tasting_notes ?? []),
            ];
        }
    }

    public function updatedName(): void
    {
        if (! $this->coffeeId) {
            $this->slug = Str::slug($this->name);
        }
    }

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:90', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('coffees', 'slug')->ignore($this->coffeeId)],
            'story' => ['required', 'string', 'max:1500'],
            'story_en' => ['nullable', 'string', 'max:1500'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'cupping' => ['nullable', 'numeric', 'between:60,100'],
            'farmer' => ['required', 'string', 'max:120'],
            'region' => ['required', 'string', 'max:120'],
            'altitude' => ['required', 'integer', 'between:0,4000'],
            'varietal' => ['required', 'string', 'max:120'],
            'process' => ['required', Rule::in(self::PROCESSES)],
            'harvest_date' => ['nullable', 'date'],
            'q_grader' => ['nullable', 'string', 'max:120'],
        ];

        foreach (array_keys(RoastProfile::LEVELS) as $level) {
            $on = $this->profiles[$level]['on'] ?? false;
            $rules["profiles.$level.price"] = [Rule::requiredIf($on), 'nullable', 'integer', 'between:1000,5000000'];
            $rules["profiles.$level.agtron"] = ['nullable', 'integer', 'between:20,120'];
            $rules["profiles.$level.notes"] = [Rule::requiredIf($on), 'nullable', 'string', 'max:200'];
            $rules["profiles.$level.flavors.*"] = ['integer', 'between:0,100'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'slug.regex' => __('Slug hanya huruf kecil, angka, dan tanda hubung.'),
            'slug.unique' => __('Slug ini sudah dipakai kopi lain.'),
            'required' => __('Wajib diisi.'),
            'required_if' => __('Wajib diisi.'),
            'integer' => __('Harus angka bulat.'),
            'numeric' => __('Harus berupa angka.'),
            'between' => __('Nilai di luar rentang yang diizinkan.'),
            'max' => __('Terlalu panjang.'),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $chosen = collect($this->profiles)->filter(fn ($p) => $p['on']);
        if ($chosen->isEmpty()) {
            $this->addError('profiles', __('Aktifkan minimal satu level sangrai.'));

            return;
        }

        // Level yang sudah pernah dipesan tidak boleh dimatikan; riwayat order butuh profilnya.
        if ($this->coffeeId) {
            $locked = RoastProfile::where('coffee_id', $this->coffeeId)
                ->whereHas('orderItems')
                ->pluck('level')
                ->filter(fn ($l) => ! ($this->profiles[$l]['on'] ?? false));
            if ($locked->isNotEmpty()) {
                $this->addError('profiles', __('Level :l sudah pernah dipesan, tidak bisa dimatikan.', ['l' => $locked->map(fn ($l) => RoastProfile::LEVELS[$l])->join(', ')]));

                return;
            }
        }

        $wasNew = $this->coffeeId === null;

        $coffee = DB::transaction(function () use ($chosen, $wasNew) {
            $coffee = Coffee::updateOrCreate(['id' => $this->coffeeId], [
                'slug' => $this->slug,
                'name' => trim($this->name),
                'story' => trim($this->story),
                'story_en' => trim($this->story_en) ?: null,
                'cupping_score' => $this->cupping === '' ? null : (float) $this->cupping,
                'is_active' => $this->is_active,
            ]);

            // Stok tidak diset langsung: selisihnya dicatat sebagai pergerakan.
            $delta = (int) $this->stock - (int) $coffee->stock_green_bean_gram;
            if ($delta !== 0) {
                $coffee->moveStock($wasNew ? 'initial' : 'adjustment', $delta, $wasNew ? null : __('Diubah lewat form kopi'));
            }

            Traceability::updateOrCreate(['coffee_id' => $coffee->id], [
                'farmer' => trim($this->farmer),
                'region' => trim($this->region),
                'altitude' => (int) $this->altitude,
                'varietal' => trim($this->varietal),
                'process' => $this->process,
                'harvest_date' => $this->harvest_date ?: null,
                'q_grader' => trim($this->q_grader) ?: null,
            ]);

            foreach ($chosen as $level => $p) {
                $existing = $coffee->roastProfiles()->where('level', $level)->first();
                $coffee->roastProfiles()->updateOrCreate(['level' => $level], [
                    'agtron' => $p['agtron'] === '' ? null : (int) $p['agtron'],
                    'price_per_100g' => (int) $p['price'],
                    'flavor_values' => collect(self::FLAVORS)->mapWithKeys(fn ($f) => [$f => (int) ($p['flavors'][$f] ?? 0)])->all(),
                    'tasting_notes' => collect(explode(',', $p['notes']))->map(fn ($n) => trim($n))->filter()->values()->all(),
                    'roast_curve' => $existing?->roast_curve ?? RoastProfile::defaultCurve($level),
                ]);
            }

            $coffee->roastProfiles()->whereNotIn('level', $chosen->keys())->delete();

            return $coffee;
        });

        $this->dispatch('coffee-saved', name: $coffee->name, created: $wasNew);
    }

    public function render()
    {
        return view('livewire.admin.coffee-form');
    }
}
