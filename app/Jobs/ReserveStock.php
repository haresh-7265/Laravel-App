<?php

namespace App\Jobs;

use App\Exceptions\ProductOutOfStockException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReserveStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Verify stock availability and reserve (decrement) for each order item.
     *
     * Runs inside a DB transaction with pessimistic locking to prevent
     * race conditions. Throws ProductOutOfStockException if any item
     * has insufficient stock — this halts the chain and triggers catch().
     */
    public function handle(): void
    {
        $this->order->load('items');

        DB::transaction(function () {
            foreach ($this->order->items as $item) {
                // Lock the product row to prevent concurrent stock changes
                $product = Product::lockForUpdate()->find($item->product_id);

                if (! $product) {
                    throw new ProductOutOfStockException(
                        productName: $item->product_name,
                        productId: $item->product_id,
                        requestedQuantity: $item->quantity,
                        availableQuantity: 0,
                        message: "Product '{$item->product_name}' (ID: {$item->product_id}) no longer exists."
                    );
                }

                // Check if sufficient stock is available
                if ($product->stock < $item->quantity) {
                    throw new ProductOutOfStockException(
                        productName: $product->name,
                        productId: $product->id,
                        requestedQuantity: $item->quantity,
                        availableQuantity: $product->stock,
                    );
                }

                // Reserve stock by decrementing
                $product->decrement('stock', $item->quantity);

                Log::channel('order')->info("ReserveStock: Reserved {$item->quantity} × {$product->name} (remaining: {$product->fresh()->stock}).");
            }
        });

        $this->order->update(['status' => 'processing']);

        Log::channel('order')->info("ReserveStock: All stock reserved for order #{$this->order->order_number}. Status → processing.");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $e): void
    {
        ReleaseStock::dispatch($this->order);
        Log::channel('order')->error("ReserveStock FAILED for order #{$this->order->order_number}: {$e->getMessage()}");
    }
}
