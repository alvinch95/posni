<?php

namespace App\Support;

/**
 * Fixed list of operating-cost categories for manual cash-outs (cash_balances.category).
 * Slugs are stored, labels are displayed.
 */
class ExpenseCategories
{
    const OTHER = 'lainnya';

    const LABELS = [
        'gaji' => 'Gaji & THR',
        'iklan' => 'Iklan',
        'affiliate' => 'Affiliate',
        'packaging' => 'Packaging',
        'pajak' => 'Pajak',
        'utilitas' => 'Utilitas',
        'peralatan' => 'Peralatan & Aset',
        'lainnya' => 'Lainnya',
    ];

    // Backfill rules: case-insensitive substring match on remark, first match wins (order matters)
    const KEYWORDS = [
        'gaji' => ['gaji', 'thr', 'bonus', 'lembur'],
        'affiliate' => ['aff'],
        'iklan' => ['ads', 'iklan'],
        'packaging' => ['bubble', 'lakban', 'fragile', 'kardus', 'packing', 'plastik', 'selotip'],
        'pajak' => ['pajak'],
        'utilitas' => ['listrik', 'biznet', 'internet', 'wifi', 'ipl', 'pln', 'air pam'],
        'peralatan' => ['mesin', 'printer', 'rak', 'hp', 'laptop', 'servis', 'service', 'tinta', 'gunting', 'mouse', 'kursi', 'bangku'],
    ];

    public static function slugs(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(?string $slug): string
    {
        return self::LABELS[$slug] ?? self::LABELS[self::OTHER];
    }

    /**
     * Normalise a stored value: null or unknown slugs count as "lainnya".
     */
    public static function normalize(?string $slug): string
    {
        return isset(self::LABELS[$slug]) ? $slug : self::OTHER;
    }

    public static function classify(?string $remark): string
    {
        $remark = strtolower(trim((string) $remark));

        if ($remark === '') {
            return self::OTHER;
        }

        // "air" alone means the water bill; as a substring it matches too much (e.g. "repair")
        if ($remark === 'air') {
            return 'utilitas';
        }

        foreach (self::KEYWORDS as $slug => $words) {
            foreach ($words as $word) {
                if (strpos($remark, $word) !== false) {
                    return $slug;
                }
            }
        }

        return self::OTHER;
    }
}
