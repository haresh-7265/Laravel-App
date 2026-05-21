<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = implode(' ', fake()->words(3));
        $price = fake()->randomFloat(2, 10, 1000);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'price' => $price,
            'discount_price' => null,
            'stock' => fake()->numberBetween(0, 100),
            'avg_rating' => fake()->randomFloat(2, 0, 5),
            'is_active' => fake()->boolean(80),
            'category_id' => Category::factory(),
            'description' => fake()->paragraph(),
            'tags' => [],
            'image' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Define the featured state.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'tags' => ['featured'],
        ]);
    }

    /**
     * Define the out of stock state.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    /**
     * Define the on sale state.
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_price' => fake()->randomFloat(2, 5, $attributes['price'] - 1),
        ]);
    }

    /**
     * Define the published state.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
