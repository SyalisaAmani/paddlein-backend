<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourtController extends Controller
{
    // Get all active courts
    public function index()
    {
        $courts = Court::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'message' => 'Courts retrieved successfully',
            'data' => $courts,
        ], 200);
    }

    // Get active and inactive court for admin
    public function adminIndex()
    {
        // Admin access
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success'=> false,
                'message'=> 'Unauthorized Access'
            ],403);
        }

        // Get all data coutrs
        $courts = Court::all();

        return response()->json([
            'success'=> true,
            'message'=> 'All courts retrieved successfully',
            'data'=> $courts
        ], 200);
    }

    // Get court detail
    public function show($id)
    {
        $court = Court::where('is_active', true)->find($id);

        if (!$court) {
            return response()->json([
                'success' => false,
                'message' => 'Court not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Court detail retrieved successfully',
            'data' => $court,
        ], 200);
    }

    // Get court availability by date
    public function availability( Request $request, $id) {
        // Validate booking date
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        // Check court
        $court = Court::where('is_active', true)->find($id);

        if (!$court) {
            return response()->json([
                'success'=> false,
                'message' => 'Court not found'
            ], 404);
        }

        // Get existing bookings
        $bookings = Booking::where('court_id', $court->id)
        ->whereDate('booking_date', $validated['date'])
        ->where('status', '!=', 'cancelled')
        ->orderBy('start_time')
        ->get(['start_time', 'end_time']);

        return response()->json([
            'success' => true,
            'message' => 'Court availability retrieved successfully',
            'data' => $bookings,
        ], 200);
    }

    // Add court data (Admin)
    public function store(Request $request)
    {
        // Admin access
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success'=> false,
                'message'=> 'Unauthorized Access',
                ],403);
        }

        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image'=> 'nullable|string|max:2048',
            'price_per_hour' => 'required|numeric|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        // Create court
        $court = Court::create([
            'name'=> $validated['name'],
            'description'=> $validated['description'] ?? null,
            'image'=> $validated['image'] ?? null,
            'price_per_hour'=> $validated['price_per_hour'],
            'is_active'=> $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Court created successfully',
            'data' => $court,
        ], 201);
    }

    // Update court data (Admin)
    public function update(Request $request, $id)
    {
        // Admin access
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'success'=> false,
                'message'=> 'Unauthorized Access',
                ],403);
        }

        // Find court
        $court = Court::find( $id );

        if  (!$court) {
            return response()->json([
                'success'=> false,
                'message'=> 'Court not found',
            ],404);
        }

        // Validate input
        $validated = $request->validate([
            'name'=> 'sometimes|required|string|max:255',
            'description'=> 'sometimes|nullable|string|max:1000',
            'image'=> 'sometimes|nullable|string|max:2048',
            'price_per_hour' => 'sometimes|required|numeric|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        // Update court
        $court->update($validated);

        return response()->json([
            'success'=> true,
            'message'=> 'Court updated successfully',
            'data'=> $court,
        ],200);
    }
}