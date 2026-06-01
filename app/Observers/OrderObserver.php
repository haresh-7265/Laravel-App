<?php

namespace App\Observers;

use App\Events\Order\{OrderDelivered, OrderPaid, OrderShipped, OrderStatusUpdated};
use App\Models\Order;
use App\Services\CacheService;

class OrderObserver
{
    public bool $afterCommit = true;  // observer methods runs after commit

    /**
     * Handle the Order "creating" event.
     * Populates the created_by and updated_by audit columns.
     */
    public function creating(Order $order): void
    {
        $userId = current_user()?->id;
        $order->created_by = $order->created_by ?? $userId;
    }

    /**
     * Handle the Order "updating" event.
     * Populates the updated_by audit column.
     */
    public function updating(Order $order): void
    {
        current_user()
        ? $order->updatedBy()->associate(current_user())
        : $order->updatedBy()->dissociate();
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->clearOrderCaches();
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if(!$order->wasChanged()){
            return;
        }

        $this->clearOrderCaches();
        
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

    public function clearOrderCaches(): void
    {
        app(CacheService::class)->forgetOrders();
    }
}
