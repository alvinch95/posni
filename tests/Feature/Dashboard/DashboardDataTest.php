<?php

namespace Tests\Feature\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardDataTest extends DashboardTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fetch(array $params)
    {
        return $this->actingAs($this->admin())->getJson('/dashboard/data?' . http_build_query($params));
    }

    private function custom(string $from, string $to)
    {
        return $this->fetch(['period' => 'custom', 'from' => $from, 'to' => $to]);
    }

    public function test_summary_uses_the_selected_period_and_net_profit_subtracts_operating_costs(): void
    {
        $this->sale('2026-03-05 10:00:00', 1000000, 300000);
        $this->sale('2026-03-31 23:59:59', 500000, 150000);
        $this->sale('2026-04-01 00:00:00', 9000000, 9000000); // outside the range
        $this->sale('2026-03-10 10:00:00', 7000000, 7000000, [], true); // cancelled (soft-deleted)

        $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, 'gaji');
        $this->cash('2026-03-07 07:00:00', 'CashOut', 'Manual Input', 50000, null, 'misc');
        $this->cash('2026-03-08 07:00:00', 'CashOut', 'Purchase', 400000); // stock: already in capital
        $this->cash('2026-03-09 07:00:00', 'CashOut', 'Cancel Sales', 70000);
        $this->cash('2026-03-09 07:00:00', 'CashIn', 'Manual Input', 20000);
        $this->cash('2026-03-09 07:00:00', 'CashOut', 'Manual Input', 999999, 'iklan', null, true); // deleted

        $current = $this->custom('2026-03-01', '2026-03-31')->assertOk()->json('summary.current');

        $this->assertEquals(1500000, $current['omzet']);
        $this->assertEquals(450000, $current['cuan']);
        $this->assertEquals(30.0, $current['margin']);
        $this->assertSame(2, $current['orders']);
        $this->assertEquals(750000, $current['aov']);
        $this->assertEquals(150000, $current['biaya_ops']);
        $this->assertEquals(300000, $current['laba_bersih']);
    }

    public function test_cost_breakdown_adds_up_to_operating_costs_and_folds_missing_categories_into_other(): void
    {
        $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, 'gaji');
        $this->cash('2026-03-07 07:00:00', 'CashOut', 'Manual Input', 30000, null);
        $this->cash('2026-03-07 07:00:00', 'CashOut', 'Manual Input', 20000, 'lainnya');
        $this->cash('2026-03-08 07:00:00', 'CashOut', 'Manual Input', 60000, 'iklan');

        $json = $this->custom('2026-03-01', '2026-03-31')->assertOk()->json();

        $this->assertEquals(210000, $json['costs']['total']);
        $this->assertEquals($json['summary']['current']['biaya_ops'], array_sum(array_column($json['costs']['items'], 'total')));
        $this->assertSame(['gaji', 'iklan', 'lainnya'], array_column($json['costs']['items'], 'slug'));
        $this->assertSame('Gaji & THR', $json['costs']['items'][0]['label']);
        $this->assertEquals(50000, $json['costs']['items'][2]['total']);
    }

    public function test_top_products_multiply_by_qty_and_follow_the_period(): void
    {
        $kayu = $this->hamper('Kotak Kayu');
        $mug = $this->hamper('Mug Grafir');

        $this->sale('2026-03-05 10:00:00', 0, 0, [
            ['hamper_id' => $kayu, 'selling' => 100000, 'capital' => 60000, 'qty' => 3],
            ['hamper_id' => $mug, 'selling' => 50000, 'capital' => 20000, 'qty' => 4],
        ]);
        $this->sale('2026-03-06 10:00:00', 0, 0, [['hamper_id' => $kayu, 'selling' => 100000, 'capital' => 60000, 'qty' => 2]]);
        $this->sale('2026-02-01 10:00:00', 0, 0, [['hamper_id' => $mug, 'selling' => 50000, 'capital' => 20000, 'qty' => 50]]); // outside
        $this->sale('2026-03-07 10:00:00', 0, 0, [['hamper_id' => $mug, 'selling' => 50000, 'capital' => 20000, 'qty' => 50]], true); // cancelled

        $top = $this->custom('2026-03-01', '2026-03-31')->assertOk()->json('top_products');

        $this->assertSame(['Kotak Kayu', 'Mug Grafir'], array_column($top['by_qty'], 'name'));
        $this->assertSame(5, $top['by_qty'][0]['qty']);
        $this->assertEquals(500000, $top['by_qty'][0]['omzet']);
        $this->assertEquals(200000, $top['by_qty'][0]['margin']);
        $this->assertSame(4, $top['by_qty'][1]['qty']);
        $this->assertEquals(120000, $top['by_qty'][1]['margin']);
        $this->assertSame(['Kotak Kayu', 'Mug Grafir'], array_column($top['by_margin'], 'name'));
    }

    public function test_daily_trend_has_a_bucket_for_every_day(): void
    {
        $this->sale('2026-03-02 09:00:00', 100000, 30000);
        $this->sale('2026-03-02 18:00:00', 200000, 60000);
        $this->sale('2026-03-04 09:00:00', 50000, 10000);

        $trend = $this->custom('2026-03-01', '2026-03-05')->assertOk()->json('trend');

        $this->assertSame('day', $trend['granularity']);
        $this->assertSame(['1 Mar', '2 Mar', '3 Mar', '4 Mar', '5 Mar'], $trend['labels']);
        $this->assertEquals([0, 300000, 0, 50000, 0], $trend['omzet']);
        $this->assertEquals([0, 90000, 0, 10000, 0], $trend['cuan']);
        $this->assertSame([0, 2, 0, 1, 0], $trend['orders']);
    }

    public function test_long_ranges_trend_per_month_and_inventory_takes_the_last_snapshot(): void
    {
        $this->sale('2026-01-15 09:00:00', 100000, 30000);
        $this->sale('2026-03-20 09:00:00', 200000, 60000);
        DB::table('inventory_values')->insert([
            ['record_date' => '2026-01-10 17:00:00', 'total' => 1000],
            ['record_date' => '2026-01-31 17:00:00', 'total' => 1500],
            ['record_date' => '2026-03-01 17:00:00', 'total' => 2000],
        ]);

        $json = $this->custom('2026-01-01', '2026-03-31')->assertOk()->json();

        $this->assertSame('month', $json['trend']['granularity']);
        $this->assertSame(['Jan 26', 'Feb 26', 'Mar 26'], $json['trend']['labels']);
        $this->assertEquals([100000, 0, 200000], $json['trend']['omzet']);
        $this->assertEquals([1500, null, 2000], $json['inventory']['values']);
    }

    public function test_daily_inventory_leaves_gaps_for_missing_snapshots(): void
    {
        DB::table('inventory_values')->insert([
            ['record_date' => '2026-03-01 17:00:00', 'total' => 1000],
            ['record_date' => '2026-03-03 17:00:00', 'total' => 1200],
        ]);

        $values = $this->custom('2026-03-01', '2026-03-03')->assertOk()->json('inventory.values');

        $this->assertEquals([1000, null, 1200], $values);
    }

    public function test_changes_compare_with_the_previous_period_and_are_null_when_it_was_zero(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');
        $this->sale('2026-03-10 10:00:00', 300000, 90000);
        $this->sale('2026-02-10 10:00:00', 200000, 60000);
        $this->sale('2026-02-20 10:00:00', 999999, 999999); // after 15 Feb: not in the comparison range

        $json = $this->fetch(['period' => 'this_month'])->assertOk()->json();

        $this->assertSame('2026-03-01', $json['period']['from']);
        $this->assertSame('2026-02-15', $json['period']['prev_to']);
        $this->assertEquals(50.0, $json['summary']['changes']['omzet']);
        $this->assertEquals(50.0, $json['summary']['changes']['laba_bersih']);

        $empty = $this->fetch(['period' => 'last_year'])->assertOk()->json('summary.changes');
        $this->assertNull($empty['omzet']);
    }

    public function test_period_defaults_to_this_month(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $this->fetch([])->assertOk()->assertJsonPath('period.key', 'this_month')->assertJsonPath('period.label', '1–15 Mar 2026');
    }

    public function test_invalid_custom_ranges_are_rejected(): void
    {
        $this->custom('2026-03-10', '2026-03-01')->assertStatus(422);
        $this->fetch(['period' => 'custom', 'from' => '2026-03-10'])->assertStatus(422);
        $this->fetch(['period' => 'forever'])->assertStatus(422);
    }
}
