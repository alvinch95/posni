<?php

namespace App\Http\Controllers;

use App\Charts\MonthlyTransactionsChart;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(MonthlyTransactionsChart $chart)
    {
        $year = request('year',now()->format("Y"));
        $currentYear = now()->format("Y");
        return view('dashboard.index',[
            'chart' => $chart->build($year),
            'currentYear' => $currentYear
        ]);
    }
}
