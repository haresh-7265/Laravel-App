<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

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
                'discount_price' => 65000,
                'stock' => 10,
                'tags' => ['apple', 'mobile', 'smartphone'],
            ],
            [
                'name' => 'Samsung Galaxy S23',
                'description' => 'Latest Samsung flagship phone',
                'price' => 65000,
                'discount_price' => 60000,
                'stock' => 15,
                'tags' => ['android', 'mobile', 'samsung'],
            ],
            [
                'name' => 'Dell XPS Laptop',
                'description' => 'High performance laptop',
                'price' => 90000,
                'discount_price' => null,
                'stock' => 5,
                'tags' => ['laptop', 'dell', 'work'],
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse',
                'price' => 1200,
                'discount_price' => 999,
                'stock' => 50,
                'tags' => ['accessory', 'mouse', 'wireless'],
            ],
        ];

        foreach ($products as $product) {
            Product::create([
                'name' => $product['name'],
                'slug' => Str::slug($product['name']),
                'description' => $product['description'],
                'price' => $product['price'],
                'discount_price' => $product['discount_price'],
                'stock' => $product['stock'],
                'category_id' => $categories[array_rand($categories)],
                'tags' => $product['tags'], // JSON handled by cast
            ]);
        }
    }
}