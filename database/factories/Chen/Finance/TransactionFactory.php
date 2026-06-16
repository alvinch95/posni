<?php

namespace Database\Factories\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'chen_user_id' => User::factory(),
            'type' => 'expense',
            'fin_category_id' => Category::factory(),
            'date' => $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'amount' => $this->faker->numberBetween(10000, 500000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function income()
    {
        return $this->state(['type' => 'income']);
    }
}
