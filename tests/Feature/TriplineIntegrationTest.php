<?php

use App\Models\User;
use App\Support\Tripline;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

/**
 * Integrasi Tripline (dashboard pemantau login terpisah) lewat app/Support/Tripline.php.
 * Sengaja diuji lewat Http::fake() -- tidak ada server Tripline sungguhan yang dihubungi.
 */
function konfigurasiTripline(): void
{
    config([
        'services.tripline.base_url' => 'http://tripline.test',
        'services.tripline.ingest_token' => 'token-ingest-palsu',
    ]);
}

test('Tripline nonaktif (env kosong) -- login tetap jalan normal tanpa memanggil Http sama sekali', function () {
    config(['services.tripline.base_url' => null, 'services.tripline.ingest_token' => null]);
    Http::fake(); // kalau sampai ada request keluar, Http::fake() tanpa izin akan melempar error

    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticated();
    Http::assertNothingSent();
});

test('login gagal (password salah) melapor ke Tripline dengan success=false', function () {
    konfigurasiTripline();
    Http::fake(['tripline.test/*' => Http::response(['ok' => true], 200)]);

    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password-salah')
        ->call('login')
        ->assertHasErrors();

    $this->assertGuest();

    Http::assertSent(function ($request) use ($user) {
        return $request->url() === 'http://tripline.test/api/ingest/attempt'
            && $request->hasHeader('Authorization', 'Bearer token-ingest-palsu')
            && $request['ip'] === '127.0.0.1'
            && $request['username'] === $user->email
            && $request['success'] === false
            && $request['reason'] === 'bad_password';
    });
});

test('login sukses melapor ke Tripline dengan success=true', function () {
    konfigurasiTripline();
    Http::fake(['tripline.test/*' => Http::response(['ok' => true], 200)]);

    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticated();

    Http::assertSent(function ($request) use ($user) {
        return $request->url() === 'http://tripline.test/api/ingest/attempt'
            && $request['username'] === $user->email
            && $request['success'] === true;
    });
});

test('Tripline bilang IP diblokir -- login ditolak SEBELUM password dicek, kredensial benar pun tetap gagal', function () {
    konfigurasiTripline();
    Http::fake([
        'tripline.test/api/ingest/check*' => Http::response(['blocked' => true, 'quarantined' => false], 200),
    ]);

    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password') // kredensial yang BENAR
        ->call('login')
        ->assertHasErrors();

    $this->assertGuest();

    // Tidak ada laporan attempt yang dikirim -- ditolak di langkah check(), sebelum Auth::attempt.
    Http::assertNotSent(fn ($request) => $request->url() === 'http://tripline.test/api/ingest/attempt');
});

test('Tripline mati/tidak terjangkau -- login tetap jalan normal (gagal-terbuka)', function () {
    konfigurasiTripline();
    Http::fake(function () {
        throw new ConnectionException('tidak terjangkau');
    });

    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticated();
});

test('login sosial sukses melapor ke Tripline', function () {
    konfigurasiTripline();
    Http::fake(['tripline.test/*' => Http::response(['ok' => true], 200)]);

    $user = User::factory()->create(['provider' => 'google', 'provider_id' => 'gid-123']);

    // Panggil langsung lewat kelas Tripline, meniru apa yang dilakukan SocialAuthController
    // setelah Auth::login berhasil -- tanpa perlu menjalankan flow OAuth penuh di test ini.
    Tripline::report(request()->ip() ?? '127.0.0.1', $user->email, success: true, reason: 'oauth_google');

    Http::assertSent(fn ($request) => $request->url() === 'http://tripline.test/api/ingest/attempt'
        && $request['username'] === $user->email && $request['success'] === true);
});
