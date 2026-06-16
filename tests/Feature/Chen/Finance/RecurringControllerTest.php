<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\RecurringRule;
use Tests\Chen\ChenTestCase;

class RecurringControllerTest extends ChenTestCase
{
    public function test_can_create_rule_with_next_run_seeded_from_start(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);

        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/recurring'), [
                'type' => 'expense', 'fin_category_id' => $cat->id, 'amount' => 500000,
                'frequency' => 'monthly', 'start_date' => '2026-07-01',
            ])
            ->assertRedirect();

        $rule = RecurringRule::where('chen_user_id', $me->id)->first();
        $this->assertNotNull($rule);
        $this->assertSame('2026-07-01', $rule->next_run_date->format('Y-m-d'));
    }

    public function test_can_toggle_active(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        $rule = RecurringRule::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id, 'active' => true]);

        $this->actingAs($me, 'chen')
            ->patch($this->chenUrl('/finance/recurring/' . $rule->id . '/toggle'))
            ->assertRedirect();

        $this->assertFalse($rule->fresh()->active);
    }

    public function test_can_update_own_rule(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        $rule = RecurringRule::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id, 'amount' => 100000]);

        $this->actingAs($me, 'chen')
            ->put($this->chenUrl('/finance/recurring/' . $rule->id), [
                'type' => 'expense', 'fin_category_id' => $cat->id, 'amount' => 250000,
                'frequency' => 'monthly', 'start_date' => '2026-07-01',
            ])
            ->assertRedirect();

        $this->assertSame('250000.00', (string) $rule->fresh()->amount);
    }

    public function test_cannot_delete_another_users_rule(): void
    {
        $me = User::factory()->create();
        $rule = RecurringRule::factory()->create([
            'chen_user_id' => User::factory()->create()->id,
            'fin_category_id' => Category::factory()->create()->id,
        ]);

        $this->actingAs($me, 'chen')
            ->delete($this->chenUrl('/finance/recurring/' . $rule->id))
            ->assertNotFound();
    }
}
