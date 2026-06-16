<?php

namespace App\Chen\Modules\Finance\Services;

use App\Chen\Modules\Finance\Models\RecurringRule;
use App\Chen\Modules\Finance\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class RecurringGenerator
{
    /**
     * Materialize all due transactions for active rules up to and including $asOf.
     * Idempotent: each run only creates rows for periods not yet generated,
     * tracked by the rule's next_run_date cursor.
     *
     * @return int number of transactions created
     */
    public function run(?CarbonInterface $asOf = null): int
    {
        $asOf = $asOf ? $asOf->copy()->startOfDay() : Carbon::now()->startOfDay();
        $created = 0;

        RecurringRule::where('active', true)
            ->where('next_run_date', '<=', $asOf->toDateString())
            ->each(function (RecurringRule $rule) use ($asOf, &$created) {
                $created += $this->runRule($rule, $asOf);
            });

        return $created;
    }

    private function runRule(RecurringRule $rule, CarbonInterface $asOf): int
    {
        $cursor = $rule->next_run_date->copy();
        $end = $rule->end_date ? $rule->end_date->copy() : null;
        $count = 0;

        while ($cursor->lessThanOrEqualTo($asOf)) {
            if ($end && $cursor->greaterThan($end)) {
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
