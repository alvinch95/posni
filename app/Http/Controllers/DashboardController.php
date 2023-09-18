<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use App\Charts\MonthlyTransactionsChart;

class DashboardController extends Controller
{
    public function index(MonthlyTransactionsChart $chart)
    {
        $year = request('year',now()->format("Y"));
        $currentYear = now()->format("Y");

        $topSellingProducts = SalesOrderDetail::select(
            DB::raw('hampers.name'),
            DB::raw('SUM(sales_order_details.qty)as Qty_Sold'),
            DB::raw('SUM(sales_order_details.selling_price - sales_order_details.capital_price) as Total_Revenue')
        )->join('hampers','hampers.id','=','sales_order_details.hamper_id')
        ->groupBy('hampers.id','hampers.name')
        ->orderByDesc('Qty_Sold')
        ->paginate(5);

        return view('dashboard.index',[
            'chart' => $chart->build($year),
            'currentYear' => $currentYear,
            'topSellingProducts' => $topSellingProducts
        ]);
    }
}
