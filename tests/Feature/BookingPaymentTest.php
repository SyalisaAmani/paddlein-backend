<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Booking;
use App\Models\Court;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    // Helper untuk membuat booking tanpa mass assignment
    protected function createBooking(User $user, Court $court): Booking
    {
        $booking = new Booking();

        $booking->user_id = $user->id;
        $booking->court_id = $court->id;
        $booking->booking_date = '2026-11-20';
        $booking->start_time = '10:00:00';
        $booking->end_time = '11:00:00';
        $booking->total_price = 150000;
        $booking->payment_status = 'unpaid';
        $booking->status = 'confirmed';
        $booking->notes = null;

        $booking->save();

        return $booking;
    }

    public function test_admin_can_update_payment_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Standard court',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking($customer, $court);

        $this->actingAs($admin);

        $response = $this->patchJson('/api/bookings/' . $booking->id . '/payment', [
            'payment_status' => 'paid',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'data' => [
                    'payment_status' => 'paid',
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'paid',
        ]);
    }

    // Customer cannot update payment status
    public function test_customer_cannot_update_payment_status(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Standard court',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking($customer, $court);

        $this->actingAs($customer);

        $response = $this->patchJson('/api/bookings/' . $booking->id . '/payment', [
            'payment_status' => 'paid',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'unpaid',
        ]);
    }

    // Guest cannot update payment status
    public function test_guest_cannot_update_payment_status(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Standard court',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking($customer, $court);

        $response = $this->patchJson('/api/bookings/' . $booking->id . '/payment', [
            'payment_status' => 'paid',
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'unpaid',
        ]);
    }

    // Admin cannot update payment status for nonexistent booking
    public function test_admin_cannot_update_payment_status_for_nonexistent_booking(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $response = $this->patchJson('/api/bookings/999/payment', [
            'payment_status' => 'paid',
        ]);

        $response->assertStatus(404);
    }

    // Admin cannot update payment status with invalid value
    public function test_admin_cannot_update_payment_status_with_invalid_value(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Standard court',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking($customer, $court);

        $this->actingAs($admin);

        $response = $this->patchJson('/api/bookings/' . $booking->id . '/payment', [
            'payment_status' => 'pending',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'unpaid',
        ]);
    }

    // Admin cannot update payment status without value
    public function test_admin_cannot_update_payment_status_without_value(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Standard court',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking($customer, $court);

        $this->actingAs($admin);

        $response = $this->patchJson('/api/bookings/' . $booking->id . '/payment', []);

        $response->assertStatus(422);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'unpaid',
        ]);
    }
}
