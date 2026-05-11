<?php

namespace App\Listeners;

use App\Events\Order\OrderDelivered;
use App\Events\Order\OrderPaid;
use App\Events\Order\OrderPlaced;
use App\Events\Order\OrderShipped;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class NotifyAdmin implements ShouldQueue
{
    public function handle(object $event): void
    {
        // ── OrderPlaced → unified Notification (mail + database + broadcast) ──
        if ($event instanceof OrderPlaced) {
            $key = 'customer-notifications:'.$event->order->user->id;

            $executed = RateLimiter::attempt(
                $key,
                5,       // max 5 notifications
                function () use ($event) {
                    $admins = User::where('role', 'admin')->get();
                    Notification::send($admins, new NewOrderReceived($event->order));
                },
                60       // per 60 seconds
            );

            if (! $executed) {
                Log::channel('customer')->warning('Rate limit hit — customer notification suppressed', [
                    'user_id' => $event->order->user->id,
                    'order_number' => $event->order->order_number,
                    'notification' => 'NewOrderReceived',
                ]);
            }

            return;
        }

        // ── Other order events → manual broadcast only (unchanged) ───────────
        $eventName = match (true) {
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
                'time' => now()->toDateTimeString(),
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
            'event' => get_class($event),
            'order_id' => $event->order->order_number,
            'error' => $exception->getMessage(),
        ]);
    }
}
