<?php

namespace App\Services;

use App\Exceptions\ProductOutOfStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private CartService $cartService)
    {
    }

    public function placeOrder(array $shippingData): Order
    {
        return DB::transaction(function () use ($shippingData) {

            $cartItems = $this->cartService->get();

            // Stock validation (before touching any DB row) 
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (!$product || $product->stock < $item['quantity']) {
                    throw new ProductOutOfStockException(
                        $item['name'],
                        $item['product_id'],
                        $item['quantity'],
                        $product?->stock ?? 0
                    );
                }
            }

            $total = $this->cartService->totalPrice();
            $subtotal = collect($cartItems)->sum(fn($item) => $item['original_price'] * $item['quantity']);
            $discount = $subtotal - $total;

            // Create order
            $paymentMethod = $shippingData['payment_method'] ?? 'cod';
            $paymentStatus = $paymentMethod=='cod' ? 'unpaid' : 'paid';
            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'shipping_name' => $shippingData['name'],
                'shipping_email' => $shippingData['email'],
                'shipping_phone' => $shippingData['phone'],
                'shipping_address' => $shippingData['address'],
                'shipping_city' => $shippingData['city'],
                'shipping_state' => $shippingData['state'],
                'shipping_pincode' => $shippingData['pincode'],
            ]);

            // Create order items + decrement stock
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'price' => $item['price'],
                    'discount_price' => $item['discount_price'] ?? null,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['quantity'] * ($item['discount_price'] ?? $item['price']),
                ]);

                Product::where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
            }

            // Clear cart after order
            $this->cartService->clear();

            // update stock
            return $order;
        });
    }

    public function cancelOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);

            foreach ($order->items as $item) {
                $item->product()->increment('stock', $item->quantity);
            }
        });
    }
}