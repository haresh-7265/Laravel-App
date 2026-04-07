<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $customerName;
    public float  $orderTotal;
    public int    $itemsCount;
    public string    $orderNumber;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $customerName,
        float  $orderTotal,
        int    $itemsCount,
        string    $orderNumber
    ) {
        $this->customerName = $customerName;
        $this->orderTotal   = $orderTotal;
        $this->itemsCount   = $itemsCount;
        $this->orderNumber  = $orderNumber;
    }

    /**
     * Get the channels the event should broadcast on.
     * Uses a private channel so only authenticated admins receive it.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('admin.orders');
    }

    /**
     * The event name as seen by the JS client.
     * Defaults to the class name; explicit declaration makes it clear.
     */
    public function broadcastAs(): string
    {
        return 'OrderPlaced';
    }

    /**
     * Data sent to the client. Only expose what the admin dashboard needs.
     */
    public function broadcastWith(): array
    {
        return [
            'order_number'  => $this->orderNumber,
            'customer_name' => $this->customerName,
            'order_total'   => number_format($this->orderTotal, 2),
            'items_count'   => $this->itemsCount,
            'placed_at'     => now()->toDateTimeString(),
        ];
    }
}