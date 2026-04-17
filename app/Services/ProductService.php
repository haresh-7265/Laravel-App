<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function getAll()
    {
        return Cache::tags(['products', 'products.list'])->remember('product.all', now()->addHour(), fn() => Product::active()->get());
    }

    public function getHomepageProducts(int $page, array $filters, int $perPage = 10): array
    {
        return Concurrency::run([
            'featured' => fn() => Cache::tags(['products', 'products.list'])->remember('products.featured', now()->addHour(), fn() => $this->getAll()->featured()->take(8)),
            'newArrivals' => fn() => Cache::tags(['products', 'products.list'])->remember('products.new', now()->addHour(), fn() => Product::active()->latest()->take(8)->get()),
            'onSale' => fn() => Cache::tags(['products', 'products.list'])->remember('products.onsale', now()->addHour(), fn() => $this->getAll()->onSale()->take(8)),
            'products' => fn() => $this->getPaginatedProducts($page, $filters, $perPage),
        ]);
    }
    // GET paginated products
    public function getPaginatedProducts(int $page, array $filters, int $perPage = 10)
    {
        ksort($filters);

        $cacheKey = 'products.' . md5(json_encode([
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
        ]));

        return Cache::tags(['products', 'products.list'])->remember($cacheKey, now()->addHour(), function () use ($perPage, $filters) {
            $ids = $this->apply($filters)->toArray();
            $products = Product::active()
                ->with('category')
                ->whereIn('id', $ids)
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->paginate($perPage)
                ->withQueryString();
            return $products;
        });
    }

    // CREATE
    public function create(array $data, ?UploadedFile $image)
    {
        try {
            if ($image) {
                $data['image'] = $this->uploadImage($image);
            }

            $product = Product::create($data);

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

            if ($image) {
                $this->deleteImage($product->image);
                $data['image'] = $this->uploadImage($image);
            }

            $product->update($data);

            return $product->refresh();

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

            $this->deleteImage($product->image);
            $product->delete();

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
                ->active()
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

    // filters
    public function apply(array $filters): Collection
    {
        // ─── 1. Load all products with category (single DB query) ──
        $products = $this->getAll();

        // ─── 2. Price range filter — filter() ──────────────────────
        if (!empty($filters['min_price'])) {
            $min = (float) $filters['min_price'];
            $products = $products->filter(fn(Product $p) => (float) $p->getFinalPriceAttribute() >= $min);
        }

        if (!empty($filters['max_price'])) {
            $max = (float) $filters['max_price'];
            $products = $products->filter(fn(Product $p) => (float) $p->getFinalPriceAttribute() <= $max);
        }

        // ─── 3. Multiple categories filter — filter() 
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $categoryIds = array_map('intval', $filters['categories']);
            $products = $products->filter(
                fn(Product $p) => in_array($p->category_id, $categoryIds)
            );
        }

        // ─── 4. In-stock only — where() ────────────────────────────
        if (!empty($filters['in_stock'])) {
            $products = $products->where('stock', '>', 0);
        }

        // ─── 5. On sale (has discount) — filter() ──────────────────
        if (!empty($filters['on_sale'])) {
            $products = $products->filter(function (Product $p) {
                return $p->discount_price
                    && (float) $p->discount_price > 0
                    && (float) $p->discount_price < (float) $p->price;
            });
        }

        // ─── 6. Sorting — sortBy() / sortByDesc() ──────────────────
        $products = $this->applySorting($products, $filters['sort'] ?? null);

        return $products->pluck('id'); // re-index
    }

    /**
     * Apply sorting using sortBy() and sortByDesc().
     */
    private function applySorting(Collection $products, ?string $sort): Collection
    {
        return match ($sort) {
            'price_low' => $products->sortBy(fn(Product $p) => (float) $p->getFinalPriceAttribute()),
            'price_high' => $products->sortByDesc(fn(Product $p) => (float) $p->getFinalPriceAttribute()),
            'popularity' => $this->sortByPopularity($products),
            'newest' => $products->sortByDesc('created_at'),
            default => $products->sortByDesc('created_at'),
        };
    }

    /**
     * Sort by popularity = total quantity sold (from order_items).
     * Uses sortByDesc() with a pre-built sales lookup.
     */
    private function sortByPopularity(Collection $products): Collection
    {
        // Build a map: product_id => total_quantity_sold
        $salesMap = OrderItem::selectRaw('product_id, SUM(quantity) as total_sold')
            ->groupBy('product_id')
            ->pluck('total_sold', 'product_id');

        return $products->sortByDesc(
            fn(Product $p) => $salesMap->get($p->id, 0)
        );
    }
}