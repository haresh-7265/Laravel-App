<?php

namespace App\Collections;

use Illuminate\Database\Eloquent\Collection;

class ProductCollection extends Collection
{
    // Filter products that have stock > 0
    public function inStock(): static
    {
        return $this->filter(fn($product) => $product->stock > 0)->values();
    }

    // Filter products within a price range
    public function byPriceRange(float $min, float $max): static
    {
        return $this->filter(
            fn($product) => $product->price >= $min && $product->price <= $max
        )->values();
    }

    // Filter featured products
    public function featured(): static
    {
        return $this->filter(
            fn($product) => in_array('featured', $product->tags ?? [])
        )->values();
    }

    // Filter products that have a discount
    public function onSale(): static
    {
        return $this->filter(
            fn($product) => !is_null($product->discount_price) && $product->discount_price > 0 && $product->discount_price > $product->price
        )->values();
    }

    // Calculate total inventory value (price × stock) for all products
    public function totalValue(): float
    {
        return $this->sum(fn($product) => $product->discount_price ?? $product->price * $product->stock);
    }

}
