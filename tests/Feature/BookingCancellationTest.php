<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Court;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta')
        );
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();

        parent::tearDown();
    }

    // Helper untuk membuat booking tanpa mass assignment
    protected function createBooking(User $user, Court $court): Booking
    {
        $booking = new Booking();

        $booking->user_id = $user->id;
        $booking->court_id = $court->id;
        $booking->booking_date = '2026-11-10';
        $booking->start_time = '13:00:00';
        $booking->end_time = '14:00:00';
        $booking->total_price = 150000;
        $booking->payment_status = 'unpaid';
        $booking->status = 'confirmed';

        $booking->save();

        return $booking;
    }

    public function test_customer_can_cancel_more_than_two_hours_before_booking(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Test Court',
            'description' => 'Court for testing',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking($user, $court);

        $response = $this->actingAs($user)
            ->patchJson('/api/bookings/' . $booking->id . '/cancel');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_customer_cannot_cancel_exactly_two_hours_before_booking(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 11:00:00', 'Asia/Jakarta')
        );

        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Test Court',
            'description' => 'Court for testing',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking($user, $court);

        $response = $this->actingAs($user)
            ->patchJson('/api/bookings/' . $booking->id . '/cancel');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Booking can only be cancelled more than 2 hours before the playing time',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_customer_cannot_cancel_less_than_two_hours_before_booking(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 11:30:00', 'Asia/Jakarta')
        );

        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Test Court',
            'description' => 'Court for testing',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking($user, $court);

        $response = $this->actingAs($user)
            ->patchJson('/api/bookings/' . $booking->id . '/cancel');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Booking can only be cancelled more than 2 hours before the playing time',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }
}
