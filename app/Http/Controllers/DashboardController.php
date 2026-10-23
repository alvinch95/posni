<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use App\Support\DashboardPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DashboardController extends Controller
{
    // "/" is shared by everyone who logs in: staff go to attendance, admins to the dashboard
    public function home()
    {
        if (!Gate::allows('admin')) {
            return redirect('/attendance/checkin');
        }

        return $this->index();
    }

    public function index()
    {
        return view('dashboard.index', [
            'presets' => DashboardPeriod::PRESET_LABELS,
            'defaultPreset' => DashboardPeriod::DEFAULT_PRESET,
        ]);
    }

    public function getData(Request $request, DashboardMetrics $metrics)
    {
        $request->validate([
            'period' => ['nullable', Rule::in(DashboardPeriod::PRESETS)],
            'from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        try {
            $period = DashboardPeriod::resolve(
                $request->input('period', DashboardPeriod::DEFAULT_PRESET),
                $request->input('from'),
                $request->input('to')
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['period' => $e->getMessage()]);
        }

        return response()->json($metrics->forPeriod($period));
    }
}
