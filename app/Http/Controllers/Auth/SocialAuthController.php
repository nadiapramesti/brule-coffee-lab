<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\OAuthProvider;
use App\Support\Tripline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $oauth = new OAuthProvider($provider);

        if (! $oauth->configured()) {
            return $this->fail(__('Login :provider belum dikonfigurasi di server ini.', ['provider' => ucfirst($provider)]));
        }

        $state = Str::random(40);
        $request->session()->put("oauth_state.{$provider}", $state);

        return redirect()->away($oauth->authorizationUrl($state));
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $oauth = new OAuthProvider($provider);
        $expected = $request->session()->pull("oauth_state.{$provider}");

        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->fail(__('Login dibatalkan.'));
        }

        // Tanpa pemeriksaan state, orang lain bisa menyuruh browser korban
        // masuk ke akun milik penyerang.
        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return $this->fail(__('Sesi login kedaluwarsa. Coba lagi.'));
        }

        try {
            $profile = $oauth->profileFromCode($request->query('code'));
        } catch (\Throwable $e) {
            report($e);

            return $this->fail(__('Gagal menghubungi :provider. Coba lagi.', ['provider' => ucfirst($provider)]));
        }

        if (! $profile['email'] || ! $profile['email_verified']) {
            return $this->fail(__('Akun :provider kamu tidak membagikan email yang terverifikasi.', ['provider' => ucfirst($provider)]));
        }

        $user = User::where('provider', $provider)->where('provider_id', $profile['id'])->first()
            ?? User::where('email', $profile['email'])->first();

        if ($user) {
            // Akun tim roastery tidak boleh dibuka lewat login sosial: email
            // yang sama di Google belum tentu orang yang sama dengan staf.
            if ($user->role !== 'customer') {
                return $this->fail(__('Akun tim roastery masuk dengan email dan password.'));
            }

            if (! $user->provider) {
                $user->forceFill(['provider' => $provider, 'provider_id' => $profile['id']])->save();
            }
        } else {
            $user = User::create([
                'name' => $profile['name'],
                'email' => $profile['email'],
                'password' => Str::random(40),
                'provider' => $provider,
                'provider_id' => $profile['id'],
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Sekadar tercatat di Tripline juga (bukan jalur yang rawan brute-force, jadi tidak perlu
        // Tripline::check() di sini -- OAuth sudah punya pengaman sendiri di sisi provider).
        Tripline::report($request->ip(), $user->email, success: true, reason: "oauth_{$provider}");

        return redirect()->intended(route('dashboard'));
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('oauth_error', $message);
    }
}
