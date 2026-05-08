<?php

namespace App\Listeners;

use App\Events\Product\ProductStockLow;
use App\Mail\LowStockAlert;
use App\Mail\StockLowMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendStockLowEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ProductStockLow $event): void
    {
        Cache::remember(
            'low_stock_alert_' . $event->product->id,
            now()->addHour(),
            function () use ($event) {
                Mail::to(config('mail.admin.address'))
                    ->cc(config('mail.admin.warehouse'))
                    ->bcc(config('mail.admin.archive'))
                    ->send(new LowStockAlert($event->product));

                return true;
            }
        );
    }
}
