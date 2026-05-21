<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => function (array $attributes) {
                return Product::find($attributes['product_id'])?->name ?? implode(' ', fake()->words(3));
            },
            'price' => function (array $attributes) {
                return Product::find($attributes['product_id'])?->price ?? fake()->randomFloat(2, 10, 500);
            },
            'discount_price' => function (array $attributes) {
                return Product::find($attributes['product_id'])?->discount_price;
            },
            'quantity' => fake()->numberBetween(1, 5),
            'subtotal' => function (array $attributes) {
                $price = $attributes['discount_price'] ?? $attributes['price'];
                return $price * $attributes['quantity'];
            },
        ];
    }
}
