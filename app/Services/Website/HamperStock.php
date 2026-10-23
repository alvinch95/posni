<?php

namespace App\Services\Website;

use App\Models\Hamper;
use Illuminate\Support\Facades\Log;

class HamperStock
{
    /**
     * Sellable units of a hamper, from the existing Hamper::getStock() (which must stay untouched).
     *
     * getStock() dereferences each detail's item, so a hamper that still lists a soft-deleted
     * item can throw. One broken hamper must not take the whole public catalog down: log it and
     * report the hamper as out of stock.
     */
    public static function of(Hamper $hamper): int
    {
        try {
            return max(0, (int) $hamper->getStock());
        } catch (\Throwable $e) {
            Log::warning('Website: could not compute stock for hamper, treating as out of stock', [
                'hamper_id' => $hamper->getKey(),
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
