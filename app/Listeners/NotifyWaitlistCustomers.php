<?php

namespace App\Listeners;

use App\Events\Product\ProductRestocked;
use App\Notifications\ProductRestockedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyWaitlistCustomers implements ShouldQueue
{
    public function handle(ProductRestocked $event): void
    {
        $product = $event->product;

        foreach ($product->waitlistUsers as $user) {
            $user->notify(new ProductRestockedNotification($product));
        }

        $product->waitlistUsers()->detach();
    }
}
