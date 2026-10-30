<?php

use App\Support\ErrorNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    Http::fake();
});

it('does nothing when no webhook is configured', function () {
    config(['services.error_webhook.url' => null]);

    ErrorNotifier::notify(new RuntimeException('boom'));

    Http::assertNothingSent();
});

it('posts a short summary to the webhook', function () {
    config(['services.error_webhook.url' => 'https://hooks.example.test/x']);

    ErrorNotifier::notify(new RuntimeException('kaboom'));

    Http::assertSent(fn ($r) => $r->url() === 'https://hooks.example.test/x'
        && str_contains($r['text'], 'kaboom')
        && str_contains($r['content'], 'RuntimeException'));
});

it('does not send the same error twice within the dedupe window', function () {
    config(['services.error_webhook.url' => 'https://hooks.example.test/x']);
    $make = fn () => new RuntimeException('sama');

    $e = $make();
    ErrorNotifier::notify($e);
    ErrorNotifier::notify($e);

    Http::assertSentCount(1);
});

it('never throws even when the webhook is unreachable', function () {
    config(['services.error_webhook.url' => 'https://hooks.example.test/x']);
    Http::fake(['*' => fn () => throw new Exception('network down')]);

    ErrorNotifier::notify(new RuntimeException('x'));

    expect(true)->toBeTrue();
});
