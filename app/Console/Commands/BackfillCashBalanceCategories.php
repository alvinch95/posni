<?php

namespace App\Console\Commands;

use App\Models\CashBalance;
use App\Support\ExpenseCategories;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCashBalanceCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashbalance:backfill-categories {--dry-run : Show the proposed mapping without saving anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill cash_balances.category for uncategorised manual cash-outs, guessed from the remark';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Rows that already have a category are never touched, so this is safe to re-run
        $rows = CashBalance::where('cash_type', 'CashOut')
            ->where('related_to', 'Manual Input')
            ->whereNull('category')
            ->get(['id', 'remark', 'amount']);

        if ($rows->isEmpty()) {
            $this->info('Nothing to backfill: every manual cash-out already has a category.');
            return 0;
        }

        $byRemark = [];
        $byCategory = [];
        $idsByCategory = [];

        foreach ($rows as $row) {
            $slug = ExpenseCategories::classify($row->remark);
            $remark = trim((string) $row->remark) === '' ? '(kosong)' : $row->remark;
            $key = $slug . '|' . $remark;

            if (!isset($byRemark[$key])) {
                $byRemark[$key] = ['remark' => $remark, 'category' => ExpenseCategories::label($slug), 'count' => 0, 'total' => 0];
            }
            $byRemark[$key]['count']++;
            $byRemark[$key]['total'] += (float) $row->amount;

            $byCategory[$slug] = ($byCategory[$slug] ?? 0) + (float) $row->amount;
            $idsByCategory[$slug][] = $row->id;
        }

        usort($byRemark, function ($a, $b) {
            return [$a['category'], $b['total']] <=> [$b['category'], $a['total']];
        });
        arsort($byCategory);

        $this->table(['Remark', 'Kategori', 'Jumlah', 'Total (Rp)'], array_map(function ($r) {
            return [$r['remark'], $r['category'], $r['count'], number_format($r['total'], 0, ',', '.')];
        }, $byRemark));

        $this->table(['Kategori', 'Baris', 'Total (Rp)'], array_map(function ($slug, $total) use ($idsByCategory) {
            return [ExpenseCategories::label($slug), count($idsByCategory[$slug]), number_format($total, 0, ',', '.')];
        }, array_keys($byCategory), $byCategory));

        if ($this->option('dry-run')) {
            $this->warn("Dry run: {$rows->count()} rows would be updated. Nothing was saved.");
            return 0;
        }

        DB::transaction(function () use ($idsByCategory) {
            foreach ($idsByCategory as $slug => $ids) {
                foreach (array_chunk($ids, 500) as $chunk) {
                    CashBalance::whereIn('id', $chunk)->whereNull('category')->update(['category' => $slug]);
                }
            }
        });

        $this->info("Updated {$rows->count()} rows.");

        return 0;
    }
}
