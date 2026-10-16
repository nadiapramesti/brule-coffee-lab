<?php

namespace App\Livewire;

use App\Models\Notice;
use Livewire\Attributes\Computed;
use Livewire\Component;

/** Bel notifikasi di navbar. Polling ringan supaya angka dan daftar selalu segar. */
class Notifications extends Component
{
    #[Computed]
    public function unread(): int
    {
        return Notice::where('user_id', auth()->id())->unread()->count();
    }

    #[Computed]
    public function items()
    {
        return Notice::where('user_id', auth()->id())->latest('id')->limit(15)->get();
    }

    /** Tandai dibaca lalu buka tujuannya. */
    public function open(int $id)
    {
        $n = Notice::where('user_id', auth()->id())->find($id);

        if (! $n) {
            return null;
        }

        $n->read_at ??= now();
        $n->save();

        return $n->url ? $this->redirect($n->url, navigate: true) : null;
    }

    public function markAllRead(): void
    {
        Notice::where('user_id', auth()->id())->unread()->update(['read_at' => now()]);
        unset($this->unread, $this->items);
    }

    public function render()
    {
        return view('livewire.notifications');
    }
}
