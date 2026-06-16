<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('finance::dashboard');
    }
}
