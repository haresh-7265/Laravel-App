<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderShipped extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'broadcast', 'database'];
    }

    /**
     * Route mail to the "emails" queue.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
            'broadcast' => 'realtime',
            'database' => 'default',
            ];
    }

    /**
     * Get the mail representation of the notification.
     * Locale-aware: Laravel auto-sets locale via $notifiable->preferredLocale().
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Order Shipped').' - #'.$this->order->order_number)
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? $notifiable->routes['mail'] ?? $this->order->shipping_name]))
            ->line(__('Great news! Your order :order has been shipped.', ['order' => $this->order->order_number]))
            ->line(__('Your tracking number is: :tracking', ['tracking' => $this->order->tracking_number ?? 'N/A']))
            ->action(__('View Order'), route('orders.show', $this->order))
            ->line(__('Thank you for shopping with us!'));
    }

    /**
     * Get the array representation of the notification.
     * Uses __() for locale-aware database messages.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'tracking_number' => $this->order->tracking_number ?? 'N/A',
            'message' => __('Your order :order has been shipped.', ['order' => $this->order->order_number]),
            'icon' => 'bi-truck',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'success',
            'message' => __('Your order :order has been shipped.', ['order' => $this->order->order_number]),
        ]);
    }

    /**
     * Handle notification failure — log the error.
     */
    public function failed(Throwable $e): void
    {
        Log::channel('order')->error('OrderShipped notification failed', [
            'order_number' => $this->order->order_number,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
