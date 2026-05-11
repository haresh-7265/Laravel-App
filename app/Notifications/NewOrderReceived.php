<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\WebhookChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly Order $order)
    {
        //
    }

    /**
     * Delivery channels: email + database + real-time broadcast.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast', WebhookChannel::class];
    }

    /**
     * Admin email with order summary.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🛒 New Order Received — #'.$this->order->order_number)
            ->greeting('Hello '.($notifiable->name ?? 'Admin').',')
            ->line('A new order has been placed and is awaiting processing.')
            ->line('**Order:** #'.$this->order->order_number)
            ->line('**Customer:** '.$this->order->shipping_name)
            ->line('**Total:** '.format_price($this->order->total))
            ->line('**Items:** '.$this->order->items()->sum('quantity'))
            ->action('View Order', route('admin.orders.show', $this->order))
            ->line('Please process this order at your earliest convenience.');
    }

    /**
     * Data stored in the notifications table — consumed by the bell dropdown and index page.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->shipping_name,
            'total' => number_format($this->order->total, 2),
            'items_count' => $this->order->items()->sum('quantity'),
            'message' => 'New order #'.$this->order->order_number.' placed by '.$this->order->shipping_name,
            'icon' => 'order',
        ];
    }

    /**
     * Real-time broadcast to the admin dashboard.
     * Integrates with the admin.orders private channel from Module 27.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'success',
            'title' => 'New Order #'.$this->order->order_number,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->shipping_name,
            'order_total' => format_price($this->order->total),
            'items_count' => $this->order->items()->sum('quantity'),
            'message' => 'New order #'.$this->order->order_number.' placed by '.$this->order->shipping_name,
            'placed_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Payload posted to the external webhook.
     *
     * @return array<string, mixed>
     */
    public function toWebhook(object $notifiable): array
    {
        return [
            'event'         => 'order.placed',
            'order_number'  => $this->order->order_number,
            'customer_name' => $this->order->shipping_name,
            'customer_email'=> $this->order->shipping_email,
            'total'         => $this->order->total,
            'items_count'   => $this->order->items()->sum('quantity'),
            'placed_at'     => $this->order->created_at->toIso8601String(),
            'admin_url'     => route('admin.orders.show', $this->order),
        ];
    }
}
