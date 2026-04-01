<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;

class ProductService
{
    // GET ALL
    public function getAll()
    {

        return Product::with('category')->get();
    }

    // CREATE
    public function create(array $data, ?UploadedFile $image)
    {
        try {
            if ($image) {
                $data['image'] = $this->uploadImage($image);
            }

            $product = Product::create($data);

            Log::channel('product')->info('Product created', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category_id' => $product->category_id,
                'has_image' => !is_null($product->image),
                'created_by' => auth()->id(),
            ]);

            return $product;

        } catch (QueryException $e) {
            // DB error Log
            Log::channel('product')->error('Failed to create product', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'created_by' => auth()->id(),
            ]);

            throw $e;   // re-throw so controller/handler can respond

        } catch (\Exception $e) {
            // Log unexpected errors
            Log::channel('product')->error('Unexpected error creating product', [
                'error' => $e->getMessage(),
                'created_by' => auth()->id(),
            ]);

            throw $e;
        }
    }


    // UPDATE
    public function update(Product $product, array $data, ?UploadedFile $image = null)
    {
        try {

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
                'updated_by' => auth()->id(),
            ]);

            return $product;
        } catch (QueryException $e) {
            Log::channel('product')->error('Failed to update product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            Log::channel('product')->error('Unexpected error updating product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id(),
            ]);

            throw $e;
        }
    }

    // DELETE
    public function delete(Product $product): bool
    {
        try {
            $snapshot = $product->only(['id', 'name', 'price', 'category_id', 'image', 'stock']);

            $this->deleteImage($product->image);
            $product->delete();

            Log::channel('product')->warning('Product deleted', [
                'product_id' => $snapshot['id'],
                'product_name' => $snapshot['name'],
                'deleted_by' => auth()->id(),
                'product_data' => $snapshot,
            ]);

            return true;

        } catch (QueryException $e) {
            Log::channel('product')->error('Failed to delete product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'deleted_by' => auth()->id(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            Log::channel('product')->error('Unexpected error deleting product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'deleted_by' => auth()->id(),
            ]);

            throw $e;
        }
    }

    // FILTER / SEARCH
    public function search(array $filters): array
    {
        try {
            $results = Product::query()
                ->when($filters['category'] ?? null, fn($q, $v) => $q->where('category_id', $v))
                ->when($filters['price'] ?? null, fn($q, $v) => $q->where('price', $v))
                ->get();

            if ($results->isEmpty()) {
                Log::channel('product')->info('Product search returned no results', [
                    'filters' => $filters,
                    'user_id' => auth()->id(),
                ]);
            }

            Log::channel('product')->debug('Product search executed', [
                'filters' => $filters,
                'result_count' => $results->count(),
                'user_id' => auth()->id(),
            ]);

            return $results->toArray();

        } catch (QueryException $e) {
            Log::channel('product')->error('Product search query failed', [
                'filters' => $filters,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            throw $e;
        }
    }

    // DELETE image
    private function deleteImage(?string $path): void
    {
        try {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);

            }
        } catch (\Exception $e) {
            Log::channel('product')->error('Failed to delete product image', [
                'path' => $path,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    // Upload image
    private function uploadImage(UploadedFile $image): string
    {
        try {
            $path = $image->store('products', 'public');

            return $path;
        } catch (\Exception $e) {
            Log::channel('product')->error('Product image upload failed', [
                'original_name' => $image->getClientOriginalName(),
                'mime_type' => $image->getMimeType(),
                'size' => $image->getSize(),
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            throw $e;
        }
    }
}