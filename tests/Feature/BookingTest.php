<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Court;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    // Helper untuk membuat booking tanpa mass assignment
    protected function createBooking(
        User $user,
        Court $court,
        string $date,
        string $startTime,
        string $endTime
    ): Booking {
        $booking = new Booking();

        $booking->user_id = $user->id;
        $booking->court_id = $court->id;
        $booking->booking_date = $date;
        $booking->start_time = $startTime;
        $booking->end_time = $endTime;
        $booking->total_price = 150000;
        $booking->payment_status = 'unpaid';
        $booking->status = 'confirmed';

        $booking->save();

        return $booking;
    }

    public function test_guest_cannot_access_booking_list(): void
    {
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_customer_can_create_booking(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta')
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

        $response = $this->actingAs($user)
            ->postJson('/api/bookings', [
                'court_id' => $court->id,
                'booking_date' => '2026-11-10',
                'start_time' => '10:00',
                'notes' => 'Automated test booking',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Booking successfully',
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'court_id' => $court->id,
            'booking_date' => '2026-11-10 00:00:00',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_customer_cannot_create_overlapping_booking(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta')
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

        $this->createBooking(
            $user,
            $court,
            '2026-11-10',
            '10:00:00',
            '11:00:00'
        );

        $response = $this->actingAs($user)
            ->postJson('/api/bookings', [
                'court_id' => $court->id,
                'booking_date' => '2026-11-10',
                'start_time' => '10:00',
                'notes' => 'Overlapping test booking',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This court is already booked for the selected time',
            ]);
    }

    public function test_customer_cannot_book_a_past_time(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 10:00:00', 'Asia/Jakarta')
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

        $response = $this->actingAs($user)
            ->postJson('/api/bookings', [
                'court_id' => $court->id,
                'booking_date' => '2026-11-10',
                'start_time' => '09:00',
                'notes' => 'Past time test',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot book a time that has already passed',
            ]);
    }

    public function test_customer_cannot_book_outside_operating_hours(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta')
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

        $response = $this->actingAs($user)
            ->postJson('/api/bookings', [
                'court_id' => $court->id,
                'booking_date' => '2026-11-10',
                'start_time' => '20:30',
                'notes' => 'Outside operating hours test',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Booking time must be between 09:00 and 21:00',
            ]);
    }

    public function test_customer_cannot_book_inactive_court(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta')
        );

        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Inactive Court',
            'description' => 'Inactive court for testing',
            'price_per_hour' => 150000,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/bookings', [
                'court_id' => $court->id,
                'booking_date' => '2026-11-10',
                'start_time' => '10:00',
                'notes' => 'Inactive court test',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_customer_cannot_book_nonexistent_court(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-20 08:00:00', 'Asia/Jakarta')
        );

        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'court_id' => 999,
            'booking_date' => '2026-11-10',
            'start_time' => '10:00',
            'notes' => 'Nonexist court test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['court_id']);
    }

    public function test_customer_cannot_book_without_booking_date(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta')
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

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'court_id' => $court->id,
            'start_time' => '10:00',
            'notes' => 'Missing booking date test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['booking_date']);
    }

    public function test_customer_cannot_book_without_start_time(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta')
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

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'court_id' => $court->id,
            'booking_date' => '2026-11-10',
            'notes' => 'Missing start time test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_customer_cannot_book_with_invalid_start_time_format(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta')
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

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'court_id' => $court->id,
            'booking_date' => '2026-11-10',
            'start_time' => '25:90',
            'notes' => 'Invalid start time format test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_customer_cannot_book_when_duration_exceeds_operating_hours(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta')
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

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'court_id' => $court->id,
            'booking_date' => '2026-11-10',
            'start_time' => '20:30',
            'notes' => 'Exceeds operating hours test',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Booking time must be between 09:00 and 21:00',
            ]);
    }

    public function test_customer_can_only_see_their_own_bookings(): void
    {
        $customerA = User::factory()->create([
            'role' => 'customer',
        ]);

        $customerB = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Test Court',
            'description' => 'Court for testing',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $this->createBooking(
            $customerA,
            $court,
            '2026-11-20',
            '10:00:00',
            '11:00:00'
        );

        $this->createBooking(
            $customerB,
            $court,
            '2026-11-20',
            '11:00:00',
            '12:00:00'
        );

        $response = $this->actingAs($customerA)
            ->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $customerA->id);
    }

    public function test_customer_cannot_cancel_another_customers_booking(): void
    {
        $customerA = User::factory()->create([
            'role' => 'customer',
        ]);

        $customerB = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Test Court',
            'description' => 'Court for testing',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking(
            $customerA,
            $court,
            '2026-11-20',
            '13:00:00',
            '14:00:00'
        );

        $response = $this->actingAs($customerB)
            ->patchJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'user_id' => $customerA->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_admin_can_see_all_customers_bookings(): void
    {
        $customerA = User::factory()->create([
            'role' => 'customer',
        ]);

        $customerB = User::factory()->create([
            'role' => 'customer',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $court = Court::create([
            'name' => 'Test Court',
            'description' => 'Court for testing',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $this->createBooking(
            $customerA,
            $court,
            '2026-11-20',
            '10:00:00',
            '11:00:00'
        );

        $this->createBooking(
            $customerB,
            $court,
            '2026-11-20',
            '11:00:00',
            '12:00:00'
        );

        $response = $this->actingAs($admin)
            ->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_cancel_booking_less_than_two_hours_before_playing_time(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-11-10 11:00:00', 'Asia/Jakarta')
        );

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $court = Court::create([
            'name' => 'Test Court',
            'description' => 'Court for testing',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $booking = $this->createBooking(
            $customer,
            $court,
            '2026-11-10',
            '12:00:00',
            '13:00:00'
        );

        $response = $this->actingAs($admin)
            ->patchJson("/api/bookings/{$booking->id}/cancel");

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
}
