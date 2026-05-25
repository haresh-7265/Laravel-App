<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReleaseStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Release (re-increment) stock for each item in a cancelled/failed order.
     *
     * This is the reverse of ReserveStock — it restores inventory when an
     * order is cancelled, refunded, or when the checkout chain fails after
     * stock was already reserved.
     */
    public function handle(): void
    {
        $this->order->load('items');

        DB::transaction(function () {
            foreach ($this->order->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);

                if (! $product) {
                    Log::channel('order')->warning("ReleaseStock: Product #{$item->product_id} ({$item->product_name}) not found — skipping.");

                    continue;
                }

                $product->increment('stock', $item->quantity);

                Log::channel('order')->info("ReleaseStock: Released {$item->quantity} × {$product->name} (now: {$product->fresh()->stock}).");
            }
        });

        Log::channel('order')->info("ReleaseStock: All stock released for order #{$this->order->order_number}.");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $e): void
    {
        Log::channel('order')->error("ReleaseStock FAILED for order #{$this->order->order_number}: {$e->getMessage()}");
    }
}
