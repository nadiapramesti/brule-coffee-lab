<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoaster
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isRoaster(), 403, __('Halaman ini khusus tim roastery.'));

        return $next($request);
    }
}
