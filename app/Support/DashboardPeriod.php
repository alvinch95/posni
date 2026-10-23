<?php

namespace App\Support;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Resolves a dashboard period preset (or custom range) into the current date range,
 * the comparison range, and the trend granularity. All dates are whole days, inclusive.
 */
class DashboardPeriod
{
    const PRESETS = ['today', 'last_7_days', 'this_month', 'last_month', 'this_year', 'last_year', 'custom'];
    const PRESET_LABELS = [
        'today' => 'Hari ini',
        'last_7_days' => '7 hari terakhir',
        'this_month' => 'Bulan ini',
        'last_month' => 'Bulan lalu',
        'this_year' => 'Tahun ini',
        'last_year' => 'Tahun lalu',
        'custom' => 'Pilih tanggal…',
    ];
    const DEFAULT_PRESET = 'this_month';

    // Ranges up to this many days are charted per day, longer ones per month
    const DAILY_MAX_DAYS = 62;

    // Custom ranges are capped at about 10 years
    const CUSTOM_MAX_DAYS = 3660;

    const MONTHS = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public $key;
    public $from;
    public $to;
    public $prevFrom;
    public $prevTo;

    private function __construct(string $key, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo)
    {
        $this->key = $key;
        $this->from = $from;
        $this->to = $to;
        $this->prevFrom = $prevFrom;
        $this->prevTo = $prevTo;
    }

    public static function resolve(string $key, ?string $from = null, ?string $to = null, ?Carbon $today = null): self
    {
        $today = $today ? $today->copy()->startOfDay() : Carbon::today();

        switch ($key) {
            case 'today':
                $yesterday = $today->copy()->subDay();
                return new self($key, $today, $today->copy(), $yesterday, $yesterday->copy());

            case 'last_7_days':
                $start = $today->copy()->subDays(6);
                return new self($key, $start, $today, $start->copy()->subDays(7), $start->copy()->subDay());

            case 'this_month':
                // Compare month-to-date with the same days of last month (31 Mar -> 28/29 Feb)
                $start = $today->copy()->startOfMonth();
                return new self($key, $start, $today, $start->copy()->subMonth(), $today->copy()->subMonthNoOverflow());

            case 'last_month':
                $start = $today->copy()->startOfMonth()->subMonth();
                $prevStart = $start->copy()->subMonth();
                return new self($key, $start, $start->copy()->endOfMonth()->startOfDay(), $prevStart, $prevStart->copy()->endOfMonth()->startOfDay());

            case 'this_year':
                $start = $today->copy()->startOfYear();
                return new self($key, $start, $today, $start->copy()->subYear(), $today->copy()->subYearNoOverflow());

            case 'last_year':
                $start = $today->copy()->startOfYear()->subYear();
                $prevStart = $start->copy()->subYear();
                return new self($key, $start, $start->copy()->endOfYear()->startOfDay(), $prevStart, $prevStart->copy()->endOfYear()->startOfDay());

            case 'custom':
                $start = self::parseDate($from);
                $end = self::parseDate($to);
                if ($start->gt($end)) {
                    throw new InvalidArgumentException('The start date must be on or before the end date.');
                }
                if ($start->diffInDays($end) + 1 > self::CUSTOM_MAX_DAYS) {
                    throw new InvalidArgumentException('Rentang tanggal maksimal 10 tahun.');
                }
                // Same-length window ending the day before the range starts
                $prevEnd = $start->copy()->subDay();
                $prevStart = $prevEnd->copy()->subDays($start->diffInDays($end));
                return new self($key, $start, $end, $prevStart, $prevEnd);
        }

        throw new InvalidArgumentException("Unknown period [{$key}].");
    }

    private static function parseDate(?string $value): Carbon
    {
        if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new InvalidArgumentException('Custom dates must use the Y-m-d format.');
        }

        return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
    }

    public function days(): int
    {
        return $this->from->diffInDays($this->to) + 1;
    }

    public function granularity(): string
    {
        return $this->days() <= self::DAILY_MAX_DAYS ? 'day' : 'month';
    }

    public static function rangeLabel(Carbon $from, Carbon $to): string
    {
        $m = self::MONTHS;

        if ($from->isSameDay($to)) {
            return $from->day . ' ' . $m[$from->month] . ' ' . $from->year;
        }
        if ($from->year === $to->year && $from->month === $to->month) {
            return $from->day . '–' . $to->day . ' ' . $m[$to->month] . ' ' . $to->year;
        }
        if ($from->year === $to->year) {
            return $from->day . ' ' . $m[$from->month] . ' – ' . $to->day . ' ' . $m[$to->month] . ' ' . $to->year;
        }

        return $from->day . ' ' . $m[$from->month] . ' ' . $from->year . ' – ' . $to->day . ' ' . $m[$to->month] . ' ' . $to->year;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'prev_from' => $this->prevFrom->toDateString(),
            'prev_to' => $this->prevTo->toDateString(),
            'label' => self::rangeLabel($this->from, $this->to),
            'prev_label' => self::rangeLabel($this->prevFrom, $this->prevTo),
            'granularity' => $this->granularity(),
        ];
    }
}
