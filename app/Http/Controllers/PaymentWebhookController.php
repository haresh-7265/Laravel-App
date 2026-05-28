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
        $signature = $request->header('X-Webhook-Signature');
        $secret = env('WEBHOOK_SIGNING_SECRET', 'webhook_secret_key');

        if (!$signature) {
            return response()->json([
                'status' => 'error',
                'message' => 'Signature header missing.',
            ], 400);
        }

        $computedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($computedSignature, $signature)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid webhook signature.',
            ], 403);
        }

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
