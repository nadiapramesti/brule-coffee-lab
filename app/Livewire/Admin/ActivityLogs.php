<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
#[Title('Log aktivitas - KROMATIK')]
class ActivityLogs extends Component
{
    use WithPagination;

    /** awalan action => label */
    public const GROUPS = ['order' => 'Pesanan', 'coffee' => 'Kopi', 'stock' => 'Stok', 'voucher' => 'Voucher', 'user' => 'Pengguna', 'settings' => 'Pengaturan'];

    #[Url(as: 'jenis')] public string $group = '';
    #[Url(as: 'oleh')] public ?int $by = null;
    #[Url(as: 'dari')] public string $from = '';
    #[Url(as: 'sampai')] public string $to = '';

    public function updating($name): void
    {
        if (in_array($name, ['group', 'by', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function setGroup(string $g): void
    {
        $this->group = array_key_exists($g, self::GROUPS) ? $g : '';
        $this->resetPage();
    }

    #[Computed]
    public function logs()
    {
        return ActivityLog::with(['user:id,name', 'subject'])
            ->when($this->group !== '', fn ($q) => $q->where('action', 'like', $this->group . '.%'))
            ->when($this->by, fn ($q) => $q->where('user_id', $this->by))
            ->when($this->from !== '' && strtotime($this->from), fn ($q) => $q->where('created_at', '>=', $this->from . ' 00:00:00'))
            ->when($this->to !== '' && strtotime($this->to), fn ($q) => $q->where('created_at', '<=', $this->to . ' 23:59:59'))
            ->latest('id')->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.activity-logs', [
            'actors' => User::whereIn('id', ActivityLog::whereNotNull('user_id')->select('user_id')->distinct())->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
