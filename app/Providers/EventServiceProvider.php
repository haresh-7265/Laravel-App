<?php

namespace App\Providers;

use App\Events\Order\{OrderDelivered, OrderPaid, OrderPlaced, OrderShipped};
use App\Listeners\{CustomerActionSubscriber, LogEvent, NotifyAdmin, UpdateInventory, SendOrderEmail};
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class      => [NotifyAdmin::class, LogEvent::class, SendOrderEmail::class],
        OrderPaid::class        => [NotifyAdmin::class, UpdateInventory::class, LogEvent::class, SendOrderEmail::class],
        OrderShipped::class     => [NotifyAdmin::class, LogEvent::class, SendOrderEmail::class],
        OrderDelivered::class   => [NotifyAdmin::class, LogEvent::class, SendOrderEmail::class],
    ];

    protected $subscribe = [
        CustomerActionSubscriber::class,
    ];
}