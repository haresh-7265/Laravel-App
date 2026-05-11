<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\NewOrderReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;
use App\Events\Order\{OrderPlaced, OrderPaid, OrderDelivered, OrderShipped};
use Throwable;

class NotifyAdmin implements ShouldQueue
{
    public function handle(object $event): void
    {
        // ── OrderPlaced → unified Notification (mail + database + broadcast) ──
        if ($event instanceof OrderPlaced) {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new NewOrderReceived($event->order));
            return;
        }

        // ── Other order events → manual broadcast only (unchanged) ───────────
        $eventName = match (true) {
            $event instanceof OrderShipped   => 'order.shipped',
            $event instanceof OrderPaid      => 'order.paid',
            $event instanceof OrderDelivered => 'order.delivered',
            default => null
        };

        if ($eventName) {
            $order = $event->order;
            $data = [
                'message'       => str($eventName)->replace('.', ' ')->title()->value(),
                'order_number'  => $order->order_number,
                'customer_name' => $order->shipping_name,
                'order_total'   => number_format($order->total, 2),
                'items_count'   => $order->items()->sum('quantity'),
                'time'          => now()->toDateTimeString()
            ];
            Broadcast::private('admin.orders')
                ->as($eventName)
                ->with($data)
                ->sendNow();
        }
    }

    public function failed($event, Throwable $exception): void
    {
        \Log::error('Admin notification failed', [
            'event'    => get_class($event),
            'order_id' => $event->order->order_number,
            'error'    => $exception->getMessage(),
        ]);
    }
}