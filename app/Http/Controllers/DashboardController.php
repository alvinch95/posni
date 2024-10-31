<?php

namespace App\Http\Controllers;

use App\Charts\DailyChart;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use App\Models\InventoryValue;
use Illuminate\Support\Carbon;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use App\Charts\InventoryValueChart;
use App\Charts\MonthlyTransactionsChart;
use App\Models\CashBalance;

class DashboardController extends Controller
{
    public function index(DailyChart $chart2, InventoryValueChart $chart3)
    {
        $year = request('year',now()->format("Y"));
        $currentDate = now();
        $currentYear = $currentDate->format("Y");
        $currentMonth = $currentDate->format("m");

        // Create a separate instance for the previous month
        $previousDate = Carbon::create($currentYear, $currentMonth, 1)->subMonth();
        $previousMonth = $previousDate->format("m");
        $previousMonthYear = $previousDate->format("Y");

        $dateFrom = request('order_date_from', today()->subDays(6));
        $dateTo = request('order_date_to', today());
        
        $topSellingProducts = SalesOrderDetail::select(
            DB::raw('hampers.name'),
            DB::raw('SUM(sales_order_details.qty)as Qty_Sold'),
            DB::raw('SUM(sales_order_details.selling_price - sales_order_details.capital_price) as Total_Revenue')
        )->join('hampers','hampers.id','=','sales_order_details.hamper_id')
        ->groupBy('hampers.id','hampers.name')
        ->orderByDesc('Qty_Sold')
        ->paginate(5);

        // Calculate the total revenue and orders for the metric cards
        $totalRevenueSum = SalesOrder::sum('total_revenue');
        $totalOrdersSum = SalesOrder::sum('total_order');
        $averageOrderValue = $totalOrdersSum ? $totalRevenueSum / $totalOrdersSum : 0;

        // Calculate credit and debit for the current month
        $totalCashOut = CashBalance::whereYear('transaction_date', $currentYear)
        ->whereMonth('transaction_date', $currentMonth)
        ->where('cash_type','=','CashOut')
        ->sum('amount'); 

        $totalCashIn = CashBalance::whereYear('transaction_date', $currentYear)
        ->whereMonth('transaction_date', $currentMonth)
        ->where('cash_type','=','CashIn')
        ->sum('amount'); 

        $totalCashOutLastMonth = CashBalance::whereYear('transaction_date', $previousMonthYear)
        ->whereMonth('transaction_date', $previousMonth)
        ->where('cash_type','=','CashOut')
        ->sum('amount');  

        $totalCashInLastMonth = CashBalance::whereYear('transaction_date', $previousMonthYear)
        ->whereMonth('transaction_date', $previousMonth)
        ->where('cash_type','=','CashIn')
        ->sum('amount');  

        return view('dashboard.index',[
            'currentYear' => $currentYear,
            'topSellingProducts' => $topSellingProducts,
            'year' => $year,
            'totalRevenueSum' => $totalRevenueSum,
            'totalOrdersSum' => $totalOrdersSum,
            'averageOrderValue' => $averageOrderValue,
            'totalCashOut' => $totalCashOut,
            'totalCashIn' => $totalCashIn,
            'totalCashOutLastMonth' => $totalCashOutLastMonth,
            'totalCashInLastMonth' => $totalCashInLastMonth,
            'months' => $this->getMonthlyTransactions($year)['months'],
            'monthlyTotalOrders' => $this->getMonthlyTransactions($year)['totalOrders'],
            'monthlyTotalRevenue' => $this->getMonthlyTransactions($year)['totalRevenue'],
            'dailyDays' => $this->getDailyTransaction($dateFrom,$dateTo)['days'],
            'dailyCountOrder' => $this->getDailyTransaction($dateFrom,$dateTo)['countOrder'],
            'dailyTotalOrders' => $this->getDailyTransaction($dateFrom,$dateTo)['totalOrders'],
            'dailyTotalRevenue' => $this->getDailyTransaction($dateFrom,$dateTo)['totalRevenue'],
            'totalInventoryValue' => $this->getInventoryValue()['totalInventoryValue'],
            'inventoryValueDays' => $this->getInventoryValue()['days']
        ]);
    }

    private function getMonthlyTransactions($year){
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

        return [
            'months' => array_column($dataArray, 'month'),
            'totalOrders' => array_column($dataArray, 'total_order'),
            'totalRevenue' => array_column($dataArray, 'total_revenue')
        ];
    }

    private function getDailyTransaction($orderDateFrom, $orderDateTo){
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

        return [
            'countOrder' => $count_order,
            'totalOrders' => $total_order,
            'totalRevenue' => $total_revenue,
            'days' => $days
        ];
    }

    private function getInventoryValue(){
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
        
        $total_inventory_value = array_column($dataArray, 'total');
        return [
            'totalInventoryValue' => $total_inventory_value,
            'days' => $days
        ];
    }
}
