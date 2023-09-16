<?php

namespace App\Charts;

use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class MonthlyTransactionsChart
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build($year): \ArielMejiaDev\LarapexCharts\BarChart
    {
        $data = SalesOrder::select(
            DB::raw('MONTH(order_date) as month'),
            DB::raw('SUM(total_order) as total_order'),
            DB::raw('SUM(total_revenue) as total_revenue'),
        )
        ->whereYear('order_date', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Define an array to map month numbers to month names
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $dataArray = $data->map(function ($item) use ($monthNames) {
            $item['month'] = $monthNames[$item['month']];
            return $item;
        })->toArray();

        $months = array_column($dataArray, 'month');
        $total_order = array_column($dataArray, 'total_order');
        $total_revenue = array_column($dataArray, 'total_revenue');
        
        return $this->chart->barChart()
            ->setTitle('Monthly Transactions')
            ->setSubtitle($year)
            ->addData('Total Order', $total_order)
            ->addData('Total Revenue', $total_revenue)
            ->setXAxis($months)
            ->setGrid()
            ->setDataLabels(true);
    }
}
