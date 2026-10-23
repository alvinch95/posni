<?php

namespace App\Services\Website;

class MediaUrl
{
    /**
     * Absolute public URL for a file on the "public" disk. posni's APP_URL ends in a slash, so
     * both sides are trimmed to avoid "//storage".
     */
    public static function to(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        return rtrim((string) config('app.url'), '/') . '/storage/' . ltrim($path, '/');
    }
}
