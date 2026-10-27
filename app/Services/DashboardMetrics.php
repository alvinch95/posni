<?php

namespace App\Services;

use App\Models\CashBalance;
use App\Models\InventoryValue;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Support\DashboardPeriod;
use App\Support\ExpenseCategories;
use Carbon\Carbon;

/**
 * Dashboard numbers for a date range. Queries stay driver-agnostic (MySQL in production,
 * sqlite in tests): bucketing by month happens in PHP, not with MONTH()/DATE_FORMAT().
 */
class DashboardMetrics
{
    const TOP_PRODUCTS_LIMIT = 10;

    /**
     * @param string|null $trendGroup day|week|month|year for the trend charts only. Null, or a grouping
     *                                with too many points for the range, uses the automatic one.
     *                                Inventory always stays automatic.
     */
    public function forPeriod(DashboardPeriod $period, ?string $trendGroup = null): array
    {
        $current = $this->summary($period->from, $period->to);
        $previous = $this->summary($period->prevFrom, $period->prevTo);

        $changes = [];
        foreach (['laba_bersih', 'omzet', 'cuan', 'orders', 'aov'] as $metric) {
            $changes[$metric] = $this->percentChange($current[$metric], $previous[$metric]);
        }

        return [
            'period' => $period->toArray(),
            'summary' => [
                'current' => $current,
                'previous' => $previous,
                'changes' => $changes,
            ],
            'trend' => $this->trend($period->from, $period->to, $period->effectiveGranularity($trendGroup)) + [
                'auto_granularity' => $period->granularity(),
                'allowed_groups' => $period->allowedGroups(),
            ],
            'top_products' => [
                'by_qty' => $this->topProducts($period->from, $period->to, 'qty'),
                'by_margin' => $this->topProducts($period->from, $period->to, 'margin'),
            ],
            'costs' => $this->costBreakdown($period->from, $period->to),
            'inventory' => $this->inventory($period->from, $period->to, $period->granularity()),
        ];
    }

    public function summary(Carbon $from, Carbon $to): array
    {
        $sales = $this->inRange(SalesOrder::query(), 'order_date', $from, $to)
            ->selectRaw('COALESCE(SUM(total_order), 0) as omzet, COALESCE(SUM(total_revenue), 0) as cuan, COUNT(*) as orders')
            ->first();

        $omzet = (float) $sales->omzet;
        $cuan = (float) $sales->cuan;
        $orders = (int) $sales->orders;
        $biayaOps = (float) $this->inRange($this->operatingCosts(), 'transaction_date', $from, $to)->sum('amount');

        return [
            'omzet' => $omzet,
            'cuan' => $cuan,
            'margin' => $omzet > 0 ? round($cuan / $omzet * 100, 1) : null,
            'orders' => $orders,
            'aov' => $orders > 0 ? $omzet / $orders : 0,
            'biaya_ops' => $biayaOps,
            'laba_bersih' => $cuan - $biayaOps,
        ];
    }

    public function trend(Carbon $from, Carbon $to, string $granularity): array
    {
        $rows = $this->inRange(SalesOrder::query(), 'order_date', $from, $to)
            ->selectRaw('DATE(order_date) as day, SUM(total_order) as omzet, SUM(total_revenue) as cuan, COUNT(*) as orders')
            ->groupByRaw('DATE(order_date)')
            ->get();

        $buckets = $this->emptyBuckets($from, $to, $granularity, function () {
            return ['omzet' => 0.0, 'cuan' => 0.0, 'orders' => 0];
        });

        foreach ($rows as $row) {
            $key = $this->bucketKey(Carbon::parse($row->day), $granularity);
            if (!isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['omzet'] += (float) $row->omzet;
            $buckets[$key]['cuan'] += (float) $row->cuan;
            $buckets[$key]['orders'] += (int) $row->orders;
        }

        return [
            'granularity' => $granularity,
            'labels' => array_column($buckets, 'label'),
            'omzet' => array_column($buckets, 'omzet'),
            'cuan' => array_column($buckets, 'cuan'),
            'orders' => array_column($buckets, 'orders'),
        ];
    }

    /**
     * Margin is line-level (selling - capital) * qty, i.e. before order discount and marketplace fee.
     */
    public function topProducts(Carbon $from, Carbon $to, string $sortBy): array
    {
        $query = SalesOrderDetail::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_details.sales_order_id')
            ->join('hampers', 'hampers.id', '=', 'sales_order_details.hamper_id')
            ->whereNull('sales_orders.deleted_at')
            ->selectRaw('hampers.id as id, hampers.name as name, SUM(sales_order_details.qty) as qty, '
                . 'SUM(sales_order_details.selling_price * sales_order_details.qty) as omzet, '
                . 'SUM((sales_order_details.selling_price - sales_order_details.capital_price) * sales_order_details.qty) as margin')
            ->groupBy('hampers.id', 'hampers.name')
            ->orderByDesc($sortBy === 'margin' ? 'margin' : 'qty')
            ->orderBy('hampers.name')
            ->limit(self::TOP_PRODUCTS_LIMIT);

        return $this->inRange($query, 'sales_orders.order_date', $from, $to)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'qty' => (int) $row->qty,
                    'omzet' => (float) $row->omzet,
                    'margin' => (float) $row->margin,
                ];
            })
            ->all();
    }

    public function costBreakdown(Carbon $from, Carbon $to): array
    {
        $rows = $this->inRange($this->operatingCosts(), 'transaction_date', $from, $to)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();

        // Fold null/unknown categories into "lainnya" so the slices always add up to biaya ops
        $totals = [];
        foreach ($rows as $row) {
            $slug = ExpenseCategories::normalize($row->category);
            $totals[$slug] = ($totals[$slug] ?? 0) + (float) $row->total;
        }
        arsort($totals);

        $grandTotal = array_sum($totals);
        $items = [];
        foreach ($totals as $slug => $total) {
            $items[] = [
                'slug' => $slug,
                'label' => ExpenseCategories::label($slug),
                'total' => $total,
                'share' => $grandTotal > 0 ? round($total / $grandTotal * 100, 1) : 0,
            ];
        }

        return ['total' => $grandTotal, 'items' => $items];
    }

    /**
     * Inventory value is a daily snapshot, not a flow: days without a snapshot stay null,
     * and a month shows its last snapshot.
     */
    public function inventory(Carbon $from, Carbon $to, string $granularity): array
    {
        $snapshots = $this->inRange(InventoryValue::query(), 'record_date', $from, $to)
            ->orderBy('record_date')
            ->get(['record_date', 'total']);

        $buckets = $this->emptyBuckets($from, $to, $granularity, function () {
            return ['value' => null];
        });

        foreach ($snapshots as $snapshot) {
            $key = $this->bucketKey(Carbon::parse($snapshot->record_date), $granularity);
            if (isset($buckets[$key])) {
                $buckets[$key]['value'] = (float) $snapshot->total; // ordered ascending, so the last one wins
            }
        }

        return [
            'granularity' => $granularity,
            'labels' => array_column($buckets, 'label'),
            'values' => array_column($buckets, 'value'),
        ];
    }

    /**
     * Manual cash-outs only. Purchases are already in capital price (inside cuan) and
     * cancelled sales already drop out of the sales totals.
     */
    private function operatingCosts()
    {
        return CashBalance::query()
            ->where('cash_type', 'CashOut')
            ->where('related_to', 'Manual Input');
    }

    private function inRange($query, string $column, Carbon $from, Carbon $to)
    {
        return $query
            ->where($column, '>=', $from->copy()->startOfDay()->toDateTimeString())
            ->where($column, '<', $to->copy()->addDay()->startOfDay()->toDateTimeString());
    }

    /**
     * One blank bucket per day / ISO week (Monday start) / month / year touching the range, keyed by
     * bucketKey(). Week buckets are labelled with the part of the week that is inside the range.
     */
    private function emptyBuckets(Carbon $from, Carbon $to, string $granularity, callable $blank): array
    {
        $buckets = [];
        $cursor = $this->bucketStart($from, $granularity);

        while ($cursor->lte($to)) {
            $key = $this->bucketKey($cursor, $granularity);
            $buckets[$key] = ['label' => $this->bucketLabel($cursor, $granularity, $from, $to)] + $blank();
            $this->advance($cursor, $granularity);
        }

        return $buckets;
    }

    private function bucketStart(Carbon $date, string $granularity): Carbon
    {
        switch ($granularity) {
            case 'week':
                return $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            case 'month':
                return $date->copy()->startOfMonth();
            case 'year':
                return $date->copy()->startOfYear();
        }

        return $date->copy()->startOfDay();
    }

    private function advance(Carbon $cursor, string $granularity): void
    {
        switch ($granularity) {
            case 'week':
                $cursor->addWeek();
                break;
            case 'month':
                $cursor->addMonth();
                break;
            case 'year':
                $cursor->addYear();
                break;
            default:
                $cursor->addDay();
        }
    }

    private function bucketKey(Carbon $date, string $granularity): string
    {
        switch ($granularity) {
            case 'week':
                return $this->bucketStart($date, 'week')->format('Y-m-d');
            case 'month':
                return $date->format('Y-m');
            case 'year':
                return $date->format('Y');
        }

        return $date->format('Y-m-d');
    }

    /**
     * @param Carbon $date the bucket's first day (for a week: its Monday, possibly before $from)
     */
    private function bucketLabel(Carbon $date, string $granularity, Carbon $from, Carbon $to): string
    {
        $month = DashboardPeriod::MONTHS[$date->month];

        switch ($granularity) {
            case 'week':
                return $this->weekLabel($date, $from, $to);
            case 'month':
                return $month . ' ' . $date->format('y');
            case 'year':
                return $date->format('Y');
        }

        return $date->day . ' ' . $month;
    }

    /**
     * "7–13 Okt", "30 Sep–6 Okt". The range is clipped to what was selected, so a range starting on a
     * Wednesday labels its first week from that Wednesday. Years only show when the range covers
     * more than one calendar year.
     */
    private function weekLabel(Carbon $monday, Carbon $from, Carbon $to): string
    {
        $start = $monday->lt($from) ? $from : $monday;
        $sunday = $monday->copy()->addDays(6);
        $end = $sunday->gt($to) ? $to : $sunday;

        $m = DashboardPeriod::MONTHS;
        $showYear = $from->year !== $to->year;

        if ($start->isSameDay($end)) {
            return $start->day . ' ' . $m[$start->month] . ($showYear ? ' ' . $start->year : '');
        }

        if ($start->year !== $end->year) {
            return $start->day . ' ' . $m[$start->month] . ' ' . $start->year . '–' . $end->day . ' ' . $m[$end->month] . ' ' . $end->year;
        }

        $suffix = $showYear ? ' ' . $end->year : '';

        if ($start->month === $end->month) {
            return $start->day . '–' . $end->day . ' ' . $m[$end->month] . $suffix;
        }

        return $start->day . ' ' . $m[$start->month] . '–' . $end->day . ' ' . $m[$end->month] . $suffix;
    }

    private function percentChange(float $current, float $previous): ?float
    {
        if ($previous == 0) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }
}
