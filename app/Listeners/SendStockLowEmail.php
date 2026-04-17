<?php

namespace App\Listeners;

use App\Events\Product\ProductStockLow;
use App\Mail\StockLowMail;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendStockLowEmail implements ShouldQueue
{

    /**
     * Handle the event.
     */
    public function handle(ProductStockLow $event): void
    {
        \Mail::to(config('admin.email'))
            ->send(new StockLowMail($event->product));
    }
}
