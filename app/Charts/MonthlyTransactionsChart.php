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
            DB::raw('SUM(total_revenue) as total_revenue')
        )
        ->whereYear('order_date', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Define an array to map month numbers to month names
        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];
        
        //loop from month 1 to 12, if that month is not exists then insert 0 as total
        for ($month = 1; $month <= 12; $month++) {
            if(!$data->contains('month',$month)){
                $newElement = [
                    'month' => $month, // Month
                    'total_order' => 0,   // Initialize total_order to 0
                    'total_revenue' => 0 // Initialize total_revenue to 0
                ];   
                $data->push($newElement);
            }
        }

        $data = $data->sortBy('month');

        //rename month 1 - 12 with the month Names
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
