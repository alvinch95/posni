<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\RecurringRule;
use App\Chen\Modules\Finance\Models\Transaction;
use App\Chen\Modules\Finance\Services\RecurringGenerator;
use Carbon\Carbon;
use Tests\Chen\ChenTestCase;

class RecurringGeneratorTest extends ChenTestCase
{
    private function rule(array $overrides = []): RecurringRule
    {
        $user = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $user->id]);

        return RecurringRule::factory()->create(array_merge([
            'chen_user_id' => $user->id,
            'fin_category_id' => $cat->id,
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'start_date' => '2026-01-01',
            'next_run_date' => '2026-01-01',
            'amount' => 100000,
        ], $overrides));
    }

    public function test_generates_due_transactions_up_to_today(): void
    {
        $rule = $this->rule();
        // From 2026-01-01 monthly through 2026-06-16 => Jan,Feb,Mar,Apr,May,Jun = 6 rows.
        app(RecurringGenerator::class)->run(Carbon::parse('2026-06-16'));

        $this->assertSame(6, Transaction::where('recurring_rule_id', $rule->id)->count());
        $this->assertSame('2026-07-01', $rule->fresh()->next_run_date->format('Y-m-d'));
    }

    public function test_is_idempotent(): void
    {
        $rule = $this->rule();
        $gen = app(RecurringGenerator::class);
        $gen->run(Carbon::parse('2026-06-16'));
        $gen->run(Carbon::parse('2026-06-16')); // second run must add nothing

        $this->assertSame(6, Transaction::where('recurring_rule_id', $rule->id)->count());
    }

    public function test_respects_end_date(): void
    {
        $rule = $this->rule(['end_date' => '2026-03-31']);
        app(RecurringGenerator::class)->run(Carbon::parse('2026-06-16'));

        // Jan, Feb, Mar only = 3 rows.
        $this->assertSame(3, Transaction::where('recurring_rule_id', $rule->id)->count());
    }

    public function test_skips_inactive_rules(): void
    {
        $rule = $this->rule(['active' => false]);
        app(RecurringGenerator::class)->run(Carbon::parse('2026-06-16'));

        $this->assertSame(0, Transaction::where('recurring_rule_id', $rule->id)->count());
    }

    public function test_command_runs_generator(): void
    {
        $rule = $this->rule(['next_run_date' => '2026-06-01', 'start_date' => '2026-06-01']);

        $this->artisan('chen:finance:run-recurring')->assertExitCode(0);

        $this->assertGreaterThanOrEqual(1, Transaction::where('recurring_rule_id', $rule->id)->count());
    }

    public function test_run_can_be_scoped_to_a_single_user(): void
    {
        $ruleA = $this->rule(['next_run_date' => '2026-06-01', 'start_date' => '2026-06-01']);
        $ruleB = $this->rule(['next_run_date' => '2026-06-01', 'start_date' => '2026-06-01']);

        // Scope to ruleA's user only.
        app(RecurringGenerator::class)->run(\Carbon\Carbon::parse('2026-06-16'), $ruleA->chen_user_id);

        $this->assertGreaterThanOrEqual(1, Transaction::where('recurring_rule_id', $ruleA->id)->count());
        $this->assertSame(0, Transaction::where('recurring_rule_id', $ruleB->id)->count());
    }

    public function test_incremental_runs_across_advancing_time_generate_each_period_once(): void
    {
        $rule = $this->rule(); // monthly from 2026-01-01

        // First run as of mid-March → Jan, Feb, Mar = 3 rows, cursor advances to Apr 1.
        app(RecurringGenerator::class)->run(\Carbon\Carbon::parse('2026-03-15'));
        $this->assertSame(3, Transaction::where('recurring_rule_id', $rule->id)->count());
        $this->assertSame('2026-04-01', $rule->fresh()->next_run_date->format('Y-m-d'));

        // Two months later → Apr, May added = 5 total, no duplicates of Jan–Mar.
        app(RecurringGenerator::class)->run(\Carbon\Carbon::parse('2026-05-10'));
        $this->assertSame(5, Transaction::where('recurring_rule_id', $rule->id)->count());
        $this->assertSame('2026-06-01', $rule->fresh()->next_run_date->format('Y-m-d'));
    }
}
