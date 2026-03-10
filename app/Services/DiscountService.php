<?php

namespace App\Services;

class DiscountService
{
    public function calculateDiscount($price, $discountPercent)
    {
        $discount = ($price * $discountPercent) / 100;
        return $price - $discount;
    }
}