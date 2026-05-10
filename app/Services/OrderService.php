<?php

namespace App\Services;

use App\Exceptions\ProductOutOfStockException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Notifications\OrderShipped;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private CartService $cartService,
        private CouponService $couponService
    ) {
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

            // ─── Coupon handling ───────────────────────────────
            $couponCode = null;
            $couponDiscount = 0;
            $appliedCoupon = $this->cartService->getAppliedCoupon();

            if ($appliedCoupon) {
                $coupon = Coupon::find($appliedCoupon['coupon_id']);

                if ($coupon) {
                    // Re-validate one last time before placing order
                    $validation = $this->couponService->validate($coupon->code, auth()->user(), $total);

                    if ($validation['success']) {
                        $couponCode = $coupon->code;
                        $couponDiscount = $validation['discount'];

                        // Redeem — increment used_count
                        $this->couponService->redeem($coupon, auth()->user());
                    }
                }
            }

            $finalTotal = $total - $couponDiscount;

            // Create order
            $paymentMethod = $shippingData['payment_method'] ?? 'cod';
            $paymentStatus = $paymentMethod == 'cod' ? 'unpaid' : 'paid';
            $order = tap(Order::create([
                'user_id' => auth()->id(),
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'coupon_code' => $couponCode,
                'coupon_discount' => $couponDiscount,
                'total' => $finalTotal,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'shipping_name' => $shippingData['name'],
                'shipping_email' => $shippingData['email'],
                'shipping_phone' => $shippingData['phone'],
                'shipping_address' => $shippingData['address'],
                'shipping_city' => $shippingData['city'],
                'shipping_state' => $shippingData['state'],
                'shipping_pincode' => $shippingData['pincode'],
            ]), function (Order $order) {
                DB::afterCommit(function () use ($order) {
                    Log::channel('order')->info('Order Placed', [
                        'order_number' => $order->order_number,
                        'total' => $order->total,
                        'user_id' => $order->user_id,
                        'payment_status' => $order->payment_status,
                        'payment_method' => $order->payment_method
                    ]);
                });
            });

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

                $product = Product::findOrFail($item['product_id']);

                $product->decrement('stock', $item['quantity']);

                // refresh updated value
                $product->refresh();

            }

            // Clear cart after order (also clears applied coupon)
            $this->cartService->clear();

            return $order;
        });
    }

    public function cancelOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {

            $order->load('items.product');

            $order->update(['status' => 'cancelled']);

            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            DB::afterCommit(function () use ($order) {
                Log::channel('order')->info('Order Cancelled', [
                    'order_number' => $order->order_number,
                    'canceled_by' => auth()->id() ?? 'system',
                ]);
            });

            return true;
        });

    }

    public function updateOrderStatus(Order $order, string $status): void
    {
        if ($status === 'cancelled') {
            $this->cancelOrder($order);
            return;
        }
        $order->update(['status' => $status]);
        $order->refresh();

        if ($status === 'shipped' && $order->user) {
            $order->user->notify(new OrderShipped($order));
        }
    }


    public function getCustomerOrdersAndStats(int $userId): array
    {
        $orders = Order::where('user_id', $userId)
            ->latest()
            ->get();

        $totalOrders = $orders->count();

        $totalSpent = $orders->where('status', '!=', 'cancelled')->sum('total');

        $averageOrderValue = $totalOrders > 0
            ? $orders->where('status', '!=', 'cancelled')->avg('total')
            : 0;

        $topProducts = OrderItem::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with('product')
            ->selectRaw('product_id, sum(quantity) as total_quantity')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->take(3)
            ->get();

        $ordersByStatus = $orders->groupBy('status')->map->count();

        return [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'totalSpent' => $totalSpent,
            'averageOrderValue' => $averageOrderValue,
            'topProducts' => $topProducts,
            'ordersByStatus' => $ordersByStatus
        ];
    }

    public function getShippingEstimate(Order $order): array
    {
        return rescue(
            // Attempt — may throw network, timeout, or parsing exceptions
            fn () => throw new Exception('estimation failed'),
 
            // Fallback — returned whenever $callback throws
            rescue: function (\Throwable $e) use ($order): array {
                Log::warning('Shipping estimate unavailable', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
 
                // Return a safe, UI-friendly default
                return [
                    'days'  => null,
                    'label' => 'Estimate unavailable — contact support',
                ];
            }
        );
    }
}