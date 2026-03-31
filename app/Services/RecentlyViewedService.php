<?php
namespace App\Services;

use App\Models\Product;

class RecentlyViewedService
{
    protected string $key = 'recently_viewed';
    protected int $limit = 10;

    // ─── Track ────────────────────────────────────────────────

    public function track(int $productId): void
    {
        $ids = session()->get($this->key, []);

        // remove if already exists (to re-add at top)
        $ids = array_filter($ids, fn($id) => $id !== $productId);

        // add to beginning
        array_unshift($ids, $productId);

        // keep only latest $limit
        $ids = array_slice($ids, 0, $this->limit);

        session()->put($this->key, $ids);
    }

    // ─── Get ──────────────────────────────────────────────────

    public function get()
    {
        $ids = session()->get($this->key, []);

        if (empty($ids)) return collect();

        // fetch products and maintain order
        return Product::whereIn('id', $ids)
            ->get()
            ->sortBy(fn($product) => array_search($product->id, $ids))
            ->values();
    }

    // ─── Clear ────────────────────────────────────────────────

    public function clear(): void
    {
        session()->forget($this->key);
    }
}