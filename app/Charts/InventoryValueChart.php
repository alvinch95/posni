<?php

namespace App\Charts;

use App\Models\InventoryValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use ArielMejiaDev\LarapexCharts\LarapexChart;

class InventoryValueChart
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build(): \ArielMejiaDev\LarapexCharts\LineChart
    {
        $currentDateTime = Carbon::now();
        $today = Carbon::today();
        $fivePM = $today->copy()->setTime(17, 0); // Set time to 5:00 PM
        if ($currentDateTime->lessThan($fivePM)) {
            //get yesterday data
            $currentDate = Carbon::parse(today()->subDays(7));
            $endDate = Carbon::parse(today()->subDays(1));
        }
        else
        {
            //get todays data because already generated from cron
            $currentDate = Carbon::parse(today()->subDays(6));
            $endDate = Carbon::parse(today());
        }
        $orderDateFromFormatted = $currentDate->format('j M Y');
        $orderDateToFormatted = $endDate->format('j M Y');

        $data = InventoryValue::select(
            DB::raw('date_format(record_date,"%e %b %Y") as record_date_formatted'),
            DB::raw('date(record_date) as record_date'),
            DB::raw('total')
        )
        ->whereRaw('date(record_date) >= "'.$currentDate->format('Y-m-d').'" and date(record_date) <= "'.$endDate->format('Y-m-d').'"')
        ->orderBy('record_date')
        ->get();

        // dd($data);

        $days = [];
        while($currentDate <= $endDate){
            $days[] = $currentDate->format('j M Y');
            if(!$data->contains('record_date_formatted',$currentDate->format('j M Y'))){
                $newElement = [
                    'record_date' => $currentDate->format('Y-m-d'),
                    'total' => 0,   // Initialize total to 0
                ];   
                $data->push($newElement);
            }
            $currentDate->addDays(1);
        }
        $data->sortBy('record_date');
        $dataArray = $data->toArray();
        // dd($dataArray);
        
        $total_inventory_value = array_column($dataArray, 'total');
        
        return $this->chart->lineChart()
            ->setTitle('Stock Value ')
            ->setSubtitle('')
            ->setHeight(227)
            ->addData('Stock Value', $total_inventory_value)
            ->setXAxis($days);
    }
}
