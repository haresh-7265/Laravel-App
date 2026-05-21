<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Seeders are called in dependency order:
     * 1. CategorySeeder - Categories must exist before products
     * 2. UserSeeder - Users must exist before orders
     * 3. ProductSeeder - Products must exist before order items
     * 4. OrderSeeder - Depends on users and products
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
