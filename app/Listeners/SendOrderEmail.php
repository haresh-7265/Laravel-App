<?php

namespace App\Listeners;

use App\Mail\OrderMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class SendOrderEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(object $event): void
    {
        $order = $event->getOrder();

        $event = match(true) {
            $event instanceof OrderPlaced    => 'placed',
            $event instanceof OrderShipped   => 'shipped',
            $event instanceof OrderPaid      => 'paid',
            $event instanceof OrderDelivered => 'delivered',
            default => null
        };

        if ($event) {
            \Mail::to($order->shipping_email)->send(new OrderMail($order, $event));
        }
    }

    public function failed( $event, Throwable $exception): void
    {
        \Log::error('Order email failed', [
            'event'    => get_class($event),
            'order_id' => $event->order->order_number,
            'error'    => $exception->getMessage(),
        ]);
    }
}
