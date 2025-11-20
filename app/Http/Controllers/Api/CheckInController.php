<?php

namespace App\Http\Controllers\Api;
// Use the necessary classes
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;


class CheckInController extends Controller
{
    // We handle the IN/OUT action through a single endpoint.
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Determine the required next action (IN or OUT)
        $latestAction = CheckIn::where('user_id', $user->id)
            ->latest('action_time')
            ->first();

        $requiredAction = (
            !$latestAction || 
            $latestAction->action_type === 'out'
        ) ? 'in' : 'out';

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

        // If no action or the last action was 'out', the next action is 'in'.
        $nextAction = (
            !$latestAction || 
            $latestAction->action_type === 'out'
        ) ? 'in' : 'out';

        return response()->json([
            'status' => $latestAction ? $latestAction->action_type : 'out', // 'in' or 'out'
            'next_action' => $nextAction
        ]);
    }

    public function records(Request $request)
    {
        // 1. Authorization Check (Simple Admin check)
        if (auth()->user()->is_admin !== 1) {
            return redirect('/')->with('error', 'Access denied. Only managers can view this report.');
        }

        // 2. Data Fetching
        // Fetch records for the last 7 days. This avoids loading too much data.
        $startDate = now()->subDays(7)->startOfDay();

        $attendance = CheckIn::with('user') // Eager load user data for names
            ->where('action_time', '>=', $startDate)
            ->orderBy('action_time', 'desc')
            ->get()
            ->groupBy(function($date) {
                // Group by date for easier display in the view
                return \Carbon\Carbon::parse($date->action_time)->format('Y-m-d');
            });

        return view('dashboard.attendances.records', [
            'attendance' => $attendance,
        ]);
    }
}