<?php

namespace App\Livewire\Admin;

use App\Support\SalesReport;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Laporan - BRÛLÉ')]
class Reports extends Component
{
    #[Url(as: 'periode')] public string $preset = '30d';
    #[Url(as: 'dari')] public string $from = '';
    #[Url(as: 'sampai')] public string $to = '';

    public function mount(): void
    {
        if (! array_key_exists($this->preset, SalesReport::PRESETS)) {
            $this->preset = '30d';
        }
    }

    public function setPreset(string $preset): void
    {
        if (! array_key_exists($preset, SalesReport::PRESETS)) {
            return;
        }
        if ($preset === 'custom' && $this->from === '') {
            $r = SalesReport::forPreset($this->preset);
            $this->from = $r->from->toDateString();
            $this->to = $r->to->toDateString();
        }
        $this->preset = $preset;
        unset($this->report, $this->previous);
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
        unset($this->report, $this->previous);
    }

    public function updatedTo(): void
    {
        $this->preset = 'custom';
        unset($this->report, $this->previous);
    }

    #[Computed]
    public function report(): SalesReport
    {
        return SalesReport::forPreset($this->preset, $this->from, $this->to);
    }

    #[Computed]
    public function previous(): array
    {
        return $this->report->previous()->summary();
    }

    public function exportQuery(): array
    {
        return array_filter(['preset' => $this->preset, 'from' => $this->preset === 'custom' ? $this->report->from->toDateString() : null, 'to' => $this->preset === 'custom' ? $this->report->to->toDateString() : null]);
    }

    public function render()
    {
        return view('livewire.admin.reports');
    }
}
