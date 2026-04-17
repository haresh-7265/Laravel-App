<?php

namespace App\Providers;

use App\Events\Order\{OrderDelivered, OrderPaid, OrderPlaced, OrderShipped};
use App\Listeners\{CustomerActionSubscriber, LogEvent, NotifyAdmin, UpdateInventory, SendOrderEmail};
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [SendOrderEmail::class, NotifyAdmin::class, LogEvent::class],
        OrderPaid::class => [SendOrderEmail::class, NotifyAdmin::class, UpdateInventory::class, LogEvent::class],
        OrderShipped::class => [SendOrderEmail::class, NotifyAdmin::class, LogEvent::class],
        OrderDelivered::class => [SendOrderEmail::class, NotifyAdmin::class, LogEvent::class],
    ];

    protected $subscribe = [
        CustomerActionSubscriber::class,
    ];
}