<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ProductService
{
    // GET ALL
    public function getAll()
    {
        Log::channel('product')->info('Fetched all products', [
            'user_id' => auth()->id(),
        ]);

        return Product::all();
    }

    // CREATE
    public function create(array $data, ?UploadedFile $image)
    {
        if ($image) {
            $data['image'] = $this->uploadImage($image);
        }

        $product = Product::create($data);

        Log::channel('product')->info('Product created', [
            'product_id' => $product->id,
            'data' => $data,
            'user_id' => auth()->id(),
        ]);

        return $product;
    }

    // UPDATE
    public function update(Product $product, array $data, ?UploadedFile $image = null)
    {
        $original = $product->getOriginal();

        if ($image) {
            $this->deleteImage($product->image);
            $data['image'] = $this->uploadImage($image);
        }

        $product->update($data);

        Log::channel('product')->info('Product updated', [
            'product_id' => $product->id,
            'changes' => $product->getChanges(),
            'original' => $original,
            'user_id' => auth()->id(),
        ]);

        return $product;
    }

    // DELETE
    public function delete(Product $product)
    {
        $productData = $product->toArray();

        $this->deleteImage($product->image);
        $product->delete();

        Log::channel('product')->warning('Product deleted', [
            'product_id' => $product->id,
            'data' => $productData,
            'user_id' => auth()->id(),
        ]);

        return true;
    }

    // FILTER / SEARCH
    public function search(array $filters)
    {
        $results = Product::query()
            ->when($filters['category'] ?? null, fn($q, $category) => $q->where('category', $category))
            ->when($filters['price'] ?? null, fn($q, $price) => $q->where('price', $price))
            ->get();

        Log::channel('product')->info('Product search executed', [
            'filters' => $filters,
            'result_count' => $results->count(),
            'user_id' => auth()->id(),
        ]);

        return $results->toArray();
    }

    // DELETE image
    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);

            Log::channel('product')->info('Product image deleted', [
                'path' => $path,
                'user_id' => auth()->id(),
            ]);
        }
    }

    // Upload image
    private function uploadImage(UploadedFile $image): string
    {
        $path = $image->store('products', 'public');

        Log::channel('product')->info('Product image uploaded', [
            'path' => $path,
            'user_id' => auth()->id(),
        ]);

        return $path;
    }
}