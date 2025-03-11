<?php

namespace App\Http\Controllers;

use App\Models\Rsvp;
use App\Models\Wish;
use Illuminate\Http\Request;

class WeddingInvitationController extends Controller
{
    public function index()
    {
        return view('invitation');
    }

    public function storeRSVP(Request $request)
    {
        // Save RSVP data
        Rsvp::create([
            'name' => $request->guest_name,
            'pax' => $request->guest_pax,
            'attendance' => $request->attendance_status,
        ]);

        // Return success message as JSON
        return response()->json(['success' => 'Thank you for your RSVP!']);
    }
    
    public function storeWish(Request $request)
    {
        Wish::create([
            'name' => $request->name,
            'message' => $request->message,
        ]);

        return response()->json(['success' => 'Thank you for your wish!']);
    }

    public function getWishes()
    {
        $wishes = Wish::latest()->get(); // Retrieve all wishes (newest first)
        return response()->json($wishes);
    }


}
