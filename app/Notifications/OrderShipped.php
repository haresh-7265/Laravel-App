<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Shipped - #'.$this->order->order_number)
            ->greeting('Hello '.($notifiable->name ?? 'Customer').',')
            ->line('Great news! Your order '.$this->order->order_number.' has been shipped.')
            ->line('Your tracking number is: '.($this->order->tracking_number ?? 'N/A'))
            ->action('View Order', route('orders.show', $this->order))
            ->line('Thank you for shopping with us!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'tracking_number' => $this->order->tracking_number ?? 'N/A',
            'message' => 'Your order '.$this->order->order_number.' has been shipped.',
            'icon' => 'truck',
        ];
    }
}
