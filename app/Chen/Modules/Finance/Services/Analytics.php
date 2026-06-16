<?php

namespace App\Chen\Modules\Finance\Services;

use App\Chen\Modules\Finance\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class Analytics
{
    /** @return array{income: float, expense: float, saving: float} */
    public function monthSummary(int $userId, CarbonInterface $month): array
    {
        $base = Transaction::where('chen_user_id', $userId)
            ->whereYear('date', $month->year)->whereMonth('date', $month->month);

        $income = (float) (clone $base)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $base)->where('type', 'expense')->sum('amount');

        return ['income' => $income, 'expense' => $expense, 'saving' => $income - $expense];
    }

    /** @return array<int, array{name: string, color: string, total: float}> */
    public function expenseByCategory(int $userId, CarbonInterface $month, string $type = 'expense'): array
    {
        return Transaction::query()
            ->join('fin_categories', 'fin_categories.id', '=', 'fin_transactions.fin_category_id')
            ->where('fin_transactions.chen_user_id', $userId)
            ->where('fin_transactions.type', $type)
            ->whereYear('fin_transactions.date', $month->year)
            ->whereMonth('fin_transactions.date', $month->month)
            ->groupBy('fin_categories.id', 'fin_categories.name', 'fin_categories.color')
            ->orderByDesc('total')
            ->get([
                'fin_categories.name',
                'fin_categories.color',
                DB::raw('SUM(fin_transactions.amount) as total'),
            ])
            ->map(fn ($r) => ['name' => $r->name, 'color' => $r->color, 'total' => (float) $r->total])
            ->all();
    }

    /** @return array<int, array{month: string, income: float, expense: float, saving: float}> */
    public function savingsTrend(int $userId, CarbonInterface $asOf, int $months = 6): array
    {
        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = $asOf->copy()->startOfMonth()->subMonths($i);
            $summary = $this->monthSummary($userId, $m);
            $out[] = [
                'month' => $m->format('Y-m'),
                'income' => $summary['income'],
                'expense' => $summary['expense'],
                'saving' => $summary['saving'],
            ];
        }

        return $out;
    }

    /** @return array{per_day: float, per_txn: float} */
    public function expenseAverages(int $userId, CarbonInterface $month): array
    {
        $base = Transaction::where('chen_user_id', $userId)->where('type', 'expense')
            ->whereYear('date', $month->year)->whereMonth('date', $month->month);

        $total = (float) (clone $base)->sum('amount');
        $count = (int) (clone $base)->count('id');
        $daysInMonth = (int) $month->copy()->daysInMonth;

        return [
            'per_day' => $daysInMonth ? round($total / $daysInMonth, 2) : 0.0,
            'per_txn' => $count ? round($total / $count, 2) : 0.0,
        ];
    }
}
