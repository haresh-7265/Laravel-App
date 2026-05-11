<?php

namespace App\Providers;

use App\Events\Order\{OrderDelivered, OrderPaid, OrderPlaced, OrderShipped};
use App\Events\Product\ProductStockLow;
use App\Listeners\{CustomerActionSubscriber, LogEvent, NotifyAdmin, SendStockLowEmail, UpdateInventory, SendOrderEmail};
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class      => [NotifyAdmin::class, LogEvent::class, SendOrderEmail::class],
        OrderPaid::class        => [NotifyAdmin::class, UpdateInventory::class, LogEvent::class, SendOrderEmail::class],
        OrderShipped::class     => [NotifyAdmin::class, LogEvent::class, SendOrderEmail::class],
        OrderDelivered::class   => [NotifyAdmin::class, LogEvent::class, SendOrderEmail::class],
        ProductStockLow::class  => [SendStockLowEmail::class],
    ];

    protected $subscribe = [
        CustomerActionSubscriber::class,
    ];
}