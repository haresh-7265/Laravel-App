<?php

namespace App\Http\Controllers;

use Arr;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $payload = [
            'event' => 'payment.completed',
            'data' => [
                'transaction_id' => 'txn_9kX2mQ',
                'amount' => 1299,
                'currency' => 'INR',
                'customer' => [
                    'id' => 42,
                    'email' => 'customer@example.com',
                ],
            ],
            'meta' => [
                'gateway' => 'razorpay',
            ],
        ];

        $payload = $request->input('payload', $payload);

        $transactionId = Arr::get($payload, 'data.transaction_id', 'unknown');
        $amount = Arr::get($payload, 'data.amount', 0);
        $currency = Arr::get($payload, 'data.currency', 'INR');
        $customerEmail = Arr::get($payload, 'data.customer.email', 'no-reply@store.com');
        $gateway = Arr::get($payload, 'meta.gateway', 'manual');
        $notes = Arr::get($payload, 'data.notes', 'No notes provided');

        return response()->json(['status' => 'ok']);
    }
}
