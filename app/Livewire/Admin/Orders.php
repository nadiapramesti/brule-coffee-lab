<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
#[Title('Pesanan - KROMATIK')]
class Orders extends Component
{
    use WithPagination;

    #[Url] public string $tab = '';
    #[Url(as: 'q')] public string $search = '';
    #[Url] public ?string $from = null;
    #[Url] public ?string $to = null;

    /** Kode pesanan yang sedang dibuka di panel samping. */
    #[Url(as: 'order')] public ?string $open = null;

    public string $tracking = '';
    public string $cancelReason = '';
    public string $note = '';
    public bool $confirmCancel = false;
    public ?string $notice = null;

    public function updating($name): void
    {
        if (in_array($name, ['tab', 'search', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, Order::TABS) ? $tab : '';
        $this->resetPage();
    }

    public function preset(string $key): void
    {
        $today = CarbonImmutable::today();
        [$this->from, $this->to] = match ($key) {
            'today' => [$today->toDateString(), $today->toDateString()],
            '7' => [$today->subDays(6)->toDateString(), $today->toDateString()],
            '30' => [$today->subDays(29)->toDateString(), $today->toDateString()],
            default => [null, null],
        };
        $this->resetPage();
    }

    #[Computed]
    public function tabCounts(): array
    {
        $counts = Order::adminFilter('', $this->search, $this->from, $this->to)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return collect(Order::TABS)->map(fn ($t) => $t[1] === null
            ? (int) $counts->sum()
            : (int) collect($t[1])->sum(fn ($s) => $counts[$s] ?? 0))->all();
    }

    #[Computed]
    public function orders()
    {
        return Order::with(['user:id,name', 'items:id,order_id,coffee_name_snapshot,grams'])
            ->adminFilter($this->tab, $this->search, $this->from, $this->to)
            ->latest()->paginate(12);
    }

    #[Computed]
    public function sum(): int
    {
        return (int) Order::adminFilter($this->tab, $this->search, $this->from, $this->to)
            ->where('status', '!=', 'cancelled')->sum('total');
    }

    #[Computed]
    public function current(): ?Order
    {
        return $this->open
            ? Order::with(['items', 'user', 'roaster', 'logs.user'])->where('order_code', $this->open)->first()
            : null;
    }

    public function show(string $code): void
    {
        $this->open = $code;
        $this->resetDetailState();
    }

    public function close(): void
    {
        $this->open = null;
        $this->resetDetailState();
    }

    private function resetDetailState(): void
    {
        unset($this->current);
        $this->tracking = '';
        $this->cancelReason = '';
        $this->confirmCancel = false;
        $this->resetErrorBag();
        $this->note = (string) ($this->current?->admin_note ?? '');
    }

    private function refreshAfter(string $msg): void
    {
        unset($this->current, $this->orders, $this->tabCounts, $this->sum);
        $this->notice = $msg;
    }

    public function markPaid(): void
    {
        $this->current?->markPaid() && $this->refreshAfter(__('Pembayaran dikonfirmasi. Pesanan masuk antrian.'));
    }

    public function advance(): void
    {
        $this->current?->advance() && $this->refreshAfter(__('Status diperbarui.'));
    }

    public function ship(): void
    {
        $this->validate(['tracking' => ['required', 'string', 'min:6', 'max:40']], [
            'tracking.required' => __('Isi nomor resi dulu.'),
            'tracking.min' => __('Nomor resi terlalu pendek.'),
        ]);

        if ($this->current?->ship($this->tracking)) {
            $this->tracking = '';
            $this->refreshAfter(__('Pesanan dikirim.'));
        }
    }

    public function complete(): void
    {
        $this->current?->complete() && $this->refreshAfter(__('Pesanan selesai.'));
    }

    public function cancel(): void
    {
        $this->validate(['cancelReason' => ['nullable', 'string', 'max:120']]);

        if ($this->current?->cancelAndRestock(trim($this->cancelReason) ?: null)) {
            $this->confirmCancel = false;
            $this->cancelReason = '';
            $this->refreshAfter(__('Pesanan dibatalkan dan stok dikembalikan.'));
        }
    }

    public function saveNote(): void
    {
        $this->validate(['note' => ['nullable', 'string', 'max:500']]);

        $order = $this->current;
        if (! $order) {
            return;
        }

        $order->update(['admin_note' => trim($this->note) ?: null]);
        ActivityLog::record('order.note', $order, 'Catatan internal diperbarui');
        $this->refreshAfter(__('Catatan disimpan.'));
    }

    public function render()
    {
        return view('livewire.admin.orders');
    }
}
