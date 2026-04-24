<?php

use App\Services\AnalyticsService;
use Illuminate\Support\Number;

if (!function_exists('analytics')) {
    function analytics(): AnalyticsService
    {
        return app(AnalyticsService::class);
    }
}

if (!function_exists('format_price')) {
    function format_price($amount)
    {
        return Number::currency($amount);
    }
}

if (!function_exists('order_status_badge')) {
    function order_status_badge($status)
    {
        return match ($status) {
            'pending' => 'warning',
            'processing' => 'primary',
            'shipped' => 'info',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }
}

if (!function_exists('human_file_size')) {
    function human_file_size($bytes)
    {
        return Number::fileSize($bytes);
    }
}

class CurrencyHelper
{
    public static function currencyAbbreviated($amount, $currency = null, $precision = 1)
    {

        if (is_null($currency)) {
            $currency = config('admin.currency_code');
        }

        $absAmount = abs($amount);

        if ($absAmount >= 1000000) {
            $abbreviated = round($amount / 1000000, $precision) . 'M';
        } elseif ($absAmount >= 1000) {
            $abbreviated = round($amount / 1000, $precision) . 'K';
        } else {
            return Number::currency($amount, in: $currency);
        }

        $symbol = self::getCurrencySymbol($currency);
        return $symbol . $abbreviated;
    }

    private static function getCurrencySymbol($currency)
    {
        return match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'INR' => '₹',
            'GBP' => '£',
            default => $currency,
        };
    }
}