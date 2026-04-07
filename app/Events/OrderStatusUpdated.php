<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public int $orderId;
    public string $status;

    public function __construct(int $orderId, string $status)
    {
        $this->orderId = $orderId;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('order.' . $this->orderId);
    }

    public function broadcastAs()
    {
        return 'OrderStatusUpdated';
    }

    public function broadcastWith()
    {
        return [
            'order_id' => $this->orderId,
            'status'   => $this->status,
        ];
    }
}