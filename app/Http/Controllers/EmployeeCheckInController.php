<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EmployeeCheckInController extends Controller
{
    public function index()
    {
        return view('employee.checkin'); 
    }
}
