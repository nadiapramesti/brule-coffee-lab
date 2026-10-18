<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.panel')]
#[Title('Pengguna - BRÛLÉ')]
class Users extends Component
{
    use WithPagination;

    public const ROLES = ['admin' => 'Admin', 'roaster' => 'Roaster', 'customer' => 'Customer'];

    #[Url(as: 'peran')] public string $role = '';
    #[Url(as: 'q')] public string $search = '';

    // Modal ubah (staf: semua data; pembeli: hanya role)
    public ?int $editingId = null;
    public string $newRole = 'customer';
    public string $editName = '';
    public string $editEmail = '';
    public string $editPhone = '';
    public string $editPassword = '';

    // Modal blokir pelanggan
    public ?int $blockingId = null;
    public string $blockReason = '';

    // Modal hapus staf
    public ?int $deletingId = null;

    // Modal tambah staf
    public bool $creating = false;
    public string $name = '';
    public string $email = '';
    public string $staffRole = 'roaster';
    public string $password = '';

    public ?string $notice = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setRole(string $role): void
    {
        $this->role = array_key_exists($role, self::ROLES) || $role === 'blocked' ? $role : '';
        $this->resetPage();
    }

    #[Computed]
    public function counts(): array
    {
        $c = User::selectRaw('role, count(*) as n')->groupBy('role')->pluck('n', 'role');

        return ['' => (int) $c->sum()]
            + collect(self::ROLES)->map(fn ($l, $r) => (int) ($c[$r] ?? 0))->all()
            + ['blocked' => User::whereNotNull('blocked_at')->count()];
    }

    #[Computed]
    public function users()
    {
        $q = trim($this->search);

        return User::withCount('orders')
            ->when($this->role === 'blocked', fn ($w) => $w->whereNotNull('blocked_at'))
            ->when($this->role !== '' && $this->role !== 'blocked', fn ($w) => $w->where('role', $this->role))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")))
            ->orderByRaw("case role when 'admin' then 0 when 'roaster' then 1 else 2 end")->orderBy('name')->paginate(12);
    }

    #[Computed]
    public function editing(): ?User
    {
        return $this->editingId ? User::find($this->editingId) : null;
    }

    public function openRole(int $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $u->id;
        $this->newRole = $u->role;
        $this->editName = $u->name;
        $this->editEmail = $u->email;
        $this->editPhone = (string) $u->phone;
        $this->editPassword = '';
        $this->resetErrorBag();
        unset($this->editing);
    }

    public function closeRole(): void
    {
        $this->editingId = null;
        unset($this->editing);
    }

    public function saveRole(): void
    {
        $u = $this->editing;
        if (! $u) {
            return;
        }
        if (! array_key_exists($this->newRole, self::ROLES)) {
            $this->closeRole();

            return;
        }

        $roleChanged = $u->role !== $this->newRole;
        if ($roleChanged && $u->id === auth()->id()) {
            $this->addError('newRole', __('Kamu tidak bisa mengubah role akunmu sendiri.'));

            return;
        }
        if ($roleChanged && $u->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            $this->addError('newRole', __('Harus ada minimal satu admin.'));

            return;
        }

        // Data diri hanya bisa diubah admin untuk akun staf. Akun pembeli dikelola pembelinya sendiri.
        $fields = [];
        if ($u->isStaff()) {
            $d = $this->validate([
                'editName' => ['required', 'string', 'max:80'],
                'editEmail' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($u->id)],
                'editPhone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
                'editPassword' => ['nullable', 'string', 'min:8', 'max:72'],
            ], [
                'editName.required' => __('Nama wajib diisi.'),
                'editEmail.required' => __('Email wajib diisi.'),
                'editEmail.email' => __('Format email belum benar. Contoh: roaster@brule.id'),
                'editEmail.unique' => __('Email ini sudah terdaftar.'),
                'editPhone.regex' => __('Nomor telepon tidak valid. Contoh: 0812xxxxxxxx'),
                'editPassword.min' => __('Kata sandi minimal 8 karakter.'),
            ]);
            $fields = [
                'name' => trim($d['editName']),
                'email' => strtolower(trim($d['editEmail'])),
                'phone' => $d['editPhone'] !== '' ? $d['editPhone'] : null,
            ];
            if (($d['editPassword'] ?? '') !== '') {
                $fields['password'] = $d['editPassword'];
            }
        }

        $old = $u->role;
        $u->fill($fields)->forceFill(['role' => $this->newRole])->save();
        if (isset($fields['password'])) {
            $u->revokeSessions();
        }

        if ($roleChanged) {
            ActivityLog::record('user.role_changed', $u, 'Role :n diubah dari :a ke :b', ['n' => $u->name, 'a' => self::ROLES[$old], 'b' => self::ROLES[$this->newRole]]);
            $this->notice = __('Role :n sekarang :r.', ['n' => $u->name, 'r' => self::ROLES[$this->newRole]]);
        } else {
            ActivityLog::record('user.updated', $u, 'Data akun :n diperbarui', ['n' => $u->name]);
            $this->notice = __('Data :n diperbarui.', ['n' => $u->name]);
        }

        $this->closeRole();
        unset($this->users, $this->counts);
    }

    #[Computed]
    public function blocking(): ?User
    {
        return $this->blockingId ? User::find($this->blockingId) : null;
    }

    public function openBlock(int $id): void
    {
        $this->blockingId = $id;
        $this->blockReason = '';
        $this->resetErrorBag();
        unset($this->blocking);
    }

    public function closeBlock(): void
    {
        $this->blockingId = null;
        unset($this->blocking);
    }

    public function confirmBlock(): void
    {
        $u = $this->blocking;
        if (! $u || $u->isStaff() || $u->id === auth()->id()) {
            $this->addError('blockReason', __('Hanya akun pembeli yang bisa diblokir.'));

            return;
        }
        $this->validate(['blockReason' => ['nullable', 'string', 'max:300']], ['blockReason.max' => __('Alasan maksimal 300 karakter.')]);

        $u->block($this->blockReason);
        ActivityLog::record('user.blocked', $u, 'Pelanggan :n diblokir', ['n' => $u->name]);

        $this->notice = __(':n diblokir dan langsung dikeluarkan dari semua sesi.', ['n' => $u->name]);
        $this->closeBlock();
        unset($this->users, $this->counts);
    }

    public function unblock(int $id): void
    {
        $u = User::findOrFail($id);
        if ($u->isBlocked()) {
            $u->unblock();
            ActivityLog::record('user.unblocked', $u, 'Blokir pelanggan :n dibuka', ['n' => $u->name]);
            $this->notice = __('Blokir :n dibuka. Dia bisa masuk lagi.', ['n' => $u->name]);
        }
        unset($this->users, $this->counts);
    }

    #[Computed]
    public function deleting(): ?User
    {
        return $this->deletingId ? User::find($this->deletingId) : null;
    }

    public function openDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->resetErrorBag();
        unset($this->deleting);
    }

    public function closeDelete(): void
    {
        $this->deletingId = null;
        unset($this->deleting);
    }

    public function deleteStaff(): void
    {
        $u = $this->deleting;
        if (! $u || ! $u->isStaff()) {
            $this->addError('delete', __('Hanya akun staf yang bisa dihapus dari sini.'));

            return;
        }
        if ($u->id === auth()->id()) {
            $this->addError('delete', __('Kamu tidak bisa menghapus akunmu sendiri.'));

            return;
        }
        if ($u->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            $this->addError('delete', __('Harus ada minimal satu admin.'));

            return;
        }

        $name = $u->name;
        $u->deleteStaff();
        ActivityLog::record('user.deleted', null, 'Akun staf :n dihapus', ['n' => $name]);

        $this->notice = __('Akun :n dihapus.', ['n' => $name]);
        $this->closeDelete();
        unset($this->users, $this->counts);
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'password']);
        $this->staffRole = 'roaster';
        $this->resetErrorBag();
        $this->creating = true;
    }

    public function closeCreate(): void
    {
        $this->creating = false;
    }

    public function createStaff(): void
    {
        $d = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'staffRole' => ['required', Rule::in(['admin', 'roaster'])],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ], [
            'name.required' => __('Nama wajib diisi.'),
            'email.required' => __('Email wajib diisi.'),
            'email.email' => __('Format email belum benar. Contoh: roaster@brule.id'),
            'email.unique' => __('Email ini sudah terdaftar.'),
            'password.required' => __('Kata sandi sementara wajib diisi.'),
            'password.min' => __('Kata sandi minimal 8 karakter.'),
        ]);

        $u = new User(['name' => trim($d['name']), 'email' => strtolower(trim($d['email'])), 'password' => $d['password']]);
        $u->forceFill(['role' => $d['staffRole'], 'email_verified_at' => now()])->save();
        ActivityLog::record('user.created', $u, 'Akun staf :n dibuat sebagai :r', ['n' => $u->name, 'r' => self::ROLES[$d['staffRole']]]);

        $this->notice = __('Akun :n dibuat. Berikan kata sandi sementara ke orangnya dan minta diganti.', ['n' => $u->name]);
        $this->creating = false;
        unset($this->users, $this->counts);
    }

    public function render()
    {
        return view('livewire.admin.users');
    }
}
