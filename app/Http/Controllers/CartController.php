<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cart
    ) {}

    // ─── Show Cart ────────────────────────────────────────────

    public function index()
    {
        $items = $this->cart->get();
        $total = $this->cart->totalPrice();
        $count = $this->cart->count();

        if (request()->expectsJson()) {
            return response()->json(compact('items', 'total', 'count'));
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
                'message' => "{$product->name} added to cart.",
                'count'   => $this->cart->count(),
                'total'   => $this->cart->totalPrice(),
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

        $this->cart->update($productId, $request->quantity);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cart updated.',
                'count'   => $this->cart->count(),
                'total'   => $this->cart->total(),
            ]);
        }

        return back()->with('success', 'Cart updated.');
    }

    // ─── Remove Item ──────────────────────────────────────────

    public function remove(int $productId)
    {
        $this->cart->remove($productId);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Item removed.',
                'count'   => $this->cart->count(),
                'total'   => $this->cart->total(),
            ]);
        }

        return back()->with('success', 'Item removed from cart.');
    }

    // ─── Clear Cart ───────────────────────────────────────────

    public function clear()
    {
        $this->cart->clear();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Cart cleared.']);
        }

        return back()->with('success', 'Cart cleared.');
    }
}