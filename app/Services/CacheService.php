<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    // ─── Product Cache ───────────────────────────────
    public function forgetProduct(string $slug): void
    {
        Cache::tags(['products'])->forget("product.{$slug}");
        $this->forgetProductPages();
    }

    public function forgetProductPages(): void
    {
        Cache::tags(['products.pages'])->flush();
    }

    // ─── Category Cache ──────────────────────────────
    public function forgetCategories(): void
    {
        Cache::tags(['categories'])->flush();
    }

    // ─── Admin Dashboard Cache ────────────────────────────
    public function forgetDashboard(): void
    {
        Cache::tags(['admin'])->flush();
    }

    // ─── Cart Cache ─────────────────────────────────────
    public function forgetCart(?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        if ($userId) {
            Cache::tags(["cart.user.{$userId}"])->flush();
        }
    }

    // ─── Flush Everything ────────────────────────────
    public function flushAll(): void
    {
        Cache::tags(['products', 'admin', 'customer'])->flush();
    }
}