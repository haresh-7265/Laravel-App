<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Broadcast;
use app\Events\Order\{OrderPlaced, OrderPaid, OrderDelivered, OrderShipped};
use Throwable;

class NotifyAdmin implements ShouldQueue
{
    public function handle(object $event): void
    {
        $eventName = match (true) {
            $event instanceof OrderPlaced => 'order.placed',
            $event instanceof OrderShipped => 'order.shipped',
            $event instanceof OrderPaid => 'order.paid',
            $event instanceof OrderDelivered => 'order.delivered',
            default => null
        };

        if ($eventName) {
            $order = $event->order;
            $data = [
                'message' => str($eventName)->replace('.', ' ')->title()->value(),
                'order_number' => $order->order_number,
                'customer_name' => $order->shipping_name,
                'order_total' => number_format($order->total, 2),
                'items_count' => $order->items()->sum('quantity'),
                'time' => now()->toDateTimeString()
            ];
            Broadcast::private('admin.orders')
                ->as($eventName)
                ->with($data)
                ->send();
        }
    }

    public function failed($event, Throwable $exception): void
    {
        \Log::error('Admin notification failed', [
            'event' => get_class($event),
            'order_id' => $event->order->order_number,
            'error' => $exception->getMessage(),
        ]);
    }
}