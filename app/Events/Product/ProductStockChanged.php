<?php

namespace App\Events\Product;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStockChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public int $productId;
    public int $stock;

    public function __construct(int $productId, int $stock)
    {
        $this->productId = $productId;
        $this->stock = $stock;
    }

    public function broadcastOn()
    {
        return new Channel('product.' . $this->productId); // public channel
    }

    public function broadcastAs()
    {
        return 'ProductStockChanged';
    }

    public function broadcastWith()
    {
        return [
            'product_id' => $this->productId,
            'stock' => $this->stock,
        ];
    }
}