<?php

namespace App\Observers;

use App\Events\Order\{OrderDelivered, OrderPaid, OrderPlaced, OrderShipped, OrderStatusUpdated};
use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        OrderPlaced::dispatch($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if($order->wasChanged('status')){
            OrderStatusUpdated::dispatch($order->id, $order->status);

            if($order->status == 'shipped'){
                OrderShipped::dispatch($order);
            }else if($order->status == 'delivered'){
                OrderDelivered::dispatch($order);
            }
        }

        if($order->wasChanged('payment_status') && $order->payment_status == 'paid'){
            OrderPaid::dispatch($order);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
