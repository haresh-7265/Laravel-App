<?php

namespace App\Services;

use App\Exceptions\ProductHasOrdersException;
use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function getAll(User|Admin|null $user = null)
    {
        [$key, $query] = match (true) {
            // guest and default logged-in user see same data — share one cache key
            is_null($user) => ['products.active', fn () => Product::active()->with('category')->get()],
            $user->can('manage_products') => ['products.all',    fn () => Product::with('category')->get()],
            default => ['products.active', fn () => Product::active()->with('category')->get()],
        };

        return Cache::tags(['products', 'products.list'])->remember($key, now()->addHour(), $query);
    }

    public function getHomepageProducts(
        array $filters,
        User|Admin|null $user = null,
        ?string $cursor = null,
        int $perPage = 20): array
    {
        return Concurrency::run([
            'featured' => fn () => $this->getFeaturedProducts($user),
            'newArrivals' => fn () => $this->getNewArrivalProducts($user),
            'onSale' => fn () => $this->getOnSaleProducts($user),
            'products' => fn () => $this->getFilteredProducts($filters, $user, $cursor, $perPage),
        ]);
    }

    // GET paginated products
    public function getFilteredProducts(
        array $filters,
        User|Admin|null $user = null,
        ?string $cursor = null,
        int $perPage = 20)
    {
        ksort($filters);
        // dump($user);
        $isManager = ! is_null($user) && $user->can('manage_products');
        // dd($isManager);
        $filterHash = md5(json_encode($filters));

        $scop = $isManager ? 'all' : 'active';
        $Key = "products.{$scop}.{$filterHash}.{$perPage}.{$cursor}";

        return Cache::tags(['products', 'products.list'])->remember($Key, now()->addHour(), function () use ($perPage, $filters, $isManager) {

            $q = $filters['q'] ?? null;
            if ($q) {
                $products = Product::search($q)
                    ->query(fn ($b) => $this->applyFilters($filters, $isManager, $b))
                    ->paginate($perPage, 'cursor')
                    ->withQueryString();
            } else {
                $products = $this->applyFilters(
                    filters: $filters,
                    isManager: $isManager)
                    ->paginate($perPage, 'cursor')
                    ->withQueryString();
            }

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
                    'created_by' => current_user()?->id ?? 'system',
                ]);
            });

            return $product;

        } catch (QueryException $e) {
            // DB error Log
            Log::channel('product')->error('Failed to create product', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'created_by' => current_user()?->id ?? 'system',
            ]);

            throw $e;   // re-throw so controller/handler can respond
        } catch (\Exception $e) {
            // Log unexpected errors
            Log::channel('product')->error('Unexpected error creating product', [
                'error' => $e->getMessage(),
                'created_by' => current_user()?->id,
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
                    'updated_by_id' => current_user()?->id ?? null,
                    'updated_by_type' => current_user()?->getMorphClass() ?? null,
                ]);
            });

        } catch (QueryException $e) {
            Log::channel('product')->error('Failed to update product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'updated_by_id' => current_user()?->id ?? null,
                'updated_by_type' => current_user()?->getMorphClass() ?? null,
            ]);

            throw $e;
        } catch (\Exception $e) {
            Log::channel('product')->error('Unexpected error updating product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'updated_by_id' => current_user()?->id ?? null,
                'updated_by_type' => current_user()?->getMorphClass() ?? null,
            ]);

            throw $e;
        }
    }

    // DELETE
    public function delete(Product $product): bool
    {
        try {

            // if ($product->orderItems()->exists()) {
            //     throw new ProductHasOrdersException;
            // }
            $product->delete();

            return true;

        } catch (QueryException $e) {
            Log::channel('product')->error('Failed to delete product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'updated_by_id' => current_user()?->id ?? null,
                'updated_by_type' => current_user()?->getMorphClass() ?? null,
            ]);

            throw $e;
        } catch (\Exception $e) {
            Log::channel('product')->error('Unexpected error deleting product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'updated_by_id' => current_user()?->id ?? null,
                'updated_by_type' => current_user()?->getMorphClass() ?? null,
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
                    'user_id' => current_user()?->id,
                ]);
            }

            Log::channel('product')->debug('Product search executed', [
                'filters' => $filters,
                'result_count' => $results->count(),
                'user_id' => current_user()?->id,
            ]);

            return $results->toArray();

        } catch (QueryException $e) {
            Log::channel('product')->error('Product search query failed', [
                'filters' => $filters,
                'error' => $e->getMessage(),
                'user_id' => current_user()?->id,
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
                'user_id' => current_user()?->id,
            ]);

            throw $e;
        }
    }

    // filters
    public function applyFilters(
        array $filters,
        bool $isManager = false,
        ?Builder $builder = null): Builder
    {
        return ($builder ?? Product::query())
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.description',
                'products.price',
                'products.discount_price',
                'products.stock',
                'products.image',
                'products.created_at',
                'categories.id as category_id',
                'categories.name as category_name',
                DB::raw('COALESCE(products.discount_price, products.price) as effective_price'),
            ])
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->when(! $isManager, function ($q) {
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
            ->when($filters['sort'] ?? 'newest', function ($q, $sort) {
                if ($sort == 'popularity') {
                    $q->addSelect(DB::raw('(SELECT COUNT(*) FROM order_items WHERE order_items.product_id = products.id) as order_count'));
                }
                match ($sort) {
                    'price_low' => $q->orderBy('effective_price', 'asc')->orderBy('products.id'),
                    'price_high' => $q->orderBy('effective_price', 'desc')->orderBy('products.id'),
                    'popularity' => $q->orderBy('order_count', 'desc')->orderBy('products.id'),
                    'newest' => $q->orderBy('products.created_at', 'desc')->orderBy('products.id'),
                    default => $q->orderBy('products.created_at', 'desc')->orderBy('products.id'),
                };
            });
    }

    // get featuren products
    public function getFeaturedProducts(User|Admin|null $user = null, int $limit = 8): Collection
    {
        $isManager = ! is_null($user) && $user->can('manage_products');

        $key = $isManager
            ? "products.all.featured.{$limit}"
            : "products.active.featured.{$limit}";

        return Cache::tags(['products', 'products.list'])
            ->remember($key,
                now()->addHour(),
                fn () => Product::when(! $isManager, fn ($q) => $q->active())
                    ->with('category')
                    ->whereJsonContains('tags', 'featured')
                    ->limit($limit)
                    ->get()
            );
    }

    // get new arrivals products
    public function getNewArrivalProducts(User|Admin|null $user = null, int $limit = 8): Collection
    {
        $isManager = ! is_null($user) && $user->can('manage_products');

        $key = $isManager
            ? "products.all.new.{$limit}"
            : "products.active.new.{$limit}";

        return Cache::tags(['products', 'products.new'])
            ->remember($key, now()->addHour(), fn () => Product::when(! $isManager, fn ($q) => $q->active())
                ->with('category')
                ->latest()
                ->limit($limit)
                ->get()
            );
    }

    // get on sale products
    public function getOnSaleProducts(User|Admin|null $user = null, int $limit = 8): Collection
    {
        $isManager = ! is_null($user) && $user->can('manage_products');

        $key = $isManager
            ? "products.all.onsale.{$limit}"
            : "products.active.onsale.{$limit}";

        return Cache::tags(['products', 'products.onsale'])
            ->remember($key, now()->addHour(), fn () => Product::when(! $isManager, fn ($q) => $q->active())
                ->with('category')
                ->whereNotNull('discount_price')
                ->where('discount_price', '>', 0)
                ->whereColumn('discount_price', '<', 'price')
                ->limit($limit)
                ->get()
            );
    }

    // get trashed products
    public function getTrashedProducts(int $page = 1, int $perPage = 10)
    {

        return Cache::tags(['products', 'products.list'])
            ->remember("products.trashed.{$page}.{$perPage}",
                now()->addHour(),
                fn () => Product::onlyTrashed()
                    ->with('category')
                    ->latest('deleted_at')
                    ->paginate($perPage)
            );
    }

    // restore product
    public function restoreProduct(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return $product;
    }

    // force delete product
    public function forceDeleteProduct(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->forceDelete();

        return $product;
    }

    public function loadShowRelations(Product $product, User|Admin|null $user): Product
    {
        $product->load([
            'reviews' => fn ($q) => $q->when(
                $user instanceof User,
                fn ($q) => $q->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$user->id])
            )->latest(),
            'reviews.user',
            'category',
        ]);

        return $product;
    }
}
