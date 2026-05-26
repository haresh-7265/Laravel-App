<?php

use App\Services\AnalyticsService;
use Illuminate\Support\Number;
use Illuminate\Support\Stringable;

if (!function_exists('analytics')) {
    function analytics(): AnalyticsService
    {
        return app(AnalyticsService::class);
    }
}

if (!function_exists('format_price')) {
    function format_price(int|float $amount)
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

if (!function_exists('meta_description')){
    function meta_description(
        string $description,
        ?string $productName = null,
        bool $debug = false,
    ): string {
        return str($description)
            // 1. Strip all HTML tags
            ->stripTags()

            // 2. Collapse whitespace/newlines into single spaces
            ->pipe(fn (Stringable $s) => $s->replaceMatches('/\s+/', ' '))

            // 3. Conditional: if description starts with the product name, remove
            //    the redundant prefix since the <title> already contains it
            ->whenStartsWith($productName ?? '', fn (Stringable $s) => $s->after($productName)->ltrim(' :-'))

            // 4. Conditional: if description ends with "...", clean it up
            //    before we apply our own truncation
            ->whenEndsWith('...', fn (Stringable $s) => $s->beforeLast('...')->append('…'))

            // 5. Conditional: if description contains "sale" or "discount",
            //    prepend a shopping-friendly prefix for richer SERP snippets
            ->whenContains(
                ['sale', 'discount', 'offer'],
                fn (Stringable $s) => $s->prepend('🏷️ ')
            )

            // 6. Debug mid-chain (only when $debug = true, for development)
            ->when($debug, fn (Stringable $s) => $s->dump())

            // 7. Truncate to 160 characters (SEO best practice)
            ->limit(160)

            // 8. Final trim
            ->trim()

            // 9. Resolve to plain string
            ->value();
    }
}
class CurrencyHelper
{
    public static function currencyAbbreviated($amount, $currency = null, $precision = 1)
    {

        if (is_null($currency)) {
            $currency = config('admin.currency_code')[app()->getLocale()]['code'];
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
        return $symbol.$abbreviated;
    }

    private static function getCurrencySymbol($currency)
    {
        return match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'INR' => '₹',
            'GBP' => '£',
            'SAR' => 'ر.س',
            default => $currency,
        };
    }
}