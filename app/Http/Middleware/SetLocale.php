<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bahasa antarmuka dipilih lewat saklar ID/EN dan disimpan di session.
 * Default Indonesia; sengaja tidak menebak dari browser supaya hasilnya
 * selalu bisa diprediksi.
 */
class SetLocale
{
    public const SUPPORTED = ['id', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', 'id');

        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'id');

        return $next($request);
    }
}
