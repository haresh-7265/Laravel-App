<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogEvent implements ShouldQueue
{
    public function handle(object $event): void
    {
        Log::channel('order')->info(class_basename($event), [
            'order_number' => $event->order->order_number,
            'status'   => $event->order->status,
            'at'       => now(),
        ]);
    }
}