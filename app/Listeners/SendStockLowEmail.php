<?php

namespace App\Listeners;

use App\Events\Product\ProductStockLow;
use App\Models\User;
use App\Notifications\ProductLowStock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class SendStockLowEmail implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * Throttled to one alert per product per hour via cache.
     * Replaces the LowStockAlert Mailable with the ProductLowStock notification,
     * which sends both email and database entries to all admin users.
     */
    public function handle(object $event): void
    {
        /** @var ProductStockLow $event */
        Cache::remember(
            'low_stock_alert_'.$event->product->id,
            now()->addHour(),
            function () use ($event) {
                $admins = User::where('role', 'admin')->get();

                Notification::send(
                    $admins,
                    new ProductLowStock($event->product)
                );

                return true;
            }
        );
    }
}
