<?php

namespace Tests\Feature;

use App\Jobs\GenerateInvoicePdf;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_generates_invoice_after_checkout(): void
    {
        $user = User::factory()->customer()->create();
        $order = Order::factory()->for($user)->create();

        GenerateInvoicePdf::dispatchSync($order); // bypasses queue, runs now

        Storage::disk('local')->assertExists("invoices/invoice-{$order->order_number}.pdf");
    }
}
