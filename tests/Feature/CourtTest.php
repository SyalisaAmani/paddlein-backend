<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Court;

class CourtTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_can_create_court(): void
    {
        // Create admin account
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Login as admin
        $this->actingAs($admin);

        // Send request to create court
        $response = $this->postJson('/api/courts', [
            'name'=> 'Paddle Court 4',
            'description' => 'Premium paddle court',
            'price_per_hour' => 225000,
            'is_active' => true,
        ]);

        // Assert response
        $response->assertStatus(201)->assertJson([
            'success' => true,
            'message' => 'Court created successfully',
        ]);

        // Assert database
        $this->assertDatabaseHas('courts', [
            'name'=> 'Paddle Court 4',
            'price_per_hour' => 225000,
            'is_active' => true,
        ]);
    }

    // Customer can't create court test
    public function test_customer_cannot_create_court(): void
    {
        // Create customer account
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        // Login as customer
        $this->actingAs($customer);

        // Attempt to create court
        $response = $this->postJson('/api/courts', [
            'name'=> 'Paddle Court 5',
            'description'=> 'New court',
            'price_per_hour'=> 250000,
            'is_active' => true,
        ]);

        // Assert forbidden response
        $response->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Unauthorized access',
        ]);

        // Assert court was not created
        $this->assertDatabaseMissing('courts', [
            'name'=> 'Paddle Court 5',
        ]);
    }

    // Guest can't create court test
    public function test_guest_cannot_create_court(): void
    {
        $response = $this->postJson('/api/courts', [
            'name' => 'Paddle Court 6',
            'description'=> 'New court',
            'price_per_hour'=> 250000,
            'is_active' => true,
        ]);

        $response->assertStatus(401)->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        $this->assertDatabaseMissing('courts', [
            'name'=> 'Paddle Cout 6',
        ]);
    }

    // Admin can't create court with invalid data test
    public function test_admin_cannot_create_court_with_invalid_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        // Send incomplete data
        $response = $this->postJson('/api/courts', [
            'description' => 'Court without required data',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'name',
            'price_per_hour',
        ]);

        $this->assertDatabaseMissing('courts', [
            'description' => 'Court without required data',
        ]);
    }

    // Update court data test
    public function test_admin_can_update_court(): void
    {
        // Create admin account
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create court
        $court = Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Standard court',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        // Login as admin
        $this->actingAs($admin);

        // Update court
        $response = $this->patchJson('/api/courts/' . $court->id, [
            'name' => 'Paddle Court Premium',
            'price_per_hour' => 250000,
        ]);

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Court updated successfully',
            ]);

        // Assert database
        $this->assertDatabaseHas('courts', [
            'id' => $court->id,
            'name' => 'Paddle Court Premium',
            'price_per_hour' => 250000,
        ]);
    }

    // Customer can't update court test
    public function test_customer_cannot_update_court(): void
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

        $this->actingAs($customer);

        $response = $this->patchJson('/api/courts/' . $court->id, [
            'name' => 'Paddle Court Premium',
            'price_per_hour' => 250000,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access',
            ]);

        // Ensure original data remains unchanged
        $this->assertDatabaseHas('courts', [
            'id' => $court->id,
            'name' => 'Paddle Court 1',
            'price_per_hour' => 150000,
        ]);
    }

    // Can't update none exist court test
    public function test_admin_cannot_update_nonexistent_court(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $response = $this->patchJson('/api/courts/999', [
            'name' => 'Paddle Court Premium',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Court not found',
            ]);
    }

    // Invalid price update court test
    public function test_admin_cannot_update_court_with_invalid_price(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $court = Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Standard court',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->patchJson('/api/courts/' . $court->id, [
            'price_per_hour' => -50000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'price_per_hour',
            ]);

        // Ensure original price remains unchanged
        $this->assertDatabaseHas('courts', [
            'id' => $court->id,
            'price_per_hour' => 150000,
        ]);
    }

    // Deactivate court test (admin)
    public function test_admin_can_deactivate_court(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $court = Court::create([
            'name' => 'Paddle Court 1',
            'description' => 'Standard court',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->patchJson('/api/courts/' . $court->id, [
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Court updated successfully',
                'data' => [
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('courts', [
            'id' => $court->id,
            'is_active' => false,
        ]);
    }
}
