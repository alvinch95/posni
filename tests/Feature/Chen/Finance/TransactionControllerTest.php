<?php

namespace Tests\Feature\Chen\Finance;

use App\Chen\Models\User;
use App\Chen\Modules\Finance\Models\Category;
use App\Chen\Modules\Finance\Models\Transaction;
use Tests\Chen\ChenTestCase;

class TransactionControllerTest extends ChenTestCase
{
    public function test_index_lists_only_own_transactions(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $mine = Category::factory()->create(['chen_user_id' => $me->id]);
        Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $mine->id, 'notes' => 'KopiSaya']);
        $their = Category::factory()->create(['chen_user_id' => $other->id]);
        Transaction::factory()->create(['chen_user_id' => $other->id, 'fin_category_id' => $their->id, 'notes' => 'KopiOrang']);

        $this->actingAs($me, 'chen')
            ->get($this->chenUrl('/finance/transactions'))
            ->assertOk()
            ->assertSee('KopiSaya')
            ->assertDontSee('KopiOrang');
    }

    public function test_can_store_expense(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id, 'type' => 'expense']);

        $this->actingAs($me, 'chen')
            ->post($this->chenUrl('/finance/transactions'), [
                'type' => 'expense', 'fin_category_id' => $cat->id,
                'date' => '2026-06-10', 'amount' => 42000, 'notes' => 'Bensin',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fin_transactions', [
            'chen_user_id' => $me->id, 'amount' => 42000.00, 'notes' => 'Bensin', 'type' => 'expense',
        ]);
    }

    public function test_store_rejects_category_of_other_user(): void
    {
        $me = User::factory()->create();
        $foreignCat = Category::factory()->create(['chen_user_id' => User::factory()->create()->id]);

        $this->actingAs($me, 'chen')
            ->from($this->chenUrl('/finance/transactions'))
            ->post($this->chenUrl('/finance/transactions'), [
                'type' => 'expense', 'fin_category_id' => $foreignCat->id,
                'date' => '2026-06-10', 'amount' => 1000,
            ])
            ->assertSessionHasErrors('fin_category_id');
    }

    public function test_can_delete_own_transaction(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        $txn = Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id]);

        $this->actingAs($me, 'chen')
            ->delete($this->chenUrl('/finance/transactions/' . $txn->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('fin_transactions', ['id' => $txn->id]);
    }

    public function test_month_filter_limits_results(): void
    {
        $me = User::factory()->create();
        $cat = Category::factory()->create(['chen_user_id' => $me->id]);
        Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id, 'date' => '2026-05-15', 'notes' => 'Mei']);
        Transaction::factory()->create(['chen_user_id' => $me->id, 'fin_category_id' => $cat->id, 'date' => '2026-06-15', 'notes' => 'Juni']);

        $this->actingAs($me, 'chen')
            ->get($this->chenUrl('/finance/transactions?month=2026-06'))
            ->assertOk()
            ->assertSee('Juni')
            ->assertDontSee('Mei');
    }
}
