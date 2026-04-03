<?php
namespace App\Http\Controllers;

use App\Exceptions\ProductOutOfStockException;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PhpParser\Node\Stmt\TryCatch;

class CartController extends Controller
{
    public function __construct(
        private CartService $cart
    ) {
    }

    // ─── Show Cart ────────────────────────────────────────────
    public function index()
    {
        $items = $this->cart->get();
        $total = $this->cart->totalPrice();
        $count = $this->cart->count();

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
    public function update(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $this->cart->update($productId, $request->quantity);
        } catch (ProductOutOfStockException $e) {
            if($request->expectsJson()){
                return $this->cartJson('danger', $e->getMessage());
            }
            throw $e;
        }

        if ($request->expectsJson()) {
            return $this->cartJson('success', 'Cart updated.');
        }

        return back()->with('success', 'Cart updated.');
    }

    // ─── Remove Item ──────────────────────────────────────────
    public function remove(int $productId): JsonResponse
    {
        $this->cart->remove($productId);

        if (request()->expectsJson()) {
            return $this->cartJson('success', 'Item removed.');
        }

        return back()->with('success', 'Item removed from cart.');
    }

    // ─── Clear Cart ───────────────────────────────────────────
    public function clear(): JsonResponse
    {
        $this->cart->clear();

        if (request()->expectsJson()) {
            return $this->cartJson('success', 'Cart cleared.');
        }

        return back()->with('success', 'Cart cleared.');
    }

    // ─── Shared JSON response builder ────────────────────────
    private function cartJson(string $status = 'success', string $message = 'OK'): JsonResponse
    {
        $items = $this->cart->get();
        $total = $this->cart->totalPrice();
        $count = $this->cart->count();

        return response()->json([
            'status'        => $status,
            'message'       => $message,
            'count'         => $count,
            'total'         => $total,
            'empty'         => $count === 0,
            "items_html"    => view('cart._items_loop', compact('items', 'total'))->render(),
            "summary_html"  => view('cart._summary', compact('items','total', 'count'))->render(),
            "shipping_html" => view('cart._shipping_bar', compact('total'))->render()
        ]);
    }
}