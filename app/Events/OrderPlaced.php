<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * Create a new event instance.
     */
    public function __construct(public readonly Order $order)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     * Uses a private channel so only authenticated admins receive it.
     */
    // public function broadcastOn(): Channel
    // {
    //     return new PrivateChannel('admin.orders');
    // }

    /**
     * The event name as seen by the JS client.
     * Defaults to the class name; explicit declaration makes it clear.
     */
    // public function broadcastAs(): string
    // {
    //     return 'OrderPlaced';
    // }

    /**
     * Data sent to the client. Only expose what the admin dashboard needs.
     */
    // public function broadcastWith(): array
    // {
    //     return [
    //         'order_number' => $this->order->order_number,
    //         'customer_name' => $this->order->shipping_name,
    //         'order_total' => number_format($this->order->total, 2),
    //         'items_count' => $this->order->items()->sum('quantity'),
    //         'placed_at' => now()->toDateTimeString(),
    //     ];
    // }
}