<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ApiImage
{
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }
        if (file_exists(public_path($path))) {
            return url($path);
        }

        return url(Storage::url($path));
    }
}