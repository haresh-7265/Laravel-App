<?php

namespace App\Observers;

use App\Events\Product\ProductOutOfStock;
use App\Events\Product\ProductRestocked;
use App\Events\Product\ProductStockChanged;
use App\Events\Product\ProductStockLow;
use App\Models\Product;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductObserver
{
    public bool $afterCommit = true; // observer methods runs after commit

    private const LOW_STOCK_THRESHOLD = 10;

    /**
     * Handle the Product "creating" event.
     * Populates the created_by and updated_by audit columns.
     */
    public function creating(Product $product): void
    {
        if(current_user()){
            $product->created_by_id = $product->created_by_id ?? current_user()->id;
            $product->created_by_type = $product->created_by_type ?? current_user()->getMorphClass();
        }

    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->clearProductCaches($product);
    }

    public function updating(Product $product)
    {
        // Populate updated_by audit column
        current_user()
        ? $product->updatedBy()->associate(current_user())
        : $product->updatedBy()->dissociate();

        // make active product when product is restocked
        $oldStock = $product->getOriginal('stock');
        $newStock = $product->stock;

        if ($oldStock == 0 && $newStock > 0) {
            $product->is_active = true;
        }
        $slugChanged = $product->isDirty('slug');
        $imageChanged = $product->isDirty('image');

        // Case 1 — only slug changed, no new image
        if ($slugChanged && ! $imageChanged && $product->image) {
            $oldPath = $product->image;
            $newPath = str_replace(
                $product->getOriginal('slug'),
                $product->slug,
                $oldPath
            );
            Storage::disk('public')->move($oldPath, $newPath);
            $product->image = $newPath;
        }

        // Case 2 — image changed (with or without slug)
        if ($imageChanged) {
            $oldImage = $product->getOriginal('image');
            if ($oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        if (! $product->wasChanged()) {
            return;
        }
        $this->clearProductCaches($product);

        if ($product->wasChanged('stock')) {
            $originalStock = $product->getOriginal('stock');
            $currentStock = $product->stock;

            ProductStockChanged::dispatch($product->id, $product->stock);

            if ($currentStock == 0) {
                ProductOutOfStock::dispatch($product);
            }

            if ($originalStock == 0 && $currentStock > 0) {
                ProductRestocked::dispatch($product);
            }

            if ($currentStock < self::LOW_STOCK_THRESHOLD) {
                ProductStockLow::dispatch($product);
            }
        }

        // Log::channel('product')->info('Product updated', [
        //     'product_id' => $product->id,
        //     'changes' => $product->getChanges(),
        //     'updated_by' => auth()->id() ?? 'system',
        // ]);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->clearProductCaches($product);

        $product->unsearchable();

        Log::channel('product')->warning('Product deleted', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'deleted_by' => current_user()?->id ?? 'system',
            'data' => [
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
            ],
        ]);
    }

    public function restored(Product $product): void
    {
        if ($product->shouldBeSearchable()) {  // soft-delete restore → re-check shouldBeSearchable
            $product->load('category')->searchable();
        }
    }

    public function forceDeleted(Product $product): void
    {
        $this->clearProductCaches($product);

        $product->unsearchable();

        // delete image
        $path = $product->image;
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        Log::channel('product')->warning('Product forceDeleted', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'deleted_by' => auth()->id() ?? 'system',
            'data' => [
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
            ],
        ]);
    }

    public function saving(Product $product): void
    {
        if (empty($product->slug)) {
            $product->slug = str($product->name)->slug();
        }
    }
    public function saved(Product $product): void  // covers created + updated both
    {
        if ($product->shouldBeSearchable()) {
            $product->load('category')->searchable();
        } else {
            $product->unsearchable(); // unpublished → remove from index
        }
    }

    public function clearProductCaches(Product $product): void
    {
        app(CacheService::class)->forgetProduct($product->slug);
    }
}
