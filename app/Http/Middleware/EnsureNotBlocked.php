<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Pelanggan yang diblokir langsung dikeluarkan di request berikutnya, lewat jalur login apa pun. */
class EnsureNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBlocked()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', self::message());
        }

        return $next($request);
    }

    public static function message(): string
    {
        return __('Akunmu diblokir, jadi belum bisa masuk. Kalau merasa ini keliru, hubungi kami lewat kontak di bagian bawah halaman.');
    }
}
