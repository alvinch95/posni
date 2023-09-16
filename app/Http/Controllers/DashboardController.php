<?php

namespace App\Http\Controllers;

use App\Charts\MonthlyTransactionsChart;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(MonthlyTransactionsChart $chart)
    {
        $year = request('year',date("Y"));
        return view('dashboard.index',[
            'chart' => $chart->build($year),
            'currentYear' => intval(date("Y"))
        ]);
    }
}
