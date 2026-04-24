<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function process($amount)
    {
        return "Payment of ₹{$amount} processed";
    }
    public function charge(array $payload)
    {
        return retry(
            3, // attempts
            function () use ($payload) {

                $response = Http::timeout(5)
                    ->post('https://api.payment.com/charge', $payload);  // fake api, implement real later

                // Throw exception if failed (required for retry)
                if ($response->failed()) {
                    throw new RequestException($response);
                }

                return $response->json();
            },
            300, // 300ms delay
            function ($exception) {
                // Retry ONLY for server/network errors
                if ($exception instanceof RequestException) {
                    $status = optional($exception->response)->status();

                    return $status >= 500 || $status === null;
                }

                return false;
            }
        );

    }
}

