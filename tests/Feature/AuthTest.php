<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // Registration customer test
    public function test_customer_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test customer',
            'email'=> 'customer@example.com',
            'password'=> 'Customer123',
            'password_confirmation' => 'Customer123',
        ]);

        $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Registration successfull'
        ]);

        $this->assertDatabaseHas('users', [
            'name'=> 'Test customer',
            'email'=> 'customer@example.com',
            'role' => 'customer',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    // Register using registered email
    public function test_customer_cannot_register_with_existing_email(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Another Customer',
            'email' => 'customer@example.com',
            'password' => 'Customer123',
            'password_confirmation' => 'Customer123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    // Register without name test
    public function test_customer_cannot_register_without_name(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'customer@example.com',
            'password' => 'Customer123',
            'password_confirmation' => 'Customer123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('users', 0);
    }

    // Password register less tahn 8 characters test
    public function test_customer_cannot_register_with_short_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }

    // Unmatched password confirmation test
    public function test_customer_cannot_register_with_unmatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => 'Customer123',
            'password_confirmation' => 'Different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }

    // Customer success login test
    public function test_customer_can_login(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'Customer123',
            'role' => 'customer',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'customer@example.com',
            'password' => 'Customer123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'email' => 'customer@example.com',
                        'role' => 'customer',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    // Incorrect password login test
    public function test_customer_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'Customer123',
            'role' => 'customer',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'customer@example.com',
            'password' => 'WrongPassword123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid email or password',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // Login with unregistered email test
    public function test_customer_cannot_login_with_unregistered_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'unknown@example.com',
            'password' => 'Customer123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid email or password',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}