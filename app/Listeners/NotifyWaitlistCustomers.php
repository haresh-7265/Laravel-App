<?php

namespace App\Listeners;

use App\Events\Product\ProductRestocked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Broadcast;

class NotifyWaitlistCustomers implements ShouldQueue
{

    public function handle(ProductRestocked $event): void
    {
        $product = $event->product;

        foreach ($product->waitlistUsers as $user) {
            //TODO implement notification for users
        }

        $product->waitlistUsers()->detach();
    }
}
