<?php

namespace App\Services\Website;

class PhoneNumber
{
    /**
     * Indonesian WhatsApp number in wa.me form (country code, no "+", no separators), or null when
     * the input is not a plausible number. "0812-3456-7890", "+62 812 3456 7890" and "81234567890"
     * all give "6281234567890".
     */
    public static function normalizeWa(?string $raw): ?string
    {
        $digits = (string) preg_replace('/\D+/', '', (string) $raw);

        if (substr($digits, 0, 1) === '0') {
            $digits = '62' . substr($digits, 1);
        } elseif (substr($digits, 0, 1) === '8') {
            $digits = '62' . $digits;
        }

        return preg_match('/^62\d{8,13}$/', $digits) === 1 ? $digits : null;
    }
}
