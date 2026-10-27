<?php

namespace Tests\Feature\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The "Tren Penjualan" chart can be grouped per day / week / month / year. Only the trend
 * changes: summary, top products, costs and inventory stay on the automatic granularity.
 */
class DashboardTrendGroupingTest extends DashboardTestCase
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

    private function custom(string $from, string $to, ?string $group = null)
    {
        $params = ['period' => 'custom', 'from' => $from, 'to' => $to];
        if ($group !== null) {
            $params['trend_group'] = $group;
        }

        return $this->fetch($params);
    }

    public function test_without_trend_group_the_trend_is_unchanged_and_reports_the_automatic_choice(): void
    {
        $this->sale('2026-03-02 09:00:00', 100000, 30000);
        $this->sale('2026-03-02 18:00:00', 200000, 60000);
        $this->sale('2026-03-04 09:00:00', 50000, 10000);

        $trend = $this->custom('2026-03-01', '2026-03-05')->assertOk()->json('trend');

        $this->assertSame('day', $trend['granularity']);
        $this->assertSame('day', $trend['auto_granularity']);
        $this->assertSame(['day', 'week', 'month', 'year'], $trend['allowed_groups']);
        $this->assertSame(['1 Mar', '2 Mar', '3 Mar', '4 Mar', '5 Mar'], $trend['labels']);
        $this->assertEquals([0, 300000, 0, 50000, 0], $trend['omzet']);
        $this->assertEquals([0, 90000, 0, 10000, 0], $trend['cuan']);
        $this->assertSame([0, 2, 0, 1, 0], $trend['orders']);

        // An empty value is the same as no value
        $this->assertSame($trend, $this->custom('2026-03-01', '2026-03-05', '')->assertOk()->json('trend'));
    }

    public function test_explicit_day_grouping_matches_the_automatic_daily_trend(): void
    {
        $this->sale('2026-03-02 09:00:00', 100000, 30000);

        $auto = $this->custom('2026-03-01', '2026-03-05')->assertOk()->json('trend');
        $day = $this->custom('2026-03-01', '2026-03-05', 'day')->assertOk()->json('trend');

        $this->assertSame($auto, $day);
    }

    public function test_week_grouping_sums_monday_to_sunday_buckets(): void
    {
        // 2026-10-05 is a Monday
        $this->sale('2026-10-05 09:00:00', 100000, 30000); // Mon
        $this->sale('2026-10-07 09:00:00', 200000, 60000); // Wed
        $this->sale('2026-10-07 15:00:00', 50000, 10000);  // Wed
        $this->sale('2026-10-11 23:30:00', 25000, 5000);   // Sun
        $this->sale('2026-10-12 00:00:00', 400000, 80000); // next Mon

        $trend = $this->custom('2026-10-05', '2026-10-18', 'week')->assertOk()->json('trend');

        $this->assertSame('week', $trend['granularity']);
        $this->assertSame('day', $trend['auto_granularity']);
        $this->assertSame(['5–11 Okt', '12–18 Okt'], $trend['labels']);
        $this->assertEquals([375000, 400000], $trend['omzet']);
        $this->assertEquals([105000, 80000], $trend['cuan']);
        $this->assertSame([4, 1], $trend['orders']);
    }

    public function test_week_buckets_are_clipped_to_the_selected_range(): void
    {
        // Wed 7 Oct to Tue 20 Oct: the first and last weeks are partial
        $this->sale('2026-10-06 10:00:00', 999, 999);    // Tue, before the range
        $this->sale('2026-10-07 10:00:00', 100000, 10000); // Wed, first day
        $this->sale('2026-10-11 10:00:00', 50000, 5000);   // Sun
        $this->sale('2026-10-20 10:00:00', 70000, 7000);   // Tue, last day
        $this->sale('2026-10-21 10:00:00', 999, 999);    // Wed, after the range

        $trend = $this->custom('2026-10-07', '2026-10-20', 'week')->assertOk()->json('trend');

        $this->assertSame(['7–11 Okt', '12–18 Okt', '19–20 Okt'], $trend['labels']);
        $this->assertEquals([150000, 0, 70000], $trend['omzet']);
        $this->assertSame([2, 0, 1], $trend['orders']);
    }

    public function test_week_label_names_both_months_when_a_week_crosses_a_month_boundary(): void
    {
        // Mon 28 Sep 2026 to Sun 11 Oct 2026
        $labels = $this->custom('2026-09-28', '2026-10-11', 'week')->assertOk()->json('trend.labels');
        $this->assertSame(['28 Sep–4 Okt', '5–11 Okt'], $labels);

        // Wed 30 Sep: the first week is clipped to start on the 30th
        $labels = $this->custom('2026-09-30', '2026-10-11', 'week')->assertOk()->json('trend.labels');
        $this->assertSame(['30 Sep–4 Okt', '5–11 Okt'], $labels);
    }

    public function test_a_single_day_week_bucket_shows_just_that_day(): void
    {
        // Mon 12 Oct is the last day of the range
        $labels = $this->custom('2026-10-05', '2026-10-12', 'week')->assertOk()->json('trend.labels');

        $this->assertSame(['5–11 Okt', '12 Okt'], $labels);
    }

    public function test_week_labels_carry_the_year_only_when_the_range_spans_calendar_years(): void
    {
        // Mon 22 Dec 2025 to Sun 11 Jan 2026
        $labels = $this->custom('2025-12-22', '2026-01-11', 'week')->assertOk()->json('trend.labels');

        $this->assertSame(['22–28 Des 2025', '29 Des 2025–4 Jan 2026', '5–11 Jan 2026'], $labels);
    }

    public function test_year_grouping_gives_one_bucket_per_calendar_year(): void
    {
        $this->sale('2025-11-10 10:00:00', 100000, 30000);
        $this->sale('2025-12-31 23:00:00', 50000, 10000);
        $this->sale('2026-01-01 00:00:00', 200000, 60000);
        $this->sale('2026-02-14 10:00:00', 300000, 90000);

        $trend = $this->custom('2025-11-01', '2026-02-28', 'year')->assertOk()->json('trend');

        $this->assertSame('year', $trend['granularity']);
        $this->assertSame(['2025', '2026'], $trend['labels']);
        $this->assertEquals([150000, 500000], $trend['omzet']);
        $this->assertEquals([40000, 150000], $trend['cuan']);
        $this->assertSame([2, 2], $trend['orders']);
    }

    public function test_year_grouping_on_the_year_presets_gives_a_single_bucket(): void
    {
        Carbon::setTestNow('2026-10-23 12:00:00');
        $this->sale('2025-06-01 10:00:00', 100000, 30000);
        $this->sale('2026-06-01 10:00:00', 200000, 60000);

        $last = $this->fetch(['period' => 'last_year', 'trend_group' => 'year'])->assertOk()->json('trend');
        $this->assertSame(['2025'], $last['labels']);
        $this->assertEquals([100000], $last['omzet']);

        $this_ = $this->fetch(['period' => 'this_year', 'trend_group' => 'year'])->assertOk()->json('trend');
        $this->assertSame(['2026'], $this_['labels']);
        $this->assertEquals([200000], $this_['omzet']);
    }

    public function test_month_grouping_works_on_a_short_range(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');
        $this->sale('2026-03-02 10:00:00', 100000, 30000);
        $this->sale('2026-03-14 10:00:00', 200000, 60000);

        $trend = $this->fetch(['period' => 'this_month', 'trend_group' => 'month'])->assertOk()->json('trend');

        $this->assertSame('month', $trend['granularity']);
        $this->assertSame('day', $trend['auto_granularity']);
        $this->assertSame(['Mar 26'], $trend['labels']);
        $this->assertEquals([300000], $trend['omzet']);
        $this->assertEquals([90000], $trend['cuan']);
        $this->assertSame([2], $trend['orders']);
    }

    public function test_a_grouping_with_too_many_points_falls_back_to_the_automatic_one(): void
    {
        $this->sale('2022-05-10 10:00:00', 100000, 30000);

        // 5 years = 1826 days: day would be 1826 buckets
        $json = $this->custom('2021-01-01', '2025-12-31', 'day')->assertOk()->json('trend');

        $this->assertSame('month', $json['granularity']);
        $this->assertSame('month', $json['auto_granularity']);
        $this->assertSame(['week', 'month', 'year'], $json['allowed_groups']);
        $this->assertCount(60, $json['labels']);
    }

    public function test_week_is_also_refused_on_a_ten_year_range(): void
    {
        // 3653 days = 523 weeks
        $json = $this->custom('2016-01-01', '2025-12-31', 'week')->assertOk()->json('trend');

        $this->assertSame('month', $json['granularity']);
        $this->assertSame(['month', 'year'], $json['allowed_groups']);
    }

    public function test_the_limit_is_400_buckets_inclusive(): void
    {
        // 400 days: day is still allowed, and honoured when asked for
        $ok = $this->custom('2025-01-01', '2026-02-04', 'day')->assertOk()->json('trend');
        $this->assertSame('day', $ok['granularity']);
        $this->assertSame(['day', 'week', 'month', 'year'], $ok['allowed_groups']);
        $this->assertCount(400, $ok['labels']);

        // 401 days: day is out
        $tooMany = $this->custom('2025-01-01', '2026-02-05', 'day')->assertOk()->json('trend');
        $this->assertSame('month', $tooMany['granularity']);
        $this->assertSame(['week', 'month', 'year'], $tooMany['allowed_groups']);
    }

    public function test_year_is_allowed_for_a_single_month(): void
    {
        $trend = $this->custom('2026-03-01', '2026-03-31', 'year')->assertOk()->json('trend');

        $this->assertSame('year', $trend['granularity']);
        $this->assertSame(['2026'], $trend['labels']);
    }

    public function test_an_unknown_trend_group_is_rejected(): void
    {
        $this->custom('2026-03-01', '2026-03-05', 'hour')->assertStatus(422)->assertJsonValidationErrors('trend_group');
    }

    public function test_grouping_the_trend_does_not_change_any_other_section(): void
    {
        $kayu = $this->hamper('Kotak Kayu');
        $this->sale('2026-03-05 10:00:00', 100000, 30000, [['hamper_id' => $kayu, 'selling' => 100000, 'capital' => 70000, 'qty' => 1]]);
        $this->sale('2026-03-20 10:00:00', 200000, 60000, [['hamper_id' => $kayu, 'selling' => 100000, 'capital' => 70000, 'qty' => 2]]);
        $this->cash('2026-03-06 07:00:00', 'CashOut', 'Manual Input', 100000, 'gaji');
        DB::table('inventory_values')->insert([
            ['record_date' => '2026-03-01 17:00:00', 'total' => 1000],
            ['record_date' => '2026-03-15 17:00:00', 'total' => 1500],
        ]);

        $plain = $this->custom('2026-03-01', '2026-03-31')->assertOk()->json();

        foreach (['week', 'month', 'year'] as $group) {
            $grouped = $this->custom('2026-03-01', '2026-03-31', $group)->assertOk()->json();

            $this->assertSame($group, $grouped['trend']['granularity']);
            $this->assertSame($plain['period'], $grouped['period']);
            $this->assertSame($plain['summary'], $grouped['summary']);
            $this->assertSame($plain['top_products'], $grouped['top_products']);
            $this->assertSame($plain['costs'], $grouped['costs']);
            $this->assertSame($plain['inventory'], $grouped['inventory']);
        }

        // Inventory stays automatic (daily for a month), whatever the trend uses
        $this->assertSame('day', $plain['inventory']['granularity']);
        $this->assertCount(31, $plain['inventory']['labels']);
    }
}
