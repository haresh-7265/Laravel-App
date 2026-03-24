<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProductService
{
    // GET ALL
    public function getAll()
    {
        return Product::all();
    }

    // CREATE
    public function create(array $data, ?UploadedFile $image)
    {
        if ($image) {
            $data['image'] = $this->uploadImage($image);
        }
        return Product::create($data);
    }

    // UPDATE
    public function update(Product $product, array $data, ?UploadedFile $image = null)
    {
        if ($image) {
            $this->deleteImage($product->image);
            $data['image'] = $this->uploadImage($image);
        }
        $product->update($data);
        return $product;
    }

    // DELETE
    public function delete(Product $product)
    {
        $this->deleteImage($product->image);
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

    // DELETE image
    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    // Upload image

    private function uploadImage(UploadedFile $image): string
    {
        return $image->store('products', 'public');
    }
}