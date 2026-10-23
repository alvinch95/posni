<?php

namespace App\Services\Website;

class Localized
{
    /**
     * Pick the text for a locale. EN is used only when the locale is "en" and the EN text is
     * not blank (after trimming); in every other case the ID text is used, or '' when empty.
     */
    public static function pick(?string $id, ?string $en, string $locale): string
    {
        if ($locale === 'en') {
            $english = trim((string) $en);
            if ($english !== '') {
                return $english;
            }
        }

        return trim((string) $id);
    }
}
