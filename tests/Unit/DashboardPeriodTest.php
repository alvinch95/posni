<?php

namespace Tests\Unit;

use App\Support\DashboardPeriod;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DashboardPeriodTest extends TestCase
{
    private function range(string $key, string $today, ?string $from = null, ?string $to = null): array
    {
        $p = DashboardPeriod::resolve($key, $from, $to, Carbon::parse($today));

        return [$p->from->toDateString(), $p->to->toDateString(), $p->prevFrom->toDateString(), $p->prevTo->toDateString()];
    }

    public function test_presets(): void
    {
        $this->assertSame(['2026-10-23', '2026-10-23', '2026-10-22', '2026-10-22'], $this->range('today', '2026-10-23'));
        $this->assertSame(['2026-10-17', '2026-10-23', '2026-10-10', '2026-10-16'], $this->range('last_7_days', '2026-10-23'));
        $this->assertSame(['2026-10-01', '2026-10-23', '2026-09-01', '2026-09-23'], $this->range('this_month', '2026-10-23'));
        $this->assertSame(['2026-09-01', '2026-09-30', '2026-08-01', '2026-08-31'], $this->range('last_month', '2026-10-23'));
        $this->assertSame(['2026-01-01', '2026-10-23', '2025-01-01', '2025-10-23'], $this->range('this_year', '2026-10-23'));
        $this->assertSame(['2025-01-01', '2025-12-31', '2024-01-01', '2024-12-31'], $this->range('last_year', '2026-10-23'));
    }

    public function test_this_month_comparison_clamps_to_end_of_shorter_month(): void
    {
        $this->assertSame(['2026-03-01', '2026-03-31', '2026-02-01', '2026-02-28'], $this->range('this_month', '2026-03-31'));
        $this->assertSame(['2024-03-01', '2024-03-31', '2024-02-01', '2024-02-29'], $this->range('this_month', '2024-03-31'));
    }

    public function test_last_month_from_a_31st(): void
    {
        $this->assertSame(['2026-02-01', '2026-02-28', '2026-01-01', '2026-01-31'], $this->range('last_month', '2026-03-31'));
    }

    public function test_custom_compares_with_same_length_window_before(): void
    {
        $this->assertSame(['2026-03-10', '2026-03-19', '2026-02-28', '2026-03-09'], $this->range('custom', '2026-10-23', '2026-03-10', '2026-03-19'));
        $this->assertSame(['2026-03-10', '2026-03-10', '2026-03-09', '2026-03-09'], $this->range('custom', '2026-10-23', '2026-03-10', '2026-03-10'));
    }

    public function test_custom_rejects_reversed_or_malformed_dates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DashboardPeriod::resolve('custom', '2026-03-19', '2026-03-10');
    }

    public function test_custom_rejects_missing_dates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DashboardPeriod::resolve('custom', null, '2026-03-10');
    }

    public function test_custom_rejects_ranges_longer_than_ten_years(): void
    {
        $this->assertSame('month', DashboardPeriod::resolve('custom', '2016-01-01', '2025-12-31')->granularity());

        $this->expectException(InvalidArgumentException::class);
        DashboardPeriod::resolve('custom', '0001-01-01', '2026-03-10');
    }

    public function test_granularity_switches_after_62_days(): void
    {
        $this->assertSame('day', DashboardPeriod::resolve('custom', '2026-01-01', '2026-03-03')->granularity()); // 62 days
        $this->assertSame('month', DashboardPeriod::resolve('custom', '2026-01-01', '2026-03-04')->granularity()); // 63 days
        $this->assertSame('month', DashboardPeriod::resolve('this_year', null, null, Carbon::parse('2026-10-23'))->granularity());
    }

    public function test_range_labels(): void
    {
        $this->assertSame('23 Okt 2026', DashboardPeriod::rangeLabel(Carbon::parse('2026-10-23'), Carbon::parse('2026-10-23')));
        $this->assertSame('1–23 Okt 2026', DashboardPeriod::rangeLabel(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-23')));
        $this->assertSame('1 Sep – 23 Okt 2026', DashboardPeriod::rangeLabel(Carbon::parse('2026-09-01'), Carbon::parse('2026-10-23')));
        $this->assertSame('1 Des 2025 – 23 Jan 2026', DashboardPeriod::rangeLabel(Carbon::parse('2025-12-01'), Carbon::parse('2026-01-23')));
    }
}
