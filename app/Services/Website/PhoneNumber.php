<?php

namespace App\Services\Website;

class PhoneNumber
{
    /**
     * Indonesian WhatsApp number in wa.me form (country code, no "+", no separators), or null when
     * the input is not a plausible number. Steps:
     *   1. strip every non-digit;
     *   2. a leading "00" (international dialing prefix) is dropped;
     *   3. a leading "0" becomes "62";
     *   4. a leading "8" gets "62" in front;
     *   5. a leading "620" (country code followed by the national trunk "0") becomes "62";
     *   6. the result must match /^62[1-9]\d{7,12}$/, otherwise null.
     * "0812-3456-7890", "+62 812 3456 7890", "+62 0812-3456-7890", "0062 812 3456 7890" and
     * "81234567890" all give "6281234567890".
     */
    public static function normalizeWa(?string $raw): ?string
    {
        $digits = (string) preg_replace('/\D+/', '', (string) $raw);

        if (substr($digits, 0, 2) === '00') {
            $digits = substr($digits, 2);
        }

        if (substr($digits, 0, 1) === '0') {
            $digits = '62' . substr($digits, 1);
        } elseif (substr($digits, 0, 1) === '8') {
            $digits = '62' . $digits;
        }

        if (substr($digits, 0, 3) === '620') {
            $digits = '62' . substr($digits, 3);
        }

        return preg_match('/^62[1-9]\d{7,12}$/', $digits) === 1 ? $digits : null;
    }
}
