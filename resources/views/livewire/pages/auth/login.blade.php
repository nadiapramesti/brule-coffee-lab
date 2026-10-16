<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email">{{ __('Email') }}</label>
            <input wire:model="form.email" id="email" class="mt-1.5" type="email" name="email" required autofocus autocomplete="username" placeholder="nama@email.com">
            <x-input-error :messages="$errors->get('form.email')" class="mt-1.5" />
        </div>

        <x-auth-password id="password" model="form.password" :label="__('Password')" :error="$errors->get('form.password')">
            <x-slot name="aside">
                <a href="{{ route('password.request') }}" wire:navigate class="text-[12.5px]">{{ __('Lupa password?') }}</a>
            </x-slot>
        </x-auth-password>

        <label for="remember" class="flex cursor-pointer items-center gap-2.5">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember" class="!w-auto">
            <span>{{ __('Ingat saya') }}</span>
        </label>

        <button type="submit" wire:loading.attr="disabled" class="w-full disabled:opacity-60">
            <span wire:loading.remove wire:target="login">{{ __('Masuk') }}</span>
            <span wire:loading wire:target="login">{{ __('Memeriksa...') }}</span>
        </button>
    </form>

    <p class="mt-6 text-center text-[13.5px] text-kraft/65">
        {{ __('Belum punya akun?') }}
        <a href="{{ route('register') }}" wire:navigate class="font-medium underline underline-offset-4">{{ __('Daftar') }}</a>
    </p>
</div>
