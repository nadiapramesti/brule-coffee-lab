<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\OAuthProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    /** Mulai login sosial dari aplikasi mobile (dibuka di Chrome Custom Tab). Tanpa sesi, jadi state disimpan di cache. */
    public function mobileStart(string $provider): RedirectResponse
    {
        $oauth = new OAuthProvider($provider);

        if (! $oauth->configured()) {
            return $this->mobileBack(['error' => __('Login :provider belum dikonfigurasi di server ini.', ['provider' => ucfirst($provider)])]);
        }

        $state = Str::random(40);
        Cache::put("oauth_mobile:{$state}", $provider, now()->addMinutes(10));

        return redirect()->away($oauth->authorizationUrl($state));
    }

    private function mobileBack(array $params): RedirectResponse
    {
        return redirect()->away('brule://oauth?'.http_build_query($params));
    }

    private function mobileCallback(Request $request, string $provider, OAuthProvider $oauth): RedirectResponse
    {
        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->mobileBack(['error' => __('Login dibatalkan.')]);
        }

        try {
            $profile = $oauth->profileFromCode($request->query('code'));
        } catch (\Throwable $e) {
            report($e);

            return $this->mobileBack(['error' => __('Gagal menghubungi :provider. Coba lagi.', ['provider' => ucfirst($provider)])]);
        }

        $user = $this->userFromProfile($provider, $profile);
        if (is_string($user)) {
            return $this->mobileBack(['error' => $user]);
        }

        // Kode sekali pakai yang ditukar app jadi token (token tidak lewat URL).
        $code = Str::random(48);
        Cache::put("oauth_code:{$code}", $user->id, now()->addMinutes(2));

        return $this->mobileBack(['code' => $code]);
    }

    /** Cari atau buat pelanggan dari profil penyedia. Mengembalikan User, atau string pesan error. */
    private function userFromProfile(string $provider, array $profile): User|string
    {
        if (! $profile['email'] || ! $profile['email_verified']) {
            return __('Akun :provider kamu tidak membagikan email yang terverifikasi.', ['provider' => ucfirst($provider)]);
        }

        $user = User::where('provider', $provider)->where('provider_id', $profile['id'])->first()
            ?? User::where('email', $profile['email'])->first();

        if ($user) {
            if ($user->role !== 'customer') {
                return __('Akun tim roastery masuk dengan email dan password.');
            }
            if ($user->blocked_at) {
                return __('Akun diblokir, hubungi admin');
            }
            if (! $user->provider) {
                $user->forceFill(['provider' => $provider, 'provider_id' => $profile['id']])->save();
            }

            return $user;
        }

        $user = User::create([
            'name' => $profile['name'],
            'email' => $profile['email'],
            'password' => Str::random(40),
            'provider' => $provider,
            'provider_id' => $profile['id'],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $oauth = new OAuthProvider($provider);

        // Login dari aplikasi mobile: state-nya ada di cache, bukan di sesi.
        if ($request->filled('state') && Cache::pull('oauth_mobile:'.$request->query('state')) === $provider) {
            return $this->mobileCallback($request, $provider, $oauth);
        }

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

        return redirect()->intended(route('dashboard'));
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('oauth_error', $message);
    }
}
