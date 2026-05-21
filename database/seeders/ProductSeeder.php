<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminIds = User::where('role', 'admin')->pluck('id')->toArray();
        $categoryIds = Category::pluck('id')->toArray();

        if (empty($adminIds) || empty($categoryIds)) {
            $this->command->error('Admin users and categories must be seeded before products.');

            return;
        }

        DB::transaction(function () use ($adminIds, $categoryIds) {
            Product::factory()->count(50)->create([
                'category_id' => fn () => fake()->randomElement($categoryIds),
                'created_by' => fn () => fake()->randomElement($adminIds),
                'updated_by' => fn () => fake()->randomElement($adminIds),
            ]);

            // onsale products
            Product::factory()->onsale()->count(50)->create([
                'category_id' => fn () => fake()->randomElement($categoryIds),
                'created_by' => fn () => fake()->randomElement($adminIds),
                'updated_by' => fn () => fake()->randomElement($adminIds),
            ]);

            // featured products
            Product::factory()->featured()->count(50)->create([
                'category_id' => fn () => fake()->randomElement($categoryIds),
                'created_by' => fn () => fake()->randomElement($adminIds),
                'updated_by' => fn () => fake()->randomElement($adminIds),
            ]);

            // outofstock products
            Product::factory()->outOfStock()->count(50)->create([
                'category_id' => fn () => fake()->randomElement($categoryIds),
                'created_by' => fn () => fake()->randomElement($adminIds),
                'updated_by' => fn () => fake()->randomElement($adminIds),
            ]);

        });
    }
}
