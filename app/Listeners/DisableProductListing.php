<?php

namespace App\Listeners;

use App\Events\Product\ProductOutOfStock;
use Illuminate\Contracts\Queue\ShouldQueue;

class DisableProductListing implements ShouldQueue
{

    public function handle(ProductOutOfStock $event)
    {
        $product = $event->product;
        $product->is_active = false;
        $product->save();
    }
}
