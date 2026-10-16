<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register" class="space-y-5" novalidate>
        <div>
            <label for="name">{{ __('Name') }}</label>
            <input wire:model="name" id="name" class="mt-1.5" type="text" name="name" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <div>
            <label for="email">{{ __('Email') }}</label>
            <input wire:model="email" id="email" class="mt-1.5" type="email" name="email" required autocomplete="username" placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <div class="flex items-baseline justify-between"><label for="phone">{{ __('Nomor WhatsApp') }}</label><span class="text-[12px] text-kraft/45">{{ __('opsional') }}</span></div>
            <input wire:model="phone" id="phone" class="mt-1.5" type="tel" name="phone" autocomplete="tel" placeholder="0812xxxxxxxx">
            <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
        </div>

        <x-auth-password id="password" model="password" :label="__('Password')" autocomplete="new-password" :error="$errors->get('password')" />
        <p class="-mt-3 text-[12px] text-kraft/50">{{ __('Minimal 8 karakter.') }}</p>

        <x-auth-password id="password_confirmation" model="password_confirmation" :label="__('Confirm Password')" autocomplete="new-password" :error="$errors->get('password_confirmation')" />

        <button type="submit" wire:loading.attr="disabled" class="w-full disabled:opacity-60">
            <span wire:loading.remove wire:target="register">{{ __('Buat akun') }}</span>
            <span wire:loading wire:target="register">{{ __('Memproses...') }}</span>
        </button>
    </form>

    <p class="mt-6 text-center text-[13.5px] text-kraft/65">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" wire:navigate class="font-medium underline underline-offset-4">{{ __('Masuk') }}</a>
    </p>
</div>
