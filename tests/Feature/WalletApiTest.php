<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup a user with a wallet
        $this->user = User::factory()->create();
        $this->user->wallet()->create(['balance' => 0]);
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_check_balance()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/wallet');

        $response->assertStatus(200)
            ->assertJson(['balance' => 0]);
    }

    public function test_successful_topup()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/topup', [
                    'amount' => 50000
                ]);

        $response->assertStatus(200)
            ->assertJson(['balance' => 50000]);

        $this->assertEquals(50000, $this->user->wallet->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $this->user->wallet->id,
            'amount' => 50000,
            'type' => 'top_up'
        ]);
    }

    public function test_successful_transfer()
    {
        $this->user->wallet->update(['balance' => 100000]);

        $receiver = User::factory()->create(['email' => 'target@example.com']);
        $receiver->wallet()->create(['balance' => 0]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfer', [
                    'amount' => 20000,
                    'target' => 'target@example.com'
                ]);

        $response->assertStatus(200);
        $this->assertEquals(80000, $this->user->wallet->fresh()->balance);
        $this->assertEquals(20000, $receiver->wallet->fresh()->balance);

        // Assert transactions logged
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $this->user->wallet->id,
            'related_wallet_id' => $receiver->wallet->id,
            'type' => 'transfer_out',
            'amount' => 20000
        ]);
    }

    public function test_transfer_fails_and_rollback_on_insufficient_balance()
    {
        $this->user->wallet->update(['balance' => 10000]);

        $receiver = User::factory()->create(['email' => 'target2@example.com']);
        $receiverWallet = $receiver->wallet()->create(['balance' => 5000]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfer', [
                    'amount' => 20000, // exceeds balance
                    'target' => 'target2@example.com'
                ]);

        $response->assertStatus(422);

        // Verify balance hasn't changed (rolled back or never deducted)
        $this->assertEquals(10000, $this->user->wallet->fresh()->balance);
        $this->assertEquals(5000, $receiver->wallet->fresh()->balance);
    }

    public function test_transfer_validation_fails_on_invalid_amount()
    {
        $this->user->wallet->update(['balance' => 100000]);

        // Scenario 1: Empty string
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfer', [
                    'amount' => '',
                    'target' => 'target@example.com'
                ]);
        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['amount'])
            ->assertSee('Nominal tidak boleh kosong.');

        // Scenario 2: Alphanumeric/Letters
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfer', [
                    'amount' => 'abc',
                    'target' => 'target@example.com'
                ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['amount'])
            ->assertSee('Nominal harus berupa angka.');

        // Scenario 3: Negative Number
        $response3 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfer', [
                    'amount' => -5000,
                    'target' => 'target@example.com'
                ]);
        $response3->assertStatus(422)
            ->assertJsonValidationErrors(['amount'])
            ->assertSee('Nominal minimal transfer adalah 1000.');

        // Scenario 4: Decimal Number / Symbols
        $response4 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfer', [
                    'amount' => 1500.50,
                    'target' => 'target@example.com'
                ]);
        $response4->assertStatus(422)
            ->assertJsonValidationErrors(['amount'])
            ->assertSee('Nominal hanya boleh bilangan bulat.');

        // Scenario 5: Over Maximum Limit
        $response5 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/transfer', [
                    'amount' => 20000000,
                    'target' => 'target@example.com'
                ]);
        $response5->assertStatus(422)
            ->assertJsonValidationErrors(['amount'])
            ->assertSee('Nominal melebihi batas maksimum transaksi.');
    }
}
