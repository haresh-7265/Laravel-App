<?php

namespace App\Services;

use App\Exceptions\ProductHasOrdersException;
use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function getAll()
    {
        $role = request()->user()?->isAdmin() ? 'admin' : 'customer';
        $key = "products.{$role}.all";

        return Cache::tags(['products', 'products.list'])->remember($key, now()->addHour(), fn () => Product::active()->with('category')->get());
    }

    public function getHomepageProducts(int $page, array $filters, int $perPage = 10): array
    {
        $role = request()->user()?->isAdmin() ? 'admin' : 'customer';

        return Concurrency::run([
            'featured' => fn () => $this->getFeaturedProducts($role),
            'newArrivals' => fn () => $this->getNewArrivalProducts($role),
            'onSale' => fn () => $this->getOnSaleProducts($role),
            'products' => fn () => $this->getFilteredProducts($page, $filters, $perPage, $role),
        ]);
    }

    // GET paginated products
    public function getFilteredProducts(int $page, array $filters, int $perPage, string $role)
    {
        ksort($filters);

        $hash = md5(json_encode([
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
            'role' => auth()->user()?->role ?? 'customer',
        ]));
        $cacheKey = "products.{$role}.{$hash}";

        return Cache::tags(['products', 'products.list'])->remember($cacheKey, now()->addHour(), function () use ($perPage, $filters, $role) {
            $products = $this->apply($filters, $role)
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
                $data['image'] = $this->uploadImage($image, $data['slug']);
            }

            $product = tap(Product::create($data), function ($product) {
                Log::channel('product')->info('Product created', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category_id' => $product->category_id,
                    'has_image' => ! is_null($product->image),
                    'created_by' => auth()->id() ?? 'system',
                ]);
            });

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
                $data['image'] = $this->uploadImage($image, $data['slug'] ?? $product->slug);
            }

            return tap($product, function ($product) use ($data) {
                $product->update($data);

                Log::channel('product')->info('Product updated', [
                    'product_id' => $product->id,
                    'changes' => $product->getChanges(),
                    'updated_by' => auth()->id() ?? 'system',
                ]);
            })->refresh();

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

            if ($product->orderItems()->exists()) {
                throw new ProductHasOrdersException;
            }
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
                ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
                ->when($filters['price'] ?? null, fn ($q, $v) => $q->where('price', $v))
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

    // Upload image
    private function uploadImage(UploadedFile $image, $slug): string
    {
        try {
            $path = $image->storeAs('products', $slug.'.'.$image->extension(), 'public');

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
    public function apply(array $filters, string $role = 'customer'): Builder
    {
        return DB::table('products')
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.description',
                'products.price',
                'products.discount_price',
                'products.stock',
                'products.image',
                'categories.id as category_id',
                'categories.name as category_name',
            ])
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->when($role !== 'admin', function ($q) {
                $q->where('products.is_active', true);
            })
            ->when(! empty($filters['min_price']), function ($q) use ($filters) {
                $q->where(DB::raw('COALESCE(products.discount_price, products.price)'), '>=', (float) $filters['min_price']);
            })
            ->when(! empty($filters['max_price']), function ($q) use ($filters) {
                $q->where(DB::raw('COALESCE(products.discount_price, products.price)'), '<=', (float) $filters['max_price']);
            })
            ->when(! empty($filters['categories']) && is_array($filters['categories']), function ($q) use ($filters) {
                $q->whereIn('products.category_id', array_map('intval', $filters['categories']));
            })
            ->when(! empty($filters['in_stock']), fn ($q) => $q->where('products.stock', '>', 0))
            ->when(! empty($filters['on_sale']), function ($q) {
                $q->whereIn('products.id', function ($sub) {
                    $sub->select('id')
                        ->from('products')
                        ->whereNotNull('discount_price')
                        ->where('discount_price', '>', 0)
                        ->whereRaw('discount_price < price');
                })
                    ->addSelect(DB::raw('ROUND((1 - products.discount_price / products.price) * 100) as discount_percent'));
            })
            ->when($filters['sort'] ?? null, function ($q, $sort) {
                match ($sort) {
                    'price_asc' => $q->orderBy(DB::raw('COALESCE(products.discount_price, products.price)'), 'asc'),
                    'price_desc' => $q->orderBy(DB::raw('COALESCE(products.discount_price, products.price)'), 'desc'),
                    'popularity' => $q->orderByDesc(
                        DB::raw('(SELECT COUNT(*) FROM order_items WHERE order_items.product_id = products.id)')
                    ),
                    'newest' => $q->orderBy('products.created_at', 'desc'),
                    default => $q->orderBy('products.created_at', 'desc'),
                };
            }, fn ($q) => $q->orderBy('products.created_at', 'desc'));
    }

    // get featuren products
    public function getFeaturedProducts(string $role = 'customer', int $limit = 8)
    {
        return Cache::tags(['products', 'products.list'])
            ->remember("products.{$role}.featured",
                now()->addHour(),
                fn () => Product::active()
                    ->with('category')
                    ->whereJsonContains('tags', 'featured')
                    ->limit($limit)
                    ->get()
            );
    }

    // get new arrivals products
    public function getNewArrivalProducts(string $role = 'customer', int $limit = 8)
    {
        return Cache::tags(['products', 'products.list'])
            ->remember("products.{$role}.new",
                now()->addHour(),
                fn () => Product::active()
                    ->with('category')
                    ->latest()
                    ->limit($limit)
                    ->get()
            );
    }

    // get on sale products
    public function getOnSaleProducts(string $role = 'customer', int $limit = 8)
    {
        return Cache::tags(['products', 'products.list'])
            ->remember("products.{$role}.onsale",
                now()->addHour(),
                fn () => Product::active()
                    ->with('category')
                    ->whereNotNull('discount_price')
                    ->where('discount_price', '>', 0)
                    ->whereColumn('discount_price', '<', 'price')
                    ->limit($limit)
                    ->get()
            );
    }
}
