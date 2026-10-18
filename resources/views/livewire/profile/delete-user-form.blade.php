<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';
    public string $confirmText = '';

    /**
     * Hapus akun pembeli. Data pribadi dibuang; pesanan dan ulasan tetap
     * ada sebagai catatan toko tanpa nama (lihat User::deleteAccount).
     */
    public function deleteUser(Logout $logout): void
    {
        $user = Auth::user();

        if ($blocker = $user->selfDeleteBlocker()) {
            $this->addError('password', $blocker);

            return;
        }

        // Akun yang dibuat lewat Google/Facebook tidak punya password yang dikenal pemiliknya.
        if ($user->provider) {
            $this->validate(['confirmText' => ['required', 'in:HAPUS']], [
                'confirmText.required' => __('Ketik HAPUS untuk melanjutkan.'),
                'confirmText.in' => __('Ketik HAPUS (huruf besar semua) untuk melanjutkan.'),
            ]);
        } else {
            $this->validate([
                'password' => ['required', 'string', 'current_password'],
            ], [
                'password.required' => __('Masukkan password untuk memastikan ini kamu.'),
                'password.current_password' => __('Password salah.'),
            ]);
        }

        $user->deleteAccount();
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section x-data="{ confirm: {{ $errors->isNotEmpty() ? 'true' : 'false' }} }">
    @php $blocker = auth()->user()->selfDeleteBlocker(); @endphp

    <header>
        <h2 class="text-lg font-medium text-redtext">{{ __('Hapus akun') }}</h2>
        <p class="mt-1 text-sm text-kraft/70">
            {{ __('Nama, email, nomor telepon, dan alamat tersimpanmu akan dihapus dan tidak bisa dikembalikan. Riwayat pesanan tetap disimpan toko tanpa namamu untuk pembukuan.') }}
        </p>
    </header>

    @if (auth()->user()->isStaff())
        <p class="mt-5 rounded border border-kraft/25 px-4 py-3 text-[13px] text-kraft/75">{{ $blocker }}</p>
    @else
        @if ($blocker)
            <p class="mt-5 rounded border border-[#E8782A]/40 bg-[#E8782A]/10 px-4 py-3 text-[13px] text-ambertext">{{ $blocker }}</p>
        @else
            <button type="button" x-show="!confirm" @click="confirm = true"
                    class="mt-5 rounded border border-redtext/50 px-5 py-2.5 text-[13.5px] font-semibold text-redtext transition-colors hover:bg-redtext/10">
                {{ __('Hapus akunku') }}
            </button>

            <form x-show="confirm" x-cloak wire:submit="deleteUser" class="mt-5 space-y-4 rounded border border-redtext/30 p-4">
                @if (auth()->user()->provider)
                    <p class="text-[13.5px] leading-relaxed">{{ __('Yakin? Ini tidak bisa dibatalkan. Ketik HAPUS untuk melanjutkan.') }}</p>
                    <div>
                        <label for="delete_confirm" class="sr-only">{{ __('Konfirmasi') }}</label>
                        <x-text-input wire:model="confirmText" id="delete_confirm" name="confirm" type="text" class="block w-full" placeholder="HAPUS" autocomplete="off" />
                        <x-input-error :messages="$errors->get('confirmText')" class="mt-2" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                @else
                    <p class="text-[13.5px] leading-relaxed">{{ __('Yakin? Ini tidak bisa dibatalkan. Masukkan passwordmu untuk melanjutkan.') }}</p>
                    <div>
                        <label for="delete_password" class="sr-only">{{ __('Password') }}</label>
                        <x-text-input wire:model="password" id="delete_password" name="password" type="password" class="block w-full"
                                      placeholder="{{ __('Password akunmu') }}" autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                @endif
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" wire:loading.attr="disabled"
                            class="rounded bg-[#B3261E] px-5 py-2.5 text-[13.5px] font-semibold text-white transition-colors hover:bg-[#8E1E18] disabled:opacity-60">
                        {{ __('Ya, hapus akunku') }}
                    </button>
                    <button type="button" @click="confirm = false; $wire.set('password', ''); $wire.set('confirmText', '')" class="text-[13.5px] text-kraft/70 hover:text-labwhite">{{ __('Batal') }}</button>
                </div>
            </form>
        @endif
    @endif
</section>
