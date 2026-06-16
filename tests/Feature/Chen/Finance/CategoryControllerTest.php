<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use Tests\Chen\ChenTestCase;

class CategoryControllerTest extends ChenTestCase
{
    public function test_user_sees_only_their_categories(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Category::factory()->create(['chen_user_id' => $me->id, 'name' => 'Mine']);
        Category::factory()->create(['chen_user_id' => $other->id, 'name' => 'Theirs']);

        $this->actingAs($me, 'chen')
            ->get($this->chenUrl('/finance/categories'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }

    public function test_can_create_category(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/categories'), [
                'type' => 'expense', 'name' => 'Transport', 'color' => '#ff0000',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fin_categories', [
            'chen_user_id' => $me->id, 'name' => 'Transport', 'type' => 'expense',
        ]);
    }

    public function test_can_update_own_category(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id, 'name' => 'Old']);

        $this->actingAs($me, 'chen')
            ->put($this->chenUrl('/finance/categories/' . $cat->id), [
                'type' => 'expense', 'name' => 'New', 'color' => '#00ff00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fin_categories', ['id' => $cat->id, 'name' => 'New']);
    }

    public function test_cannot_update_another_users_category(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => User::factory()->create()->id]);

        $this->actingAs($me, 'chen')
            ->put($this->chenUrl('/finance/categories/' . $cat->id), [
                'type' => 'expense', 'name' => 'Hack', 'color' => '#000000',
            ])
            ->assertNotFound();
    }

    public function test_deleting_category_in_use_soft_deletes(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id]);

        $this->actingAs($me, 'chen')
            ->delete($this->chenUrl('/finance/categories/' . $cat->id))
            ->assertRedirect();

        $this->assertSoftDeleted('fin_categories', ['id' => $cat->id]);
    }
}
