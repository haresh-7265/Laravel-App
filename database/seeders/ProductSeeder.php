<?php

namespace Database\Seeders;

use App\Models\Admin;
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
        $admins = Admin::all();
        $adminIds = $admins->pluck('id')->toArray();
        $categoryIds = Category::pluck('id')->toArray();

        if (empty($adminIds) || empty($categoryIds)) {
            $this->command->error('Admin users and categories must be seeded before products.');

            return;
        }

        $morphType = app(Admin::class)->getMorphClass(); // 'admin'

    $morphOverride = fn() => [
        'category_id'      => fake()->randomElement($categoryIds),
        'created_by_id'    => fake()->randomElement($adminIds),
        'created_by_type'  => $morphType,
        'updated_by_id'    => fake()->randomElement($adminIds),
        'updated_by_type'  => $morphType,
    ];

        DB::transaction(function () use ($morphOverride) {
            Product::factory()->count(50)->create($morphOverride);

            // onsale products
            Product::factory()->onsale()->count(50)->create($morphOverride);

            // featured products
            Product::factory()->featured()->count(50)->create($morphOverride);

            // outofstock products
            Product::factory()->outOfStock()->count(50)->create($morphOverride);

        });
    }
}
