<?php

namespace Database\Factories\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\RecurringRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringRuleFactory extends Factory
{
    protected $model = RecurringRule::class;

    public function definition()
    {
        return [
            'chen_user_id' => User::factory(),
            'fin_category_id' => Category::factory(),
            'type' => 'expense',
            'amount' => $this->faker->numberBetween(50000, 1000000),
            'notes' => null,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'next_run_date' => '2026-01-01',
            'active' => true,
        ];
    }
}
