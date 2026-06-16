<?php

namespace App\Chen\Modules\Finance\Controllers;

use App\Chen\Modules\Finance\Models\FinanceSetting;
use App\Chen\Modules\Finance\Models\Transaction;
use App\Chen\Modules\Finance\Services\Analytics;
use App\Chen\Modules\Finance\Services\RecurringGenerator;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Analytics $analytics, RecurringGenerator $generator)
    {
        $generator->run(); // idempotent catch-up so dashboards reflect due recurring rows

        $uid = Auth::guard('chen')->id();
        $now = Carbon::now();

        $summary = $analytics->monthSummary($uid, $now);
        $byCategory = $analytics->expenseByCategory($uid, $now);
        $trend = $analytics->savingsTrend($uid, $now);
        $averages = $analytics->expenseAverages($uid, $now);
        $setting = FinanceSetting::firstOrNew(['chen_user_id' => $uid]);
        $recent = Transaction::with('category')->where('chen_user_id', $uid)
            ->orderByDesc('date')->orderByDesc('id')->limit(5)->get();

        return view('finance::dashboard', compact(
            'summary', 'byCategory', 'trend', 'averages', 'setting', 'recent'
        ));
    }
}
