<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductLowStock extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly Product $product)
    {
        //
    }

    /**
     * Delivery channels: email + database (no broadcast needed for stock alerts).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Admin email — reuses the existing low-stock markdown template.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Low Stock Alert: ' . $this->product->name)
            ->markdown('emails.admin.low-stock', [
                'product' => $this->product,
            ]);
    }

    /**
     * Data stored in the notifications table.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'stock'        => $this->product->stock,
            'message'      => 'Low stock alert: ' . $this->product->name . ' has only ' . $this->product->stock . ' units left',
            'icon'         => 'alert',
        ];
    }
}
