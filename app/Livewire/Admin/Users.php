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
#[Title('Pengguna - KROMATIK')]
class Users extends Component
{
    use WithPagination;

    public const ROLES = ['admin' => 'Admin', 'roaster' => 'Roaster', 'customer' => 'Customer'];

    #[Url(as: 'peran')] public string $role = '';
    #[Url(as: 'q')] public string $search = '';

    // Modal ubah role
    public ?int $editingId = null;
    public string $newRole = 'customer';

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
        $this->role = array_key_exists($role, self::ROLES) ? $role : '';
        $this->resetPage();
    }

    #[Computed]
    public function counts(): array
    {
        $c = User::selectRaw('role, count(*) as n')->groupBy('role')->pluck('n', 'role');

        return ['' => (int) $c->sum()] + collect(self::ROLES)->map(fn ($l, $r) => (int) ($c[$r] ?? 0))->all();
    }

    #[Computed]
    public function users()
    {
        $q = trim($this->search);

        return User::withCount('orders')
            ->when($this->role !== '', fn ($w) => $w->where('role', $this->role))
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
        if (! $u || ! array_key_exists($this->newRole, self::ROLES) || $u->role === $this->newRole) {
            $this->closeRole();

            return;
        }
        if ($u->id === auth()->id()) {
            $this->addError('newRole', __('Kamu tidak bisa mengubah role akunmu sendiri.'));

            return;
        }
        if ($u->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            $this->addError('newRole', __('Harus ada minimal satu admin.'));

            return;
        }

        $old = $u->role;
        $u->forceFill(['role' => $this->newRole])->save();
        ActivityLog::record('user.role_changed', $u, 'Role :n diubah dari :a ke :b', ['n' => $u->name, 'a' => self::ROLES[$old], 'b' => self::ROLES[$this->newRole]]);

        $this->notice = __('Role :n sekarang :r.', ['n' => $u->name, 'r' => self::ROLES[$this->newRole]]);
        $this->closeRole();
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
            'email.email' => __('Format email belum benar. Contoh: roaster@kromatik.id'),
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
