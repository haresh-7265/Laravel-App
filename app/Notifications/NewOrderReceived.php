<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\WebhookChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

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
     * Delivery channels: email + database + real-time broadcast + webhook.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast', WebhookChannel::class];
    }

    /**
     * Route mail and webhook to the "emails" queue, broadcast to "realtime".
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail'                  => 'emails',
            'broadcast'             => 'realtime',
            WebhookChannel::class   => 'emails',
        ];
    }

    /**
     * Admin email with order summary.
     * Locale-aware: Laravel auto-sets app locale via $notifiable->preferredLocale().
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New Order Received') . ' — #' . $this->order->order_number)
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? 'Admin']))
            ->line(__('A new order has been placed and is awaiting processing.'))
            ->line('**' . __('Order') . ':** #' . $this->order->order_number)
            ->line('**' . __('Customer') . ':** ' . $this->order->shipping_name)
            ->line('**' . __('Total') . ':** ' . format_price($this->order->total))
            ->line('**' . __('Items') . ':** ' . $this->order->items()->sum('quantity'))
            ->action(__('View Order'), route('admin.orders.show', $this->order))
            ->line(__('Please process this order at your earliest convenience.'));
    }

    /**
     * Data stored in the notifications table — consumed by the bell dropdown and index page.
     * Uses __() for locale-aware database messages.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'      => $this->order->id,
            'order_number'  => $this->order->order_number,
            'customer_name' => $this->order->shipping_name,
            'total'         => number_format($this->order->total, 2),
            'items_count'   => $this->order->items()->sum('quantity'),
            'message'       => __('New order #:order placed by :customer', [
                'order'    => $this->order->order_number,
                'customer' => $this->order->shipping_name,
            ]),
            'icon'          => 'order',
        ];
    }

    /**
     * Real-time broadcast to the admin dashboard.
     * Integrates with the admin.orders private channel from Module 27.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type'          => 'success',
            'title'         => __('New Order') . ' #' . $this->order->order_number,
            'order_number'  => $this->order->order_number,
            'customer_name' => $this->order->shipping_name,
            'order_total'   => format_price($this->order->total),
            'items_count'   => $this->order->items()->sum('quantity'),
            'message'       => __('New order #:order placed by :customer', [
                'order'    => $this->order->order_number,
                'customer' => $this->order->shipping_name,
            ]),
            'placed_at'     => now()->toDateTimeString(),
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
            'event'          => 'order.placed',
            'order_number'   => $this->order->order_number,
            'customer_name'  => $this->order->shipping_name,
            'customer_email' => $this->order->shipping_email,
            'total'          => $this->order->total,
            'items_count'    => $this->order->items()->sum('quantity'),
            'placed_at'      => $this->order->created_at->toIso8601String(),
            'admin_url'      => route('admin.orders.show', $this->order),
        ];
    }

    /**
     * Handle notification failure — log the error.
     */
    public function failed(Throwable $e): void
    {
        Log::channel('order')->error('NewOrderReceived notification failed', [
            'order_number' => $this->order->order_number,
            'error'        => $e->getMessage(),
            'trace'        => $e->getTraceAsString(),
        ]);
    }
}
