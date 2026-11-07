<?php

beforeEach(fn () => $this->seed());

it('sends basic security headers on every page', function () {
    $this->get('/')->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy');
});

it('adds HSTS only on secure requests', function () {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
});
