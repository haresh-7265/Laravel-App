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
        $this->forgetDashboard();
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

    // ─── Admin Dashboard Cache ────────────────────────────
    public function forgetDashboard(): void
    {
        Cache::forget('admin.dashboard.stats');
        Cache::forget('admin.dashboard.recent_orders');
        Cache::forget('admin.dashboard.low_stock');
    }

    // ─── Cart Cache ─────────────────────────────────────
    public function forgetCart(?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        if ($userId) {
            Cache::forget("cart.items.{$userId}");
            Cache::forget("cart.summary.{$userId}");
        }
    }

    // ─── Flush Everything ────────────────────────────
    public function flushAll(): void
    {
        Cache::flush();
    }
}