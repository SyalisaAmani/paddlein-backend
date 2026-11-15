<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourtController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\AuthController;

// Authentication API (Public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Court API (Public)
Route::get('/courts', [CourtController::class, 'index']);
Route::get('/courts/{id}', [CourtController::class, 'show']);
Route::get('/courts/{id}/availability', [CourtController::class, 'availability']);

// Protected API
Route::middleware('auth:sanctum')->group(function () {

    // Booking: Admin dan Customer
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // Admin only
    Route::middleware('admin')->group(function () {
        Route::patch('/bookings/{id}/payment', [BookingController::class, 'updatePaymentStatus']);

        Route::get('/admin/courts', [CourtController::class, 'adminIndex']);
        Route::post('/courts', [CourtController::class, 'store']);
        Route::patch('/courts/{id}', [CourtController::class, 'update']);
    });

    // Logout user
    Route::post('/logout', [AuthController::class, 'logout']);
});
