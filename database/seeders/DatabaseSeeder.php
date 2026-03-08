<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $testUser->wallet()->create(['balance' => 0]);

        $receiver = User::factory()->create([
            'name' => 'Receiver',
            'email' => 'receiver@example.com',
            'password' => bcrypt('password'),
        ]);
        $receiver->wallet()->create(['balance' => 0]);
    }
}
