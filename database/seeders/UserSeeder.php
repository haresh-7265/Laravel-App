<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed 4 admin users
        for ($i = 1; $i <= 4; $i++) {
            User::factory()->admin()->create([
                'name' => "Admin {$i}",
                'email' => "admin{$i}@example.com",
                'password' => 'password',
            ]);
        }

        // Seed 1 default customer for easy QA testing
        User::factory()->customer()->create([
            'name' => 'Default Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        // Seed remaining 49 customers
        User::factory()->customer()->count(49)->create();
    }
}
