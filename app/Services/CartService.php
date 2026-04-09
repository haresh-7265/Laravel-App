<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Exceptions\ProductOutOfStockException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CartService
{
    private string $sessionKey = 'cart';

    public function __construct(
        private CacheService $cacheService
    ) {
    }

    // ─────────────────────────────────────────────────────────
    // GET CART
    // ─────────────────────────────────────────────────────────

    public function get(): array
    {
        if (auth()->check()) {
            return $this->getDbCart();
        }

        return $this->getSessionCart();
    }

    // ─── DB cart (logged in users) ────────────────────────────

    private function getDbCart(): array
    {
        $userId = auth()->id();

        return Cache::tags(['customer', "cart.user.{$userId}"])->remember("cart.items.{$userId}", now()->addMinutes(10), function () use ($userId) {
            return Cart::with('product')
                ->where('user_id', $userId)
                ->get()
                ->map(fn($item) => $this->formatDbItem($item))
                ->values()
                ->toArray();
        });
    }

    // ─── Session cart (guests) ────────────────────────────────

    private function getSessionCart(): array
    {
        return collect(Session::get($this->sessionKey, []))
            ->values()
            ->toArray();
    }

    // ─── Format DB item to match session structure ────────────

    private function formatDbItem(Cart $item): array
    {
        $price = $item->product->discount_price ?? $item->product->price;
        return [
            'product_id' => $item->product_id,
            'name' => $item->product->name,
            'price' => $price,
            'original_price' => $item->product->price,
            'quantity' => $item->quantity,
            'subtotal' => $item->getSubtotalAttribute(),
            'image_url' => $item->getImageUrlAttribute(),
            'stock' => $item->product->stock,
        ];
    }

    // ─────────────────────────────────────────────────────────
    // ADD ITEM
    // ─────────────────────────────────────────────────────────

    public function add(Product $product, int $quantity = 1): void
    {
        // Check stock before adding
        $this->checkStock($product, $quantity);

        if (auth()->check()) {
            $this->addToDb($product, $quantity);
        } else {
            $this->addToSession($product, $quantity);
        }

        $this->cacheService->forgetCart();

        Log::channel('product')->info('Item added to cart', [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'user_id' => auth()->id() ?? 'guest',
        ]);
    }

    private function addToDb(Product $product, int $quantity): void
    {
        $existing = Cart::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            // Check stock against new total quantity
            $newQuantity = $existing->quantity + $quantity;
            $this->checkStock($product, $newQuantity);

            $existing->update(['quantity' => $newQuantity]);
        } else {
            Cart::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }
    }

    private function addToSession(Product $product, int $quantity): void
    {
        $cart = Session::get($this->sessionKey, []);

        if (isset($cart[$product->id])) {
            //  Check stock against new total quantity
            $newQuantity = $cart[$product->id]['quantity'] + $quantity;
            $this->checkStock($product, $newQuantity);

            $cart[$product->id]['quantity'] = $newQuantity;
            $cart[$product->id]['subtotal'] = ($product->discount_price ?? $product->price) * $newQuantity;
        } else {
            $cart[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->discount_price ?? $product->price,
                'original_price' => $product->price,
                'quantity' => $quantity,
                'subtotal' => ($product->discount_price ?? $product->price) * $quantity,
                'image_url' => $this->getImageUrl($product->image),
                'stock' => $product->stock,
            ];
        }

        Session::put($this->sessionKey, $cart);
    }

    // ─────────────────────────────────────────────────────────
    // UPDATE QUANTITY
    // ─────────────────────────────────────────────────────────

    public function update(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($productId);
            return;
        }

        $product = Product::findOrFail($productId);

        // Check stock on update too
        $this->checkStock($product, $quantity);

        if (auth()->check()) {
            Cart::where('user_id', auth()->id())
                ->where('product_id', $productId)
                ->update(['quantity' => $quantity]);
        } else {
            $cart = Session::get($this->sessionKey, []);

            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] = $quantity;
                $cart[$productId]['subtotal'] = $cart[$productId]['price'] * $quantity;
                Session::put($this->sessionKey, $cart);
            }
        }

        $this->cacheService->forgetCart();
    }

    // ─────────────────────────────────────────────────────────
    // REMOVE ITEM
    // ─────────────────────────────────────────────────────────

    public function remove(int $productId): void
    {
        if (auth()->check()) {
            Cart::where('user_id', auth()->id())
                ->where('product_id', $productId)
                ->delete();
        } else {
            $cart = Session::get($this->sessionKey, []);
            unset($cart[$productId]);
            Session::put($this->sessionKey, $cart);
        }

        $this->cacheService->forgetCart();
    }

    // ─────────────────────────────────────────────────────────
    // CLEAR CART
    // ─────────────────────────────────────────────────────────

    public function clear(): void
    {
        if (auth()->check()) {
            Cart::where('user_id', auth()->id())->delete();
        } else {
            Session::forget($this->sessionKey);
        }

        $this->cacheService->forgetCart();
    }

    // ─────────────────────────────────────────────────────────
    // MERGE SESSION → DB ON LOGIN
    // ─────────────────────────────────────────────────────────

    public function mergeSessionCart(): void
    {
        $sessionCart = Session::get($this->sessionKey, []);

        // Only merge if logged in AND is a customer
        if (empty($sessionCart) || !auth()->check() || !auth()->user()->isCustomer()) {
            return;
        }

        foreach ($sessionCart as $productId => $item) {
            $product = Product::find($productId);

            if (!$product) {
                continue;   // skip if product was deleted
            }

            $existing = Cart::where('user_id', auth()->id())
                ->where('product_id', $productId)
                ->first();

            $newQuantity = $existing
                ? $existing->quantity + $item['quantity']
                : $item['quantity'];

            // Cap at available stock — never exceed stock limit
            $newQuantity = min($newQuantity, $product->stock);

            if ($newQuantity <= 0) {
                continue;
            }

            Cart::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'product_id' => $productId,
                ],
                [
                    'quantity' => $newQuantity,
                ]
            );
        }

        // Clear session cart after merge
        Session::forget($this->sessionKey);

        $this->cacheService->forgetCart();

        Log::channel('product')->info('Session cart merged to DB', [
            'user_id' => auth()->id(),
            'item_count' => count($sessionCart),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────

    public function totalPrice(): float
    {
        return $this->getSummary()['total'];
    }

    public function count(): int
    {
        return $this->getSummary()['count'];
    }

    public function getSummary(): array
    {
        if (!auth()->check()) {
            return $this->computeSummary();
        }

        $userId = auth()->id();

        return Cache::tags(['cart', "cart.user.{$userId}"])
            ->remember("cart.summary.{$userId}", now()->addMinutes(10), function () {
                return $this->computeSummary();
            });
    }

    private function computeSummary(): array
    {
        $items = collect($this->get());

        return [
            'total' => $items->sum(fn($item) => $item['price'] * $item['quantity']),
            'count' => $items->sum(fn($item) => $item['quantity']),
        ];
    }

    public function isEmpty(): bool
    {
        return empty($this->get());
    }

    // ─── Stock check — throws exception if insufficient ───────

    private function checkStock(Product $product, int $quantity): void
    {
        if ($product->stock < $quantity) {
            throw new ProductOutOfStockException(
                productName: $product->name,
                productId: $product->id,
                requestedQuantity: $quantity,
                availableQuantity: $product->stock,
            );
        }
    }

    // ─── Image URL helper ─────────────────────────────────────

    private function getImageUrl(?string $path): string
    {
        if (!$path) {
            return asset('storage/products/default.jpg');
        }

        return asset('storage/' . $path);
    }
}