<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Number;

class ValidDiscountPrice implements ValidationRule
{
    public function __construct(private float $price)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value < 0) {
            $fail('Discount price cannot be negative.');
        }

        if ($value >= $this->price) {
            $fail('Discount price must be less than original price ' . Number::currency($this->price) . '.');
        }
    }
}