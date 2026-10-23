<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WebUpload
{
    /** The website media lives on the "public" disk, never the default (local) one. */
    private const DISK = 'public';

    /**
     * Store an uploaded file under $dir (e.g. "website/categories") and return its path on
     * the public disk. The frontend builds the URL from this path (see MediaUrl).
     */
    public static function store(UploadedFile $file, string $dir): string
    {
        return (string) Storage::disk(self::DISK)->putFile($dir, $file);
    }

    /** Remove a previously stored file. A null or blank path is a no-op. */
    public static function delete(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }
}
