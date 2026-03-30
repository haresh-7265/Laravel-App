<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{

    public function index()
    {
        $cart = session('cart', []);
        if (empty($cart)) {
            return view('cart.index', compact('cart'))->with('warning', 'Cart is empty');
        }

        return view('cart.index', compact('cart'));
    }
    public function add(Request $request, Product $product)
    {
        $cart = session('cart', []);

        $qty = $request->input('qty', 1);
        if (isset($cart[$product->id])) {
            $cart[$product->id]['qty'] += $qty;
            session()->put('cart', $cart);

            return redirect()->back()
                ->with('warning', "'{$product->name}' is already in your cart — quantity updated to {$cart[$product->id]['qty']}.");
        }

        $cart[$product->id] = [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'image' => $product->image,
            'qty' => $qty,
        ];
        session()->put('cart', $cart);

        return redirect()->back()
            ->with('success', "'{$product->name}' added to your cart.");
    }

    public function remove(int $id)
    {
        $cart = session('cart', []);

        if (!isset($cart[$id])) {
            return redirect()->route('cart.index')
                ->with('warning', 'Product not found in your cart.');
        }

        $name = $cart[$id]['name'];
        $cart[$id]['qty']--;

        if ($cart[$id]['qty'] <= 0) {
            unset($cart[$id]);
            session()->put('cart', $cart);
            return redirect()->route('cart.index')
                ->with('info', "'{$name}' removed from your cart.");
        }

        session()->put('cart', $cart);
        return redirect()->route('cart.index')
            ->with('info', "'{$name}' quantity reduced to {$cart[$id]['qty']}.");
    }

    public function delete(int $id)
    {
        $cart = session('cart', []);
        $name = $cart[$id]['name'] ?? "Product #{$id}";

        unset($cart[$id]);
        session()->put('cart', $cart);

        return redirect()->route('cart.index')
            ->with('danger', "'{$name}' has been deleted from your cart.");
    }

    public function clear()
    {
        session()->forget('cart');

        return redirect()->route('cart.index')
            ->with('info', 'Your cart has been cleared.');
    }
}