<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    // GET ALL
    public function getAll()
    {
        return Product::all();
    }

    // CREATE
    public function create(array $data)
    {
        return Product::create($data);
    }

    // UPDATE
    public function update(Product $product, array $data)
    {
        $product->update($data);
        return $product;
    }

    // DELETE
    public function delete(Product $product)
    {
        $product->delete();
        return true;
    }

    // FILTER
    public function search(array $filters)
    {
        return Product::query()
            ->when($filters['category'] ?? null, fn($q, $category) => $q->where('category', $category))
            ->when($filters['price'] ?? null, fn($q, $price) => $q->where('price', $price))
            ->get()
            ->toArray();
    }
}