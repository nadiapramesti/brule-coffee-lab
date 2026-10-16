<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.google.client_id' => 'gid', 'services.google.client_secret' => 'gsecret',
        'services.google.redirect' => null,
        'services.facebook.client_id' => 'fid', 'services.facebook.client_secret' => 'fsecret',
        'services.facebook.redirect' => null,
    ]);
});

function fakeGoogle(array $profile = []) {
    Http::fake([
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok']),
        'openidconnect.googleapis.com/*' => Http::response($profile + ['sub' => 'g-1', 'name' => 'Budi', 'email' => 'budi@gmail.com', 'email_verified' => true]),
    ]);
}

it('redirects to google with a state token', function () {
    $r = $this->get(route('social.redirect', 'google'));
    $r->assertRedirectContains('accounts.google.com');
    expect(session('oauth_state.google'))->toHaveLength(40);
});

it('shows a friendly message when the provider is not configured', function () {
    config(['services.google.client_id' => null]);
    $this->get(route('social.redirect', 'google'))->assertRedirect(route('login'))->assertSessionHas('oauth_error');
});

it('creates a verified customer on first google login', function () {
    fakeGoogle();
    $this->withSession(['oauth_state.google' => 'abc'])
        ->get(route('social.callback', ['provider' => 'google', 'code' => 'c', 'state' => 'abc']))
        ->assertRedirect();

    $u = User::where('email', 'budi@gmail.com')->firstOrFail();
    expect($u->role)->toBe('customer')->and($u->provider)->toBe('google')->and($u->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($u);
});

it('rejects a wrong state', function () {
    fakeGoogle();
    $this->withSession(['oauth_state.google' => 'abc'])
        ->get(route('social.callback', ['provider' => 'google', 'code' => 'c', 'state' => 'evil']))
        ->assertRedirect(route('login'))->assertSessionHas('oauth_error');
    $this->assertGuest();
});

it('links an existing customer by verified email', function () {
    $existing = User::factory()->create(['email' => 'budi@gmail.com']);
    fakeGoogle();
    $this->withSession(['oauth_state.google' => 'abc'])
        ->get(route('social.callback', ['provider' => 'google', 'code' => 'c', 'state' => 'abc']));
    $this->assertAuthenticatedAs($existing);
    expect($existing->fresh()->provider)->toBe('google');
});

it('never logs staff in through a social account', function () {
    User::factory()->create(['email' => 'budi@gmail.com', 'role' => 'roaster']);
    fakeGoogle();
    $this->withSession(['oauth_state.google' => 'abc'])
        ->get(route('social.callback', ['provider' => 'google', 'code' => 'c', 'state' => 'abc']))
        ->assertRedirect(route('login'));
    $this->assertGuest();
});

it('refuses unverified google emails', function () {
    fakeGoogle(['email_verified' => false]);
    $this->withSession(['oauth_state.google' => 'abc'])
        ->get(route('social.callback', ['provider' => 'google', 'code' => 'c', 'state' => 'abc']))
        ->assertRedirect(route('login'));
    $this->assertGuest();
});

it('logs in through facebook', function () {
    Http::fake([
        'graph.facebook.com/v19.0/oauth/access_token*' => Http::response(['access_token' => 't']),
        'graph.facebook.com/v19.0/me*' => Http::response(['id' => 'f-9', 'name' => 'Sinta', 'email' => 'sinta.fb@example.com']),
    ]);
    $this->withSession(['oauth_state.facebook' => 'zzz'])
        ->get(route('social.callback', ['provider' => 'facebook', 'code' => 'c', 'state' => 'zzz']));
    expect(User::where('provider', 'facebook')->where('provider_id', 'f-9')->exists())->toBeTrue();
});

it('shows social buttons on login and register', function () {
    $this->get('/login')->assertSee('Google')->assertSee('Facebook');
    $this->get('/register')->assertSee('Google')->assertSee('Facebook');
});

it('builds the redirect uri from the current host, not APP_URL', function () {
    config(['app.url' => 'http://localhost']);
    $r = $this->get('http://127.0.0.1:8000/auth/google/redirect');
    parse_str(parse_url($r->headers->get('Location'), PHP_URL_QUERY), $q);
    expect($q['redirect_uri'])->toBe('http://127.0.0.1:8000/auth/google/callback');
});
