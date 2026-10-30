<?php

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Integrasi Tripline di jalur API mobile (app/Http/Controllers/Api/AuthController.php).
 * Sama seperti versi web, tapi di sini INGEST_TOKEN cuma hidup di server -- tidak pernah
 * dikirim ke aplikasi Flutter-nya. Sengaja diuji lewat Http::fake(), tidak ada server
 * Tripline sungguhan yang dihubungi.
 */
function konfigurasiTriplineApi(): void
{
    config([
        'services.tripline.base_url' => 'http://tripline.test',
        'services.tripline.ingest_token' => 'token-ingest-palsu',
    ]);
}

test('Tripline nonaktif -- POST /api/login tetap jalan normal tanpa memanggil Http sama sekali', function () {
    config(['services.tripline.base_url' => null, 'services.tripline.ingest_token' => null]);
    Http::fake();

    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token', 'user']);

    Http::assertNothingSent();
});

test('login API gagal (password salah) melapor ke Tripline dengan success=false', function () {
    konfigurasiTriplineApi();
    Http::fake(['tripline.test/*' => Http::response(['ok' => true], 200)]);

    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password-salah',
    ])->assertStatus(422);

    Http::assertSent(function ($request) use ($user) {
        return $request->url() === 'http://tripline.test/api/ingest/attempt'
            && $request->hasHeader('Authorization', 'Bearer token-ingest-palsu')
            && $request['username'] === $user->email
            && $request['success'] === false
            && $request['reason'] === 'bad_password';
    });
});

test('login API sukses melapor ke Tripline dengan success=true dan tetap balikin token', function () {
    konfigurasiTriplineApi();
    Http::fake(['tripline.test/*' => Http::response(['ok' => true], 200)]);

    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token', 'user']);

    Http::assertSent(fn ($request) => $request->url() === 'http://tripline.test/api/ingest/attempt'
        && $request['username'] === $user->email && $request['success'] === true);
});

test('Tripline bilang IP diblokir -- POST /api/login ditolak 429 SEBELUM password dicek', function () {
    konfigurasiTriplineApi();
    Http::fake([
        'tripline.test/api/ingest/check*' => Http::response(['blocked' => true, 'quarantined' => false], 200),
    ]);

    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password', // kredensial yang BENAR
    ])->assertStatus(429);

    // Tidak ada laporan attempt yang dikirim -- ditolak di check(), sebelum cek password.
    Http::assertNotSent(fn ($request) => $request->url() === 'http://tripline.test/api/ingest/attempt');
});

test('Tripline mati/tidak terjangkau -- POST /api/login tetap jalan normal (gagal-terbuka)', function () {
    konfigurasiTriplineApi();
    Http::fake(function () {
        throw new ConnectionException('tidak terjangkau');
    });

    $user = User::factory()->create();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token', 'user']);
});

test('tukar kode OAuth sukses melapor ke Tripline dengan reason oauth_exchange', function () {
    konfigurasiTriplineApi();
    Http::fake(['tripline.test/*' => Http::response(['ok' => true], 200)]);

    $user = User::factory()->create();
    Cache::put('oauth_code:kode-abc', $user->id, 60);

    $this->postJson('/api/auth/exchange', ['code' => 'kode-abc'])
        ->assertOk()->assertJsonStructure(['token', 'user']);

    Http::assertSent(fn ($request) => $request->url() === 'http://tripline.test/api/ingest/attempt'
        && $request['username'] === $user->email
        && $request['success'] === true
        && $request['reason'] === 'oauth_exchange');
});
