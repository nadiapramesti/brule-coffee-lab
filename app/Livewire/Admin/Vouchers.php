<?php

namespace App\Livewire\Admin;

use App\Models\Voucher;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.panel')]
#[Title('Voucher - BRÛLÉ')]
class Vouchers extends Component
{
    #[Url(as: 'tampil')] public string $show = 'all';
    #[Url(as: 'q')] public string $search = '';

    public bool $open = false;
    public ?int $editingId = null;
    public string $code = '';
    public string $type = 'percent';
    public string $value = '';
    public string $minSubtotal = '';
    public string $maxDiscount = '';
    public string $quota = '';
    public string $startsAt = '';
    public string $expiresAt = '';
    public string $note = '';
    public bool $active = true;
    public ?string $notice = null;

    #[Computed]
    public function vouchers()
    {
        $all = Voucher::when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('code', 'like', '%' . Voucher::normalize($this->search) . '%')->orWhere('note', 'like', "%{$this->search}%")))
            ->latest('id')->get();

        return $this->show === 'all' ? $all : $all->filter(fn ($v) => $this->show === 'live' ? $v->state() === 'active' : $v->state() !== 'active')->values();
    }

    #[Computed]
    public function counts(): array
    {
        $all = Voucher::all();

        return ['all' => $all->count(), 'live' => $all->filter(fn ($v) => $v->state() === 'active')->count(), 'ended' => $all->filter(fn ($v) => $v->state() !== 'active')->count()];
    }

    public function openForm(?int $id = null): void
    {
        $this->resetErrorBag();
        $v = $id ? Voucher::findOrFail($id) : null;
        $this->editingId = $v?->id;
        $this->code = $v?->code ?? '';
        $this->type = $v?->type ?? 'percent';
        $this->value = (string) ($v?->value ?? '');
        $this->minSubtotal = $v && $v->min_subtotal ? (string) $v->min_subtotal : '';
        $this->maxDiscount = (string) ($v?->max_discount ?? '');
        $this->quota = (string) ($v?->quota ?? '');
        $this->startsAt = $v?->starts_at?->format('Y-m-d') ?? '';
        $this->expiresAt = $v?->expires_at?->format('Y-m-d') ?? '';
        $this->note = $v?->note ?? '';
        $this->active = $v?->is_active ?? true;
        $this->open = true;
    }

    public function closeForm(): void
    {
        $this->open = false;
    }

    public function save(): void
    {
        $this->code = Voucher::normalize($this->code);
        $max = $this->type === 'percent' ? 100 : 10000000;

        $d = $this->validate([
            'code' => ['required', 'regex:/^[A-Z0-9_-]{3,24}$/', Rule::unique('vouchers', 'code')->ignore($this->editingId)],
            'type' => ['required', Rule::in(array_keys(Voucher::TYPES))],
            'value' => ['required', 'integer', 'min:1', "max:$max"],
            'minSubtotal' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'maxDiscount' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'startsAt' => ['nullable', 'date'],
            'expiresAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'note' => ['nullable', 'string', 'max:120'],
        ], [
            'code.required' => __('Kode voucher wajib diisi.'),
            'code.regex' => __('Kode 3-24 karakter: huruf, angka, strip, atau garis bawah.'),
            'code.unique' => __('Kode ini sudah dipakai voucher lain.'),
            'value.required' => __('Nilai potongan wajib diisi.'),
            'value.integer' => __('Nilai harus angka bulat.'),
            'value.min' => __('Nilai minimal 1.'),
            'value.max' => $this->type === 'percent' ? __('Persen maksimal 100.') : __('Nilai terlalu besar.'),
            'expiresAt.after_or_equal' => __('Tanggal berakhir tidak boleh sebelum tanggal mulai.'),
        ]);

        $existing = $this->editingId ? Voucher::findOrFail($this->editingId) : null;
        if ($existing && $d['quota'] !== '' && $d['quota'] !== null && (int) $d['quota'] < $existing->used_count) {
            $this->addError('quota', __('Kuota tidak boleh di bawah jumlah pemakaian (:n).', ['n' => $existing->used_count]));

            return;
        }

        $data = [
            'code' => $d['code'], 'type' => $d['type'], 'value' => (int) $d['value'],
            'min_subtotal' => (int) ($d['minSubtotal'] ?: 0),
            'max_discount' => $d['type'] === 'percent' && $d['maxDiscount'] !== '' ? (int) $d['maxDiscount'] : null,
            'quota' => $d['quota'] !== '' ? (int) $d['quota'] : null,
            'starts_at' => $d['startsAt'] !== '' ? \Carbon\Carbon::parse($d['startsAt'])->startOfDay() : null,
            'expires_at' => $d['expiresAt'] !== '' ? \Carbon\Carbon::parse($d['expiresAt'])->endOfDay() : null,
            'note' => $d['note'] !== '' ? $d['note'] : null,
            'is_active' => $this->active,
        ];

        $v = $existing ?: new Voucher;
        $v->fill($data)->save();
        \App\Models\ActivityLog::record($existing ? 'voucher.updated' : 'voucher.created', $v, $existing ? 'Voucher :c diperbarui' : 'Voucher :c dibuat', ['c' => $v->code]);
        $this->notice = __('Voucher :c disimpan.', ['c' => $data['code']]);
        $this->open = false;
        unset($this->vouchers, $this->counts);
    }

    public function toggle(int $id): void
    {
        $v = Voucher::findOrFail($id);
        $v->update(['is_active' => ! $v->is_active]);
        \App\Models\ActivityLog::record('voucher.toggled', $v, $v->is_active ? 'Voucher :c diaktifkan' : 'Voucher :c dimatikan', ['c' => $v->code]);
        unset($this->vouchers, $this->counts);
    }

    public function delete(int $id): void
    {
        $v = Voucher::findOrFail($id);
        if ($v->used_count > 0) {
            $this->notice = __('Voucher yang sudah dipakai tidak dihapus supaya riwayat pesanan utuh. Nonaktifkan saja.');

            return;
        }
        \App\Models\ActivityLog::record('voucher.deleted', null, 'Voucher :c dihapus', ['c' => $v->code]);
        $v->delete();
        $this->notice = __('Voucher :c dihapus.', ['c' => $v->code]);
        $this->open = false;
        unset($this->vouchers, $this->counts);
    }

    public function render()
    {
        return view('livewire.admin.vouchers');
    }
}
