<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Get all category IDs
        $categories = Category::pluck('id')->toArray();

        // Sample products
        $products = [
            [
                'name' => 'iPhone 13',
                'description' => 'Apple smartphone with A15 chip',
                'price' => 70000,
                'stock' => 10,
            ],
            [
                'name' => 'Samsung Galaxy S23',
                'description' => 'Flagship Android phone',
                'price' => 65000,
                'stock' => 15,
            ],
            [
                'name' => 'Dell Laptop',
                'description' => 'Powerful laptop for work',
                'price' => 55000,
                'stock' => 8,
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse',
                'price' => 800,
                'stock' => 50,
            ],
        ];

        foreach ($products as $product) {
            Product::create([
                'name' => $product['name'],
                'description' => $product['description'],
                'category_id' => $categories[array_rand($categories)], // random category
                'price' => $product['price'],
                'stock' => $product['stock'],
            ]);
        }
    }
}