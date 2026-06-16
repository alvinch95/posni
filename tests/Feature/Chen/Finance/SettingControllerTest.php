<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use Tests\Chen\ChenTestCase;

class SettingControllerTest extends ChenTestCase
{
    public function test_settings_page_renders(): void
    {
        $this->actingAs(User::factory()->create(), 'chen')
            ->get($this->chenUrl('/finance/settings'))
            ->assertOk()
            ->assertSee('Pengaturan');
    }

    public function test_can_save_settings_upserting_one_row(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/settings'), [
                'currency' => 'IDR', 'monthly_spending_target' => 5000000, 'monthly_savings_target' => 2000000,
            ])->assertRedirect();

        // Save again — must update, not create a second row (unique chen_user_id).
        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/settings'), [
                'currency' => 'IDR', 'monthly_spending_target' => 6000000, 'monthly_savings_target' => 2500000,
            ])->assertRedirect();

        $this->assertDatabaseCount('fin_settings', 1);
        $this->assertDatabaseHas('fin_settings', [
            'chen_user_id' => $me->id, 'monthly_spending_target' => 6000000.00,
        ]);
    }
}
