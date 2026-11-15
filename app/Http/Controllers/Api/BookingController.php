<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Court;
use Carbon\Carbon;

class BookingController extends Controller
{
    //admin
    public function index()
    {
        $user = Auth::user();

        $bookings = $user->role === 'admin'
        ? Booking::with(['user','court'])->latest()->get()
        :Booking::with(['user','court'])-> where('user_id', $user->id)->latest()->get();

        return response()->json([
            'success'=> true,
            'message' =>'Bookings retrieved successfully',
            'data'=> $bookings,
        ], 200);
    }

    // Show detail booking by ID (admin)
    public function show($id)
    {
        $user = Auth::user();

        $booking = Booking::with(['user', 'court'])
            ->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // Customer's booking data
        if ($user->role !== 'admin' && $booking->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking detail retrieved successfully',
            'data' => $booking,
        ], 200);
    }

    // Create booking
    public function store(Request $request)
    {
        $validated = $request->validate([
            'court_id' => 'required|integer|exists:courts,id',
            'booking_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Calculate booking time
        $startTime = Carbon::createFromFormat(
            'H:i',
            $validated['start_time']
        );

        $endTime = $startTime->copy()->addMinutes(60);

        // Check booking date and time
        $bookingDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['booking_date'] . ' ' . $validated['start_time'],
            config('app.timezone')
        );

        if ($bookingDateTime->lte(now())) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot book a time that has already passed',
            ], 422);
        }

        // Check operating hours
        $openingTime = Carbon::createFromTime(9, 0);
        $closingTime = Carbon::createFromTime(21, 0);

        if (
            $startTime->lt($openingTime) ||
            $endTime->gt($closingTime)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Booking time must be between 09:00 and 21:00',
            ], 422);
        }

        // Database transaction
        return DB::transaction(function () use (
            $validated,
            $startTime,
            $endTime
        ) {

            // Lock court row to prevent simultaneous booking
            $court = Court::where('is_active', true)
                ->where('id', $validated['court_id'])
                ->lockForUpdate()
                ->first();

            if (!$court) {
                return response()->json([
                    'success' => false,
                    'message' => 'Court is not available',
                ], 404);
            }

            // Check overlapping booking
            $existingBooking = Booking::where('court_id', $court->id)
                ->whereDate('booking_date', $validated['booking_date'])
                ->where('status', '!=', 'cancelled')
                ->where('start_time', '<', $endTime->format('H:i:s'))
                ->where('end_time', '>', $startTime->format('H:i:s'))
                ->exists();

            if ($existingBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'This court is already booked for the selected time',
                ], 422);
            }

            // Create booking
            $booking = new Booking();
            $booking->user_id = Auth::id();
            $booking->court_id = $court->id;
            $booking->booking_date = $validated['booking_date'];
            $booking->start_time = $startTime->format('H:i:s');
            $booking->end_time = $endTime->format('H:i:s');
            $booking->total_price = $court->price_per_hour;
            $booking->payment_status = 'unpaid';
            $booking->status = 'confirmed';
            $booking->notes = $validated['notes'] ?? null;
            $booking->save();

            $booking->load(['user', 'court']);

            return response()->json([
                'success' => true,
                'message' => 'Booking successfully',
                'data' => $booking,
            ], 201);
        });
    }

    // Method cancel booking
    public function cancel($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        if (Auth::user()->role !== 'admin' && $booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking has already been cancelled',
            ], 422);
        }

        $bookingDateTime = \Carbon\Carbon::parse(
            $booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time,
            config('app.timezone')
        );

        if (Auth::user()->role !== 'admin' && now()->gte($bookingDateTime->copy()->subHours(2))) {
            return response()->json([
                'success' => false,
                'message' => 'Booking can only be cancelled more than 2 hours before the playing time',
            ], 422);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'data' => $booking,
        ], 200);
    }

    // Payment status update (admin)
    public function updatePaymentStatus (Request $request, $id) {

        // Admin access
        if (Auth::user() -> role !== 'admin'){
            return response()->json([
                'success'=> false,
                'message'=> 'Unauthorized access',
            ], 403);
        }

        // Find booking
        $booking = Booking::find( $id );

        if (!$booking) {
            return response()->json([
                'success'=> false,
                'message'=> 'Booking not found',
            ],404);
        }

        // Cannot confirming payment to cancelled booking
        if ($booking->status === 'cancelled') {
            return response()->json([
                'success'=> false,
                'message' => 'Pembayaran booking yang dibatalkan tidak dapat dikonfirmasi'
            ], 422);
        }

        // Validate payment status
        $validated = $request->validate([
            'payment_status'=> 'required|in:unpaid,paid',
        ]);

        // Update payment status
        $booking->payment_status = $validated['payment_status'];
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
            'data' => $booking,
        ], 200);
    }
}