<?php

namespace App\Livewire\Forms;

use App\Http\Middleware\EnsureNotBlocked;
use App\Support\Tripline;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $ip = request()->ip();

        // Tripline bilang IP ini lagi dianggap berisiko (brute-force/spraying terdeteksi dari
        // percobaan-percobaan sebelumnya) -- tolak di sini SEBELUM password sempat dicek, supaya
        // tidak buang waktu hash dan tidak membocorkan apakah emailnya valid.
        $risk = Tripline::check($ip);
        if ($risk['blocked']) {
            throw ValidationException::withMessages([
                'form.email' => __('Terlalu banyak percobaan mencurigakan dari jaringanmu. Coba lagi beberapa menit lagi.'),
            ]);
        }

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            Tripline::report($ip, $this->email, success: false, reason: 'bad_password');

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        if (Auth::user()->isBlocked()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'form.email' => EnsureNotBlocked::message(),
            ]);
        }

        Tripline::report($ip, $this->email, success: true);
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
