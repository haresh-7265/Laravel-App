<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\WebhookChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ActionsBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class NewOrderReceived extends BaseNotification
{
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
        return ['mail', 'database', 'broadcast', WebhookChannel::class, 'slack'];
    }

    public function getPayload()
    {
        return $this->order->toArray();
    }

    /**
     * Route mail and webhook to the "emails" queue, broadcast to "realtime".
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
            'broadcast' => 'realtime',
            WebhookChannel::class => 'emails',
            'slack' => 'emails',
        ];
    }

    /**
     * Admin email with order summary.
     * Locale-aware: Laravel auto-sets app locale via $notifiable->preferredLocale().
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New Order Received').' — #'.$this->order->order_number)
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? 'Admin']))
            ->line(__('A new order has been placed and is awaiting processing.'))
            ->line('**'.__('Order').':** #'.$this->order->order_number)
            ->line('**'.__('Customer').':** '.$this->order->shipping_name)
            ->line('**'.__('Total').':** '.format_price($this->order->total))
            ->line('**'.__('Items').':** '.$this->order->items()->sum('quantity'))
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
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->shipping_name,
            'total' => number_format($this->order->total, 2),
            'items_count' => $this->order->items()->sum('quantity'),
            'message' => __('New order #:order placed by :customer', [
                'order' => $this->order->order_number,
                'customer' => $this->order->shipping_name,
            ]),
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
            'title' => __('New Order').' #'.$this->order->order_number,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->shipping_name,
            'order_total' => format_price($this->order->total),
            'items_count' => $this->order->items()->sum('quantity'),
            'message' => __('New order #:order placed by :customer', [
                'order' => $this->order->order_number,
                'customer' => $this->order->shipping_name,
            ]),
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
            'text' => "🛒 New Order: #{$this->order->order_number}",
            'attachments' => [
                [
                    'fields' => [
                        ['title' => 'Customer', 'value' => $this->order->shipping_name,  'short' => true],
                        ['title' => 'Email',    'value' => $this->order->shipping_email, 'short' => true],
                        ['title' => 'Total',    'value' => $this->order->total,          'short' => true],
                        ['title' => 'Items',    'value' => $this->order->items()->sum('quantity'), 'short' => true],
                        ['title' => 'Placed',   'value' => $this->order->created_at->toIso8601String(), 'short' => false],
                    ],
                    'actions' => [
                        ['type' => 'button', 'text' => 'View Order', 'url' => route('admin.orders.show', $this->order)],
                    ],
                ],
            ],
        ];
    }

    /**
     * Post to Slack.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $isHighValue = $this->order->total > 1000;
        $emoji = $isHighValue ? '🟠' : '🟢';

        return (new SlackMessage)
            ->to('#orders')
            ->headerBlock(sprintf('New Order Received #%s', $this->order->order_number))
            ->sectionBlock(function (SectionBlock $block) use ($emoji) {
                $block->text(sprintf('%s *Customer:* %s', $emoji, $this->order->shipping_name));
            })
            ->contextBlock(function (ContextBlock $block) {
                $block->text(sprintf('Total: %s | Items: %d | Payment: %s',
                    format_price($this->order->total),
                    $this->order->items()->sum('quantity'),
                    strtoupper($this->order->payment_method)
                ));
            })
            ->actionsBlock(function (ActionsBlock $block) {
                $block->button('View Order')->url(route('admin.orders.show', $this->order));
            });
    }
}
