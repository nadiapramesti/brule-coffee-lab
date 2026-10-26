<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bahasa respons API mengikuti header Accept-Language dari aplikasi mobile (id | en).
 * Notifikasi, pesan validasi, dan pesan galat ikut diterjemahkan lewat lang/en.json yang sama dengan web.
 */
class ApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $lang = strtolower(substr((string) $request->header('Accept-Language', 'id'), 0, 2));
        $locale = in_array($lang, SetLocale::SUPPORTED, true) ? $lang : 'id';
        app()->setLocale($locale);

        $response = $next($request);

        if ($locale === 'en' && $response instanceof JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data) && isset($data['message']) && is_string($data['message'])) {
                $data['message'] = __($data['message']);
                $response->setData($data);
            }
        }

        return $response;
    }
}
