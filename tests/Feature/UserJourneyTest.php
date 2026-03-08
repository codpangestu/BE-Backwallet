<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class UserJourneyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test a complete flow from registration to transfer and checking history.
     */
    public function test_full_user_wallet_journey()
    {
        $regResponseA = $this->postJson('/api/register', [
            'name' => 'Alice',
            'username' => 'alice123',
            'email' => 'alice@example.com',
            'password' => 'password',
            'phone_number' => '0811111111'
        ]);
        $regResponseA->assertStatus(201);
        $tokenA = $regResponseA->json('access_token');

        $this->postJson('/api/register', [
            'name' => 'Bob',
            'username' => 'bob456',
            'email' => 'bob@example.com',
            'password' => 'password',
            'phone_number' => '0822222222'
        ])->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/topup', [
                'amount' => 100000
            ])->assertStatus(200)
            ->assertJson(['balance' => 100000]);

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/transfer', [
                'amount' => 50000,
                'target' => 'bob@example.com'
            ])->assertStatus(200)
            ->assertJson(['balance' => 50000]);

        $userB = User::where('email', 'bob@example.com')->first();
        $this->assertEquals(50000, $userB->wallet->balance);

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/transactions')
            ->assertStatus(200)
            ->assertJsonCount(2, 'transactions');

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/transfer', [
                'amount' => 10000,
                'target' => 'alice@example.com'
            ])->assertStatus(422)
            ->assertSee('Cannot transfer to yourself.');

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/transfer', [
                'amount' => 1000.50,
                'target' => 'bob@example.com'
            ])->assertStatus(422)
            ->assertSee('Nominal hanya boleh bilangan bulat.');
    }
}
