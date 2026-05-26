<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class StringHelper
{
    // ─── Naming Convention Transformers ─────────────────────────────────

    /**
     * Convert a string to the correct naming convention for a given context.
     *
     * Uses: Str::camel(), Str::snake(), Str::studly(), Str::kebab()
     *
     * @param  string  $value   The source string (e.g. 'product_category')
     * @param  string  $convention  One of: camel, snake, studly, kebab
     */
    public static function toConvention(string $value, string $convention): string
    {
        return match ($convention) {
            'camel'  => Str::camel($value),   // productCategory
            'snake'  => Str::snake($value),   // product_category
            'studly' => Str::studly($value),  // ProductCategory
            'kebab'  => Str::kebab($value),   // product-category
            default  => $value,
        };
    }

    /**
     * Derive a model class name from a table name.
     *
     * Uses: Str::singular() + Str::studly()
     *
     * Example: 'product_reviews' → 'ProductReview'
     */
    public static function tableToModel(string $tableName): string
    {
        return Str::studly(Str::singular($tableName));
    }

    /**
     * Derive a table name from a model class name.
     *
     * Uses: Str::plural() + Str::snake()
     *
     * Example: 'ProductReview' → 'product_reviews'
     */
    public static function modelToTable(string $modelName): string
    {
        return Str::plural(Str::snake($modelName));
    }

    /**
     * Generate a slug-friendly route parameter from a model name.
     *
     * Uses: Str::kebab() + Str::singular()
     *
     * Example: 'ProductCategory' → 'product-category'
     */
    public static function modelToRouteParam(string $modelName): string
    {
        return Str::kebab(Str::singular($modelName));
    }

    /**
     * Generate a camelCase method name from a descriptive string.
     *
     * Uses: Str::camel()
     *
     * Example: 'get user orders' → 'getUserOrders'
     */
    public static function toMethodName(string $description): string
    {
        return Str::camel($description);
    }

    // ─── Pluralisation ─────────────────────────────────────────────────

    /**
     * English-only plural. Use ONLY for:
     *  - Internal code (log messages, dev tools, debugging)
     *  - English-only contexts where translation is unnecessary
     *
     * For user-facing text, use trans_choice() instead — Str::plural()
     * is English-only and won't work for Arabic, French, etc.
     *
     * @see trans_choice() for locale-aware pluralisation
     */
    public static function englishPlural(string $word, int $count): string
    {
        return Str::plural($word, $count);
    }

}
