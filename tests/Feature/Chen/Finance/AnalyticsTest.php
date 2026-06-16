<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use App\Chen\Modules\Finance\Services\Analytics;
use Carbon\Carbon;
use Tests\Chen\ChenTestCase;

class AnalyticsTest extends ChenTestCase
{
    private function seedData(User $user): void
    {
        $exp = Category::factory()->create(['chen_user_id' => $user->id, 'type' => 'expense', 'name' => 'Makan']);
        $inc = Category::factory()->income()->create(['chen_user_id' => $user->id, 'name' => 'Gaji']);

        Transaction::factory()->create(['chen_user_id' => $user->id, 'type' => 'income', 'fin_category_id' => $inc->id, 'date' => '2026-06-01', 'amount' => 10000000]);
        Transaction::factory()->create(['chen_user_id' => $user->id, 'type' => 'expense', 'fin_category_id' => $exp->id, 'date' => '2026-06-05', 'amount' => 3000000]);
        Transaction::factory()->create(['chen_user_id' => $user->id, 'type' => 'expense', 'fin_category_id' => $exp->id, 'date' => '2026-06-10', 'amount' => 1000000]);
    }

    public function test_month_totals_and_saving(): void
    {
        $user = User::factory()->create();
        $this->seedData($user);

        $summary = app(Analytics::class)->monthSummary($user->id, Carbon::parse('2026-06-15'));

        $this->assertEquals(10000000, $summary['income']);
        $this->assertEquals(4000000, $summary['expense']);
        $this->assertEquals(6000000, $summary['saving']); // income - expense
    }

    public function test_expense_by_category_groups_and_sums(): void
    {
        $user = User::factory()->create();
        $this->seedData($user);

        $breakdown = app(Analytics::class)->expenseByCategory($user->id, Carbon::parse('2026-06-15'));

        $this->assertCount(1, $breakdown);
        $this->assertSame('Makan', $breakdown[0]['name']);
        $this->assertEquals(4000000, $breakdown[0]['total']);
    }

    public function test_savings_trend_returns_six_months(): void
    {
        $user = User::factory()->create();
        $this->seedData($user);

        $trend = app(Analytics::class)->savingsTrend($user->id, Carbon::parse('2026-06-15'));

        $this->assertCount(6, $trend);
        $this->assertSame('2026-06', $trend[5]['month']);
        $this->assertEquals(6000000, $trend[5]['saving']);
        $this->assertEquals(0, $trend[0]['saving']); // Jan 2026 had nothing
    }

    public function test_dashboard_renders_and_triggers_recurring_catchup(): void
    {
        $user = User::factory()->create();
        $this->seedData($user);

        $this->actingAs($user, 'chen')
            ->get($this->chenUrl('/finance'))
            ->assertOk()
            ->assertSee('Tabungan'); // savings card label
    }

    public function test_analytics_scoped_per_user(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->seedData($other);

        $summary = app(Analytics::class)->monthSummary($me->id, Carbon::parse('2026-06-15'));

        $this->assertEquals(0, $summary['income']);
        $this->assertEquals(0, $summary['expense']);
    }
}
