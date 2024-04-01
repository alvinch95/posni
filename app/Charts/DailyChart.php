<?php

namespace App\Charts;

use App\Models\SalesOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class DailyChart
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build($orderDateFrom, $orderDateTo): \ArielMejiaDev\LarapexCharts\LineChart
    {
        $currentDate = Carbon::parse($orderDateFrom);
        $orderDateFromFormatted = $currentDate->format('j M Y');
        $endDate = Carbon::parse($orderDateTo);
        $orderDateToFormatted = $endDate->format('j M Y');

        $data = SalesOrder::select(
            DB::raw('date_format(order_date,"%e %b %Y") as order_date_formatted'),
            DB::raw('date(order_date) as order_date'),
            DB::raw('SUM(total_order) as total_order'),
            DB::raw('SUM(total_revenue) as total_revenue'),
            DB::raw('COUNT(*) as count_order')
        )
        ->whereRaw('date(order_date) >= "'.$currentDate->format('Y-m-d').'" and date(order_date) <= "'.$endDate->format('Y-m-d').'"')
        ->groupByRaw('date(order_date)')
        ->orderByRaw('date(order_date)')
        ->get();

        $days = [];
        while($currentDate <= $endDate){
            if(!$data->contains('order_date_formatted',$currentDate->format('j M Y'))){
                $newElement = [
                    'order_date_formatted' => $currentDate->format('j M Y'),
                    'order_date' => $currentDate->format('Y-m-d'),
                    'total_order' => 0,   // Initialize total_order to 0
                    'total_revenue' => 0, // Initialize total_revenue to 0
                    'count_order' => 0 // Initialize total_revenue to 0
                ];   
                $data->push($newElement);
            }
            $days[] = $currentDate->format('j M Y');
            $currentDate->addDays(1);
        }
        $data = $data->sortBy('order_date');
        $dataArray = $data->toArray();

        $total_order = array_column($dataArray, 'total_order');
        $total_revenue = array_column($dataArray, 'total_revenue');
        $count_order = array_column($dataArray, 'count_order');
        
        return $this->chart->lineChart()
            ->setTitle('Performance '.$orderDateFromFormatted.' - '.$orderDateToFormatted)
            ->setSubtitle('Total Order vs Total Revenue')
            ->addData('Total Order', $total_order)
            ->addData('Total Revenue', $total_revenue)
            ->addData('Count Order', $count_order)
            ->setXAxis($days);
    }
}
