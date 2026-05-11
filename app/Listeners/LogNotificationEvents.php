<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

/**
 * Listens for NotificationSent and NotificationFailed events
 * to provide admin-level audit logging of all notification activity.
 */
class LogNotificationEvents implements ShouldQueue
{
    /**
     * Handle NotificationSent — log every successful delivery.
     */
    public function handleSent(NotificationSent $event): void
    {
        Log::channel('daily')->info('Notification sent', [
            'notification' => get_class($event->notification),
            'channel' => $event->channel,
            'notifiable' => get_class($event->notifiable).':'.$event->notifiable->getKey(),
        ]);
    }

    /**
     * Handle NotificationFailed — log every failed delivery.
     */
    public function handleFailed(NotificationFailed $event): void
    {
        Log::channel('daily')->error('Notification failed', [
            'notification' => get_class($event->notification),
            'channel' => $event->channel,
            'notifiable' => get_class($event->notifiable).':'.$event->notifiable->getKey(),
            'data' => $event->data,
        ]);
    }

}
