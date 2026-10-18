<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di belakang tunnel/reverse proxy (Cloudflare Tunnel, ngrok, hosting) skema asli ada di header
        // X-Forwarded-*; tanpa ini URL aset dan Livewire terbentuk http:// dan diblokir browser (mixed content).
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [\App\Http\Middleware\SetLocale::class, \App\Http\Middleware\EnsureNotBlocked::class]);
        $middleware->validateCsrfTokens(except: ['midtrans/notification']);
        $middleware->alias([
            'roaster' => \App\Http\Middleware\EnsureRoaster::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
