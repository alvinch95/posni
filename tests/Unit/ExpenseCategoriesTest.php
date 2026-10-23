<?php

namespace Tests\Unit;

use App\Support\ExpenseCategories;
use PHPUnit\Framework\TestCase;

class ExpenseCategoriesTest extends TestCase
{
    /**
     * @dataProvider remarks
     */
    public function test_classify_maps_real_remarks(?string $remark, string $expected): void
    {
        $this->assertSame($expected, ExpenseCategories::classify($remark));
    }

    public function remarks(): array
    {
        return [
            ['gaji', 'gaji'],
            ['gaji + thr', 'gaji'],
            ['gaji + bonus', 'gaji'],
            ['lembur', 'gaji'],
            ['aff maret', 'affiliate'],
            ['ads', 'iklan'],
            ['ads 1jt', 'iklan'],
            ['shopee ads', 'iklan'],
            ['iklan 500rb', 'iklan'],
            ['bubble', 'packaging'],
            ['4 loco + 3 lakban', 'packaging'],
            ['plastik + ongkir retur', 'packaging'],
            ['pajak juni 2025', 'pajak'],
            ['listrik', 'utilitas'],
            ['biznet', 'utilitas'],
            ['ipl jan-juni', 'utilitas'],
            ['air', 'utilitas'],
            ['AIR PAM', 'utilitas'],
            ['printer sublim', 'peralatan'],
            ['hp aset', 'peralatan'],
            ['rak kertas', 'peralatan'],
            ['tinta 3010', 'peralatan'],
            ['servis laptop', 'peralatan'],
            ['toko abc', 'lainnya'],
            ['100 + 200', 'lainnya'],
            ['', 'lainnya'],
            [null, 'lainnya'],
        ];
    }

    public function test_normalize_folds_unknown_and_null_into_other(): void
    {
        $this->assertSame('iklan', ExpenseCategories::normalize('iklan'));
        $this->assertSame('lainnya', ExpenseCategories::normalize(null));
        $this->assertSame('lainnya', ExpenseCategories::normalize('bogus'));
    }
}
