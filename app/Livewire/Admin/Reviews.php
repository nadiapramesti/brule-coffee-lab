<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Review;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Ulasan - BRÛLÉ')]
class Reviews extends Component
{
    #[Url(as: 'tampil')] public string $show = 'all';
    public ?string $notice = null;

    #[Computed]
    public function reviews()
    {
        return Review::with(['user:id,name,email', 'coffee:id,name'])
            ->when($this->show === 'visible', fn ($q) => $q->where('is_hidden', false))
            ->when($this->show === 'hidden', fn ($q) => $q->where('is_hidden', true))
            ->latest('id')->limit(100)->get();
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'all' => Review::count(),
            'visible' => Review::where('is_hidden', false)->count(),
            'hidden' => Review::where('is_hidden', true)->count(),
        ];
    }

    public function toggle(int $id): void
    {
        $r = Review::with('coffee')->findOrFail($id);
        $r->update(['is_hidden' => ! $r->is_hidden]);
        ActivityLog::record($r->is_hidden ? 'review.hidden' : 'review.shown', $r,
            $r->is_hidden ? 'Ulasan :n disembunyikan' : 'Ulasan :n ditampilkan kembali', ['n' => $r->coffee->name]);
        unset($this->reviews, $this->counts);
    }

    public function delete(int $id): void
    {
        $r = Review::with('coffee')->findOrFail($id);
        ActivityLog::record('review.deleted', $r, 'Ulasan :n dihapus', ['n' => $r->coffee->name]);
        $r->delete();
        $this->notice = __('Ulasan dihapus.');
        unset($this->reviews, $this->counts);
    }

    public function render()
    {
        return view('livewire.admin.reviews');
    }
}
