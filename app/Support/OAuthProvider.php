<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * OAuth 2.0 authorization-code flow untuk Google dan Facebook.
 *
 * Ditulis langsung di atas HTTP client Laravel, tanpa paket tambahan:
 * dua penyedia ini hanya butuh tiga panggilan (alihkan, tukar kode,
 * ambil profil), dan tanpa paket tidak ada dependensi baru untuk dirawat.
 */
class OAuthProvider
{
    public const SUPPORTED = ['google', 'facebook'];

    public function __construct(private string $name)
    {
        abort_unless(in_array($name, self::SUPPORTED, true), 404);
    }

    public function configured(): bool
    {
        return filled(config("services.{$this->name}.client_id"))
            && filled(config("services.{$this->name}.client_secret"));
    }

    /**
     * Alamat balik dibuat dari alamat yang sedang dibuka di browser, jadi
     * otomatis cocok dengan yang didaftarkan (127.0.0.1:8000 saat lokal,
     * domain asli saat deploy). GOOGLE_REDIRECT_URI hanya untuk menimpa.
     */
    private function redirectUri(): string
    {
        return config("services.{$this->name}.redirect") ?: route('social.callback', $this->name);
    }

    public function authorizationUrl(string $state): string
    {
        $c = config("services.{$this->name}");

        return match ($this->name) {
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                'client_id' => $c['client_id'], 'redirect_uri' => $this->redirectUri(),
                'response_type' => 'code', 'scope' => 'openid email profile',
                'state' => $state, 'prompt' => 'select_account',
            ]),
            'facebook' => 'https://www.facebook.com/v19.0/dialog/oauth?'.http_build_query([
                'client_id' => $c['client_id'], 'redirect_uri' => $this->redirectUri(),
                'response_type' => 'code', 'scope' => 'email,public_profile', 'state' => $state,
            ]),
        };
    }

    /**
     * Tukar kode jadi profil pengguna.
     *
     * @return array{id:string,name:string,email:?string,email_verified:bool}
     */
    public function profileFromCode(string $code): array
    {
        $c = config("services.{$this->name}");

        if ($this->name === 'google') {
            $token = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code, 'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
                'redirect_uri' => $this->redirectUri(), 'grant_type' => 'authorization_code',
            ])->throw()->json('access_token');

            $p = Http::withToken($token)->get('https://openidconnect.googleapis.com/v1/userinfo')->throw()->json();

            return [
                'id' => (string) $p['sub'],
                'name' => $p['name'] ?? Str::before($p['email'] ?? 'Pengguna', '@'),
                'email' => $p['email'] ?? null,
                'email_verified' => (bool) ($p['email_verified'] ?? false),
            ];
        }

        $token = Http::get('https://graph.facebook.com/v19.0/oauth/access_token', [
            'client_id' => $c['client_id'], 'client_secret' => $c['client_secret'],
            'redirect_uri' => $this->redirectUri(), 'code' => $code,
        ])->throw()->json('access_token');

        $p = Http::get('https://graph.facebook.com/v19.0/me', [
            'fields' => 'id,name,email', 'access_token' => $token,
        ])->throw()->json();

        return [
            'id' => (string) $p['id'],
            'name' => $p['name'] ?? 'Pengguna',
            'email' => $p['email'] ?? null,
            // Facebook hanya memberikan email yang sudah dikonfirmasi pemiliknya.
            'email_verified' => filled($p['email'] ?? null),
        ];
    }
}
