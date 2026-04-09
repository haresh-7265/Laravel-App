<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    // ─── Product Cache ───────────────────────────────
    public function forgetProduct(string $slug): void
    {
        Cache::forget("product.{$slug}");
        $this->forgetProductPages();
    }

    public function forgetProductPages(): void
    {
        $keys = Cache::get('products.page.keys', []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget('products.page.keys');
    }

    // ─── Category Cache ──────────────────────────────
    public function forgetCategories(): void
    {
        Cache::forget("categoies");
    }

    // ─── Flush Everything ────────────────────────────
    public function flushAll(): void
    {
        Cache::flush();
    }
}