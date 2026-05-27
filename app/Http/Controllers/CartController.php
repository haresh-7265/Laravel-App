<?php
namespace App\Http\Controllers;

use App\Exceptions\ProductOutOfStockException;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Number;

class CartController extends Controller
{
    public function __construct(
        private CartService $cart,
        private CouponService $couponService
    ) {
    }

    // ─── Show Cart ────────────────────────────────────────────
    public function index()
    {
        $items = $this->cart->get();
        $cartSummary = $this->cart->getSummary();
        $total = $cartSummary['total'];
        $count = $cartSummary['count'];

        if (request()->expectsJson()) {
            return $this->cartJson();
        }

        return view('cart.index', compact('items', 'total', 'count'));
    }

    // ─── Add to Cart ──────────────────────────────────────────
    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $this->cart->add($product, $request->input('quantity', 1));

        if ($request->expectsJson()) {
            return response()->json([
                'status'     => 'success',
                'message'    => "{$product->name} added to cart.",
                'cart_count' => $this->cart->count()
            ]);
        }

        return back()->with('success', "{$product->name} added to cart.");
    }

    // ─── Update Quantity ──────────────────────────────────────
    public function update(Request $request, int $productId)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $this->cart->update($productId, $request->quantity);
        } catch (ProductOutOfStockException $e) {
            if($request->expectsJson()){
                return $this->cartJson('error', $e->getMessage());
            }
            throw $e;
        }

        if ($request->expectsJson()) {
            return $this->cartJson('success', 'Cart updated.');
        }

        return back()->with('success', 'Cart updated.');
    }

    // ─── Remove Item ──────────────────────────────────────────
    public function remove(int $productId)
    {
        $this->cart->remove($productId);

        if (request()->expectsJson()) {
            return $this->cartJson('success', 'Item removed.');
        }

        return back()->with('success', 'Item removed from cart.');
    }

    // ─── Clear Cart ───────────────────────────────────────────
    public function clear()
    {
        $this->cart->clear();

        if (request()->expectsJson()) {
            return $this->cartJson('success', 'Cart cleared.');
        }

        return back()->with('success', 'Cart cleared.');
    }

    // ─── Apply Coupon ─────────────────────────────────────────
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_code' => ['required', 'string', 'max:50'],
        ]);

        $user = current_user();
        $cartTotal = $this->cart->totalPrice();

        $result = $this->couponService->validate($request->coupon_code, $user, $cartTotal);

        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'],
            ], 422);
        }

        // Store coupon in session
        $this->cart->applyCoupon($result['coupon'], $result['discount']);

        // Bust cache so summary recalculates
        app(\App\Services\CacheService::class)->forgetCart();

        return $this->cartJson('success', "Coupon '{$result['coupon']->code}' applied! You save " . format_price($result['discount']));
    }

    // ─── Remove Coupon ────────────────────────────────────────
    public function removeCoupon(): JsonResponse
    {
        $this->cart->removeCoupon();

        // Bust cache
        app(\App\Services\CacheService::class)->forgetCart();

        return $this->cartJson('success', 'Coupon removed.');
    }

    // ─── Shared JSON response builder ────────────────────────
    private function cartJson(string $status = 'success', string $message = 'OK'): JsonResponse
    {
        $items = $this->cart->get();
        $cartSummary = $this->cart->getSummary();
        $total = $cartSummary['total'];
        $count = $cartSummary['count'];
        $coupon = $cartSummary['coupon'];
        $couponDiscount = $cartSummary['coupon_discount'];

        return response()->json([
            'status'          => $status,
            'message'         => $message,
            'count'           => $count,
            'total'           => $total,
            'coupon'          => $coupon,
            'coupon_discount' => $couponDiscount,
            'empty'           => $count === 0,
            "items_html"      => view('cart._items_loop', compact('items', 'total'))->render(),
            "summary_html"    => view('cart._summary', compact('items', 'total', 'count', 'coupon', 'couponDiscount'))->render(),
            "shipping_html"   => view('cart._shipping_bar', compact('total'))->render()
        ]);
    }
}