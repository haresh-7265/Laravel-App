<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

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
     * Route mail to the "emails" queue.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'emails',
        ];
    }

    /**
     * Admin email — reuses the existing low-stock markdown template.
     * Locale-aware subject via __().
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ ' . __('Low Stock Alert') . ': ' . $this->product->name)
            ->markdown('emails.admin.low-stock', [
                'product' => $this->product,
            ]);
    }

    /**
     * Data stored in the notifications table.
     * Uses __() for locale-aware database messages.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'stock'        => $this->product->stock,
            'message'      => __('Low stock alert: :product has only :stock units left', [
                'product' => $this->product->name,
                'stock'   => $this->product->stock,
            ]),
            'icon'         => 'alert',
        ];
    }

    /**
     * Handle notification failure — log the error.
     */
    public function failed(Throwable $e): void
    {
        Log::channel('product')->error('ProductLowStock notification failed', [
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'error'        => $e->getMessage(),
            'trace'        => $e->getTraceAsString(),
        ]);
    }
}
