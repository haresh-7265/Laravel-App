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

class ProductObserver
{
    private const LOW_STOCK_THRESHOLD = 10;
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->clearProductCaches($product);
        // Log::channel('product')->info('Product created', [
        //     'product_id' => $product->id,
        //     'product_name' => $product->name,
        //     'category_id' => $product->category_id,
        //     'has_image' => !is_null($product->image),
        //     'created_by' => auth()->id() ?? 'system',
        // ]);
    }

    public function updating(Product $product)
    {
        $slugChanged = $product->isDirty('slug');
        $imageChanged = $product->isDirty('image');

        // Case 1 — only slug changed, no new image
        if ($slugChanged && !$imageChanged && $product->image) {
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

        // delete image
        $path = $product->image;
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        Log::channel('product')->warning('Product deleted', [
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

    public function clearProductCaches(Product $product): void
    {
        app(CacheService::class)->forgetProduct($product->slug);
    }
}
