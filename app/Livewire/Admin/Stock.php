<?php

namespace App\Livewire\Admin;

use App\Models\Coffee;
use App\Models\StockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
#[Title('Stok - KROMATIK')]
class Stock extends Component
{
    use WithPagination;


    #[Url] public string $tab = 'overview';
    #[Url(as: 'kopi')] public ?int $coffeeFilter = null;
    #[Url(as: 'jenis')] public string $typeFilter = '';

    // Modal penyesuaian stok
    public ?int $adjusting = null;
    public string $mode = 'restock';   // restock | count | waste
    public string $amount = '';
    public string $reason = '';
    public ?string $notice = null;

    public function updating($name): void
    {
        if (in_array($name, ['coffeeFilter', 'typeFilter'], true)) {
            $this->resetPage();
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'history' ? 'history' : 'overview';
    }

    #[Computed]
    public function rows()
    {
        $since = now()->subDays(30);

        // Konsumsi bersih 30 hari = terjual - batal (gram biji mentah).
        $net = StockMovement::whereIn('type', ['sale', 'cancel'])->where('created_at', '>=', $since)
            ->selectRaw('coffee_id, sum(grams) as g')->groupBy('coffee_id')->pluck('g', 'coffee_id');

        return Coffee::orderBy('name')->get()->map(function (Coffee $c) use ($net) {
            $used = max(0, -(int) ($net[$c->id] ?? 0));
            $perDay = $used / 30;

            return (object) [
                'coffee' => $c,
                'used' => $used,
                'perDay' => $perDay,
                'daysLeft' => $perDay > 0 ? (int) floor($c->stock_green_bean_gram / $perDay) : null,
                'status' => $c->stock_green_bean_gram <= 0 ? 'out' : ($c->stock_green_bean_gram < \App\Models\Setting::int('low_stock_gram') ? 'low' : 'ok'),
            ];
        });
    }

    #[Computed]
    public function totals(): array
    {
        $r = $this->rows;
        $sum = (int) $r->sum(fn ($x) => $x->coffee->stock_green_bean_gram);

        return [
            'kg' => $sum / 1000,
            'roasted' => round($sum * 0.85 / 1000, 1),
            'low' => $r->where('status', 'low')->count(),
            'out' => $r->where('status', 'out')->count(),
        ];
    }

    #[Computed]
    public function movements()
    {
        return StockMovement::with(['coffee:id,name', 'user:id,name', 'order:id,order_code'])
            ->when($this->coffeeFilter, fn ($q) => $q->where('coffee_id', $this->coffeeFilter))
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->latest('id')->paginate(15);
    }

    #[Computed]
    public function target(): ?Coffee
    {
        return $this->adjusting ? Coffee::find($this->adjusting) : null;
    }

    public function openAdjust(int $id, string $mode = 'restock'): void
    {
        $this->adjusting = $id;
        $this->mode = in_array($mode, ['restock', 'count', 'waste'], true) ? $mode : 'restock';
        $this->amount = '';
        $this->reason = '';
        $this->resetErrorBag();
        unset($this->target);
    }

    public function closeAdjust(): void
    {
        $this->adjusting = null;
        unset($this->target);
    }

    public function save(): void
    {
        $coffee = $this->target;
        if (! $coffee) {
            return;
        }

        $min = $this->mode === 'count' ? 0 : 1;
        $this->validate([
            'amount' => ['required', 'integer', "min:$min", 'max:1000000'],
            'reason' => [$this->mode === 'waste' ? 'required' : 'nullable', 'string', 'max:120'],
        ], [
            'amount.required' => __('Isi jumlah gram dulu.'),
            'amount.integer' => __('Jumlah harus angka bulat.'),
            'amount.min' => __('Jumlah terlalu kecil.'),
            'amount.max' => __('Terlalu besar, maksimal 1.000.000 gram.'),
            'reason.required' => __('Alasan wajib diisi untuk pengurangan stok.'),
        ]);

        $grams = (int) $this->amount;
        $reason = trim($this->reason) ?: null;

        if ($this->mode === 'waste' && $grams > $coffee->stock_green_bean_gram) {
            $this->addError('amount', __('Melebihi stok yang ada (:s g).', ['s' => number_format($coffee->stock_green_bean_gram)]));

            return;
        }

        match ($this->mode) {
            'restock' => $coffee->moveStock('restock', $grams, $reason),
            'waste' => $coffee->moveStock('waste', -$grams, $reason),
            default => $grams === $coffee->stock_green_bean_gram
                ? null
                : $coffee->moveStock('adjustment', $grams - $coffee->stock_green_bean_gram, $reason),
        };

        \App\Models\ActivityLog::record('stock.adjusted', $coffee, match ($this->mode) { 'restock' => 'Restock :n: +:g g', 'waste' => 'Stok :n dikurangi (susut): -:g g', default => 'Stok :n dikoreksi menjadi :g g' }, ['n' => $coffee->name, 'g' => number_format($grams, 0, ',', '.')]);

        $this->notice = __('Stok :n diperbarui.', ['n' => $coffee->name]);
        $this->closeAdjust();
        unset($this->rows, $this->totals, $this->movements);
    }

    public function render()
    {
        return view('livewire.admin.stock', ['coffees' => Coffee::orderBy('name')->get(['id', 'name'])]);
    }
}
