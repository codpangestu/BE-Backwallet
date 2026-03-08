<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_validation_fails()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'username']);
    }

    public function test_successful_register_creates_wallet()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone_number' => '08123456789'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['access_token']);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com'
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->wallet);
        $this->assertEquals(0, $user->wallet->balance);
    }

    public function test_login_successful()
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token']);
    }
}
