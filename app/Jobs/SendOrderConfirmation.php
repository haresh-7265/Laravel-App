<?php

namespace App\Jobs;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Send the order confirmation email to the customer.
     *
     * This is the final step in the checkout chain — it runs only after
     * payment has been charged, stock reserved, and invoice generated.
     */
    public function handle(): void
    {
        $this->order->load('user');

        Mail::to($this->order->shipping_email, $this->order->shipping_name)
            ->locale($this->order->user?->preferredLocale())
            ->later(now()->addMinutes(5), new OrderConfirmation($this->order));

        Log::channel('order')->info("SendOrderConfirmation: Email sent for order #{$this->order->order_number} to {$this->order->shipping_email}.");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $e): void
    {
        Log::channel('order')->error("SendOrderConfirmation FAILED for order #{$this->order->order_number}: {$e->getMessage()}");
    }
}
