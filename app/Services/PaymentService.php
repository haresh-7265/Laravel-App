<?php

namespace App\Services;

class PaymentService
{
    public function process($amount)
    {
        return "Payment of ₹{$amount} processed";
    }
}

