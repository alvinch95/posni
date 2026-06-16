<?php

namespace Database\Factories\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'chen_user_id' => User::factory(),
            'type' => 'expense',
            'name' => $this->faker->word(),
            'color' => $this->faker->hexColor(),
            'sort_order' => 0,
        ];
    }

    public function income()
    {
        return $this->state(['type' => 'income']);
    }
}
