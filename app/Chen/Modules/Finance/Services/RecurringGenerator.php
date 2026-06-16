<?php

namespace App\Chen\Modules\Finance\Services;

use App\Chen\Modules\Finance\Models\RecurringRule;
use App\Chen\Modules\Finance\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringGenerator
{
    private const MAX_PER_RULE = 1000;

    /**
     * Materialize all due transactions for active rules up to and including $asOf.
     * Idempotent: each run only creates rows for periods not yet generated,
     * tracked by the rule's next_run_date cursor.
     *
     * @return int number of transactions created
     */
    public function run(?CarbonInterface $asOf = null, ?int $userId = null): int
    {
        $asOf = $asOf ? $asOf->copy()->startOfDay() : Carbon::now()->startOfDay();
        $created = 0;

        RecurringRule::where('active', true)
            ->where('next_run_date', '<=', $asOf->toDateString())
            ->when($userId !== null, fn ($q) => $q->where('chen_user_id', $userId))
            ->each(function (RecurringRule $rule) use ($asOf, &$created) {
                $created += $this->runRule($rule, $asOf);
            });

        return $created;
    }

    private function runRule(RecurringRule $rule, CarbonInterface $asOf): int
    {
        return DB::transaction(function () use ($rule, $asOf) {
            // Re-fetch with a row lock so a concurrent run (cron + dashboard catch-up)
            // can't read the same cursor and double-create. (lockForUpdate is a no-op on sqlite.)
            $rule = RecurringRule::lockForUpdate()->find($rule->id);
            if (! $rule || ! $rule->active) {
                return 0;
            }

            $cursor = $rule->next_run_date->copy();
            $end = $rule->end_date ? $rule->end_date->copy() : null;
            $count = 0;

            while ($cursor->lessThanOrEqualTo($asOf)) {
                if ($end && $cursor->greaterThan($end)) {
                    break;
                }
                if ($count >= self::MAX_PER_RULE) {
                    Log::warning("RecurringGenerator: rule {$rule->id} hit MAX_PER_RULE cap; will continue next run.");
                    break;
                }

                Transaction::create([
                    'chen_user_id' => $rule->chen_user_id,
                    'type' => $rule->type,
                    'fin_category_id' => $rule->fin_category_id,
                    'date' => $cursor->toDateString(),
                    'amount' => $rule->amount,
                    'notes' => $rule->notes,
                    'recurring_rule_id' => $rule->id,
                ]);
                $count++;

                $cursor = $this->advance($cursor, $rule->frequency);
            }

            $rule->next_run_date = $cursor->toDateString();
            $rule->save();

            return $count;
        });
    }

    private function advance(CarbonInterface $date, string $frequency): CarbonInterface
    {
        switch ($frequency) {
            case 'weekly':
                return $date->copy()->addWeek();
            case 'yearly':
                return $date->copy()->addYear();
            case 'monthly':
            default:
                return $date->copy()->addMonthNoOverflow();
        }
    }
}
