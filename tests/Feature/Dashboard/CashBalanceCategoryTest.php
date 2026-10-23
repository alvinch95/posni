<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashBalanceCategoryTest extends DashboardTestCase
{
    public function test_admin_can_recategorise_a_manual_cash_out(): void
    {
        $id = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, 'lainnya', 'transfer');

        $this->actingAs($this->admin())
            ->patchJson("/dashboard/cashbalances/{$id}/category", ['category' => 'gaji'])
            ->assertOk()
            ->assertJson(['category' => 'gaji', 'label' => 'Gaji & THR']);

        $row = DB::table('cash_balances')->find($id);
        $this->assertSame('gaji', $row->category);
        $this->assertEquals(100000, $row->amount);
    }

    public function test_inline_edit_works_as_a_spoofed_form_post(): void
    {
        $id = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, 'lainnya');

        $this->actingAs($this->admin())
            ->post("/dashboard/cashbalances/{$id}/category", ['_method' => 'PATCH', 'category' => 'iklan'], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame('iklan', DB::table('cash_balances')->find($id)->category);
    }

    public function test_cash_in_still_saves_before_the_category_migration_has_run(): void
    {
        Schema::drop('cash_balances');
        Schema::create('cash_balances', function (Blueprint $table) {
            $table->id();
            $table->timestamp('transaction_date')->nullable();
            $table->string('cash_type')->nullable();
            $table->string('related_to')->nullable();
            $table->decimal('current_balance', 13, 2)->nullable();
            $table->decimal('amount', 13, 2)->nullable();
            $table->decimal('end_balance', 13, 2)->nullable();
            $table->string('remark')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->integer('created_by');
            $table->timestamps();
        });

        $this->actingAs($this->admin())->post('/dashboard/cashbalances', [
            'transaction_date' => '2026-03-06', 'cashType' => 'CashIn', 'amount' => 50000, 'remark' => 'test',
        ]);

        $this->assertSame(1, DB::table('cash_balances')->count());
    }

    public function test_unknown_categories_are_rejected(): void
    {
        $id = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, 'lainnya');

        $this->actingAs($this->admin())
            ->patchJson("/dashboard/cashbalances/{$id}/category", ['category' => 'liburan'])
            ->assertStatus(422);

        $this->assertSame('lainnya', DB::table('cash_balances')->find($id)->category);
    }

    public function test_only_manual_cash_outs_can_be_categorised(): void
    {
        $admin = $this->admin();
        $purchase = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Purchase', 100000);
        $sale = $this->cash('2026-03-06 07:00:00', 'CashIn', 'Sales', 100000);

        $this->actingAs($admin)->patchJson("/dashboard/cashbalances/{$purchase}/category", ['category' => 'gaji'])->assertStatus(422);
        $this->actingAs($admin)->patchJson("/dashboard/cashbalances/{$sale}/category", ['category' => 'gaji'])->assertStatus(422);

        $this->assertNull(DB::table('cash_balances')->find($purchase)->category);
    }

    public function test_staff_cannot_recategorise(): void
    {
        $id = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, 'lainnya');

        $this->actingAs($this->staff())
            ->patchJson("/dashboard/cashbalances/{$id}/category", ['category' => 'gaji'])
            ->assertForbidden();
    }

    public function test_cash_out_requires_a_category_and_cash_in_stores_none(): void
    {
        $admin = $this->admin();
        $base = ['transaction_date' => '2026-03-06', 'amount' => 50000, 'remark' => 'test'];

        $this->actingAs($admin)
            ->post('/dashboard/cashbalances', $base + ['cashType' => 'CashOut'])
            ->assertSessionHasErrors('category');
        $this->assertSame(0, DB::table('cash_balances')->count());

        $this->actingAs($admin)->post('/dashboard/cashbalances', $base + ['cashType' => 'CashOut', 'category' => 'packaging']);
        $this->actingAs($admin)->post('/dashboard/cashbalances', $base + ['cashType' => 'CashIn', 'category' => 'packaging']);

        $this->assertSame(['packaging', null], DB::table('cash_balances')->orderBy('id')->pluck('category')->all());
    }

    public function test_backfill_dry_run_saves_nothing_and_real_run_skips_categorised_rows(): void
    {
        $ads = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, null, 'ads 1jt');
        $blank = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, null, null);
        $manual = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, 'gaji', 'ads');
        $purchase = $this->cash('2026-03-06 07:00:00', 'CashOut', 'Purchase', 100000, null, 'PO-1');

        $this->artisan('cashbalance:backfill-categories', ['--dry-run' => true])->assertExitCode(0);
        $this->assertNull(DB::table('cash_balances')->find($ads)->category);

        $this->artisan('cashbalance:backfill-categories')->assertExitCode(0);

        $this->assertSame('iklan', DB::table('cash_balances')->find($ads)->category);
        $this->assertSame('lainnya', DB::table('cash_balances')->find($blank)->category);
        $this->assertSame('gaji', DB::table('cash_balances')->find($manual)->category);
        $this->assertNull(DB::table('cash_balances')->find($purchase)->category);

        $this->artisan('cashbalance:backfill-categories')
            ->expectsOutput('Nothing to backfill: every manual cash-out already has a category.')
            ->assertExitCode(0);
    }
}
