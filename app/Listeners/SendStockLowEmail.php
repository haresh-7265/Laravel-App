<?php

namespace App\Listeners;

use App\Events\Product\ProductStockLow;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendStockLowEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProductStockLow $event): void
    {
        \Mail::to(config('admin.email'))
            ->send(new StockLowMail($event->product));
    }
}
