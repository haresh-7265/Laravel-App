<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class RecentlyViewedService
{
    protected int $limit = 10;
    protected int $ttlDays = 30;

    //  Generate unique key (user or session)
    protected function getKey(?int $userId = null, ?string $sessionId = null): string
    {
        return $userId
            ? "recently_viewed:user:{$userId}"
            : "recently_viewed:session:{$sessionId}";
    }

    // ─── Track ────────────────────────────────────────────────

    public function track(int $productId, ?int $userId = null, ?string $sessionId = null): void
    {
        $key = $this->getKey($userId, $sessionId);

        $ids = Cache::get($key, []);

        // remove if already exists
        $ids = array_filter($ids, fn($id) => $id !== $productId);

        // add to beginning
        array_unshift($ids, $productId);

        // keep only latest limit
        $ids = array_slice($ids, 0, $this->limit);

        Cache::put($key, $ids, now()->addDays($this->ttlDays));
    }

    // ─── Get ──────────────────────────────────────────────────

    public function get(?int $userId = null, ?string $sessionId = null)
    {
        $key = $this->getKey($userId, $sessionId);

        $ids = Cache::get($key, []);

        if (empty($ids)) return collect();

        return Product::whereIn('id', $ids)
            ->get()
            ->sortBy(fn($product) => array_search($product->id, $ids))
            ->values();
    }

    // ─── Clear ────────────────────────────────────────────────

    public function clear(?int $userId = null, ?string $sessionId = null): void
    {
        $key = $this->getKey($userId, $sessionId);

        Cache::forget($key);
    }
}