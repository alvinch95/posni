<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\RecurringRule;
use App\Chen\Modules\Finance\Models\Transaction;
use Tests\Chen\ChenTestCase;

class ModelsTest extends ChenTestCase
{
    public function test_can_persist_a_transaction_with_category(): void
    {
        $category = Category::factory()->create(['name' => 'Makan']);
        $txn = Transaction::factory()->create([
            'chen_user_id' => $category->chen_user_id,
            'fin_category_id' => $category->id,
            'amount' => 25000,
        ]);

        $this->assertDatabaseHas('fin_transactions', ['id' => $txn->id, 'amount' => 25000.00]);
        $this->assertSame('Makan', $txn->category->name);
    }

    public function test_category_soft_deletes(): void
    {
        $category = Category::factory()->create();
        $category->delete();

        $this->assertSoftDeleted('fin_categories', ['id' => $category->id]);
    }

    public function test_recurring_rule_casts_dates_and_bool(): void
    {
        $rule = RecurringRule::factory()->create();

        $this->assertTrue($rule->active);
        $this->assertSame('2026-01-01', $rule->next_run_date->format('Y-m-d'));
    }
}
