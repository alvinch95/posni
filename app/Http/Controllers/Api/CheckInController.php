<?php

namespace App\Http\Controllers\Api;
// Use the necessary classes
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use SebastianBergmann\Environment\Console;

class CheckInController extends Controller
{
    // We handle the IN/OUT action through a single endpoint.
    public function store(Request $request)
    {
        $user = $request->user();
        $currentDateTime = now();

        // 1. Determine the required next action (IN or OUT)
        $latestAction = CheckIn::where('user_id', $user->id)
            ->latest('action_time')
            ->first();

        $requiredAction = 'in'; // Default to Check-In

        if ($latestAction) {
            $lastActionDate = \Carbon\Carbon::parse($latestAction->action_time)->toDateString();
            $todayDate = $currentDateTime->toDateString();

            if ($lastActionDate === $todayDate) {
                // Last action was TODAY: Use standard logic
                $requiredAction = ($latestAction->action_type === 'out') ? 'in' : 'out';
            }
            // If last action was YESTERDAY or earlier, requiredAction remains 'in'
        }

        // 2. Validation (Photo, Location)
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photo' => 'required|image|max:2048', // 2MB max image size
        ]);
        
        // 3. Geofencing Check (Function needed here)
        if (!$this->isWithinOfficeGeofence($request->latitude, $request->longitude)) {
            return response()->json([
                'message' => 'Error: Anda harus ada di radius toko untuk check-'.$requiredAction.'.'
            ], 403);
        }

        // 4. Store Photo
        $path = $request->file('photo')->store('checkin_photos', 'public');

        // 5. Record Database Entry
        CheckIn::create([
            'user_id' => $user->id,
            'action_type' => $requiredAction,
            'action_time' => now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'photo_path' => $path,
        ]);

        return response()->json([
            'message' => 'Sukses: Absen check-'.$requiredAction.' tercatat.',
            'status' => $requiredAction
        ], 201);
    }
    
    // 6. Geofencing Helper Function (You need to implement this in the Controller)
    // app/Http/Controllers/Api/CheckInController.php (Add this protected method)
    protected function isWithinOfficeGeofence($employeeLat, $employeeLon)
    {
        // Earth's radius in Kilometers
        $earthRadiusKm = 6371; 

        // Retrieve office coordinates and radius from .env
        $officeLat = (float)env('OFFICE_LAT');
        $officeLon = (float)env('OFFICE_LONG');
        $geofenceRadius = (float)env('GEOFENCE_RADIUS_KM');

        // Convert degrees to radians
        $latFrom = deg2rad($employeeLat);
        $lonFrom = deg2rad($employeeLon);
        $latTo = deg2rad($officeLat);
        $lonTo = deg2rad($officeLon);

        // Haversine calculation variables
        $lonDelta = $lonTo - $lonFrom;
        $latDelta = $latTo - $latFrom;

        // Haversine core formula (a)
        $angle = sin($latDelta / 2) * sin($latDelta / 2) +
                cos($latFrom) * cos($latTo) *
                sin($lonDelta / 2) * sin($lonDelta / 2);

        // Calculate central angle (c) and distance (d)
        $centralAngle = 2 * atan2(sqrt($angle), sqrt(1 - $angle));
        $distanceKm = $earthRadiusKm * $centralAngle;

        // Check if the calculated distance is within the allowed radius
        return $distanceKm <= $geofenceRadius;
    }

    public function status(Request $request)
    {
        $user = $request->user();

        $latestAction = CheckIn::where('user_id', $user->id)
            ->latest('action_time')
            ->first();

        $nextAction = 'in'; // Default to Check-In

        if ($latestAction) {
            $lastActionDate = \Carbon\Carbon::parse($latestAction->action_time)->toDateString();
            $todayDate = now()->toDateString();
            
            if ($lastActionDate === $todayDate) {
                // Last action was TODAY: Use standard logic
                $nextAction = ($latestAction->action_type === 'out') ? 'in' : 'out';
            }
            // If last action was YESTERDAY or earlier, we keep the default 'in'
        }

        return response()->json([
            'status' => $latestAction ? $latestAction->action_type : 'out', 
            'next_action' => $nextAction // Will be 'in' if the last action was yesterday
        ]);
    }

    public function records(Request $request)
    {
        if (auth()->user()->is_admin !== 1) {
            return redirect('/')->with('error', 'Access denied. Only managers can view this report.');
        }

        $selectedMonth = $request->input('month', now()->month);
        $selectedYear = $request->input('year', now()->year);

        $startDate = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $records = CheckIn::with('user')
            ->whereBetween('action_time', [$startDate, $endDate])
            ->orderBy('action_time')
            ->get();

        $summary = $records->groupBy('user_id')->map(function ($userRecords) {
            $user = $userRecords->first()->user;
            $lateCount = 0;
            $days = $userRecords->groupBy(fn($r) => \Carbon\Carbon::parse($r->action_time)->format('Y-m-d'))
                ->map(function ($dayRecords) use (&$lateCount) {
                    $in = $dayRecords->where('action_type', 'in')->sortBy('action_time')->first();
                    $out = $dayRecords->where('action_type', 'out')->sortByDesc('action_time')->first();
                    $inTime = $in ? \Carbon\Carbon::parse($in->action_time)->format('H:i') : '-';
                    if ($inTime !== '-' && $inTime > '08:05') {
                        $lateCount++;
                    }
                    return [
                        'in' => $inTime,
                        'out' => $out ? \Carbon\Carbon::parse($out->action_time)->format('H:i') : '-',
                        'in_photo' => $in->photo_path ?? null,
                        'in_lat' => $in->latitude ?? null,
                        'in_lng' => $in->longitude ?? null,
                        'out_photo' => $out->photo_path ?? null,
                        'out_lat' => $out->latitude ?? null,
                        'out_lng' => $out->longitude ?? null,
                    ];
                });
            return [
                'name' => $user->name ?? 'N/A',
                'total_days' => $days->count(),
                'late_count' => $lateCount,
                'days' => $days,
            ];
        })->sortBy('name')->values();

        return view('dashboard.attendances.records', [
            'summary' => $summary,
            'daysInMonth' => $daysInMonth,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'startDate' => $startDate,
        ]);
    }

    public function myHistory(Request $request)
    {
        $startDate = today();

        $records = CheckIn::where('user_id', auth()->id())
            ->where('action_time', '>=', $startDate)
            ->orderBy('action_time', 'desc')
            ->limit(10) // Limit to 10 latest entries
            ->get();

        return response()->json($records);
    }
}