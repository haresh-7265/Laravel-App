<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductRestockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Product $product)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Product Back in Stock') . ' - ' . $this->product->name)
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? 'Customer']))
            ->line(__('Good news! The product you joined the waitlist for is back in stock.'))
            ->line('**' . __('Product') . ':** ' . $this->product->name)
            ->action(__('View Product'), route('products.show', $this->product))
            ->line(__('Stock may be limited, so place your order soon.'));
    }

}
