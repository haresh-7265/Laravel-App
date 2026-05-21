<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed 10 default reference categories
        $categories = [
            ['name' => 'Electronics'],
            ['name' => 'Clothing'],
            ['name' => 'Books'],
            ['name' => 'Furniture'],
            ['name' => 'Beauty & Health'],
            ['name' => 'Sports & Outdoors'],
            ['name' => 'Toys & Games'],
            ['name' => 'Automotive'],
            ['name' => 'Groceries'],
            ['name' => 'Home & Kitchen'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }

        // 2. Seed default admin user
        User::firstOrCreate(
            ['email' => config('admin.email', 'admin@example.com')],
            [
                'name' => config('admin.name', 'Admin'),
                'password' => Hash::make(config('admin.password', 'password')),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('ProductionSeeder: Reference data seeded successfully.');
    }
}
