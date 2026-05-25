<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChargePayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Simulate charging the customer's payment method.
     *
     * In a real application this would call a payment gateway (Stripe, Razorpay, etc.).
     * For COD orders we simply mark as "unpaid" and move on.
     */
    public function handle(): void
    {
        if ($this->order->payment_method === 'cod') {
            Log::channel('order')->info("ChargePayment: COD order #{$this->order->order_number} — no charge needed.");

            return;
        }

        // ── Simulate payment gateway charge ──────────────────────────
        // In production: $response = PaymentGateway::charge($this->order->total, ...);

        $this->order->update(['payment_status' => 'paid']);

        Log::channel('order')->info("ChargePayment: Order #{$this->order->order_number} charged ".format_price($this->order->total).'.');
    }

    /**
     * Handle a job failure — the chain's catch() will also fire.
     */
    public function failed(\Throwable $e): void
    {
        Log::channel('order')->error("ChargePayment FAILED for order #{$this->order->order_number}: {$e->getMessage()}");

        $this->order->update(['payment_status' => 'failed']);
    }
}
