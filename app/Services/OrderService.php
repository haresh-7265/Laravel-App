<?php

namespace App\Services;

use App\Events\Order\OrderPlaced;
use App\Exceptions\EmptyCartException;
use App\Exceptions\ProductOutOfStockException;
use App\Jobs\GenerateInvoicePdf;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Notifications\OrderShipped;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private CartService $cartService,
        private CouponService $couponService
    ) {}

    public function getFilteredOrders(
        array $filters,
        string $role = 'customer',
        ?int $userId = null,
        ?string $cursor = null,
        int $perPage = 20)
    {
        ksort($filters);

        // cache scope: admin sees all, others see only their scoped data
        $cacheScope = $role === 'admin'
            ? 'admin'
            : ($userId ? "user.{$userId}" : 'guest');

        $payload = json_encode([
            'filters' => $filters,
            'cursor' => $cursor,
            'perPage' => $perPage,
        ], JSON_THROW_ON_ERROR); // throws on failure instead of silent false

        $hash = md5($payload); // faster than md5, still collision-resistant for cache keys

        $ordersCacheKey = "orders.{$cacheScope}.{$hash}";

        return Cache::tags(['orders'])->remember($ordersCacheKey, now()->addMinutes(10), function () use ($filters, $role, $userId, $perPage, $cursor) {
            return Order::with([
                'user:id,name,email',
                'items:id,order_id,product_id,product_name',
                'items.product:id,image',
            ])
            ->select([
                'id',
                'user_id',
                'order_number',
                'total',
                'payment_status',
                'payment_method',
                'status',
                'created_at'
            ])
                ->when(
                    $role !== 'admin',
                    fn ($q) => $q->where('user_id', $userId)
                )
                ->when(! empty($filters['search']), function ($q) use ($filters) {
                    $q->where('order_number', 'like', '%'.$filters['search'].'%')
                        ->orWhereHas(
                            'user',
                            fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%')
                                ->orWhere('email', 'like', '%'.$filters['search'].'%')
                        );
                })
                ->when(
                    ! empty($filters['status']),
                    fn ($q) => $q->where('status', $filters['status'])
                )
                ->when(
                    ! empty($filters['payment_status']),
                    fn ($q) => $q->where('payment_status', $filters['payment_status'])
                )
                ->when(
                    ! empty($filters['date']),
                    fn ($q) => $q->whereDate('created_at', $filters['date'])
                )
                ->latest()
                ->orderByDesc('id')
                ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
        });
    }

    public function getOrderStatusCounts()
    {
        $user = request()->user();
        $role = $user?->role;
        $id = $user?->id;
        $suffix = $role !== 'admin' ? ".{$id}" : '';
        $countsCacheKey = "orders.{$role}{$suffix}.status_counts";

        return Cache::tags(['orders'])->remember($countsCacheKey, now()->addMinutes(10), function () use ($role, $id) {
            return Order::selectRaw('status, count(*) as count')
                ->when(
                    $role != 'admin',
                    fn ($q) => $q->where('user_id', $id)
                )
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        });
    }

    public function placeOrder(array $shippingData): Order
    {
        return DB::transaction(function () use ($shippingData) {

            $cartItems = $this->cartService->get();
            if (empty($cartItems)) {
                throw new EmptyCartException('Cart is empty');
            }
            // Stock validation (before touching any DB row)
            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (! $product || $product->stock < $item['quantity']) {
                    throw new ProductOutOfStockException(
                        $item['name'],
                        $item['product_id'],
                        $item['quantity'],
                        $product?->stock ?? 0
                    );
                }
            }

            $total = $this->cartService->totalPrice();
            $subtotal = collect($cartItems)->sum(fn ($item) => $item['original_price'] * $item['quantity']);
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
                'order_number' => 'ORD-'.strtoupper(uniqid()),
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
                        'payment_method' => $order->payment_method,
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

            DB::afterCommit(function () use ($order){
                OrderPlaced::dispatch($order);
                GenerateInvoicePdf::dispatch($order);
            });

            return $order;
        }, attempts: 3);
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
            $order->user->notify((new OrderShipped($order))->locale($order->user->preferredLocale()));
        }
    }

    public function getCustomerOrderStats(int $userId): array
    {
        $stats = DB::table('orders')
            ->select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN status != "cancelled" THEN total ELSE 0 END) as total_spent'),
                DB::raw('ROUND(AVG(CASE WHEN status != "cancelled" THEN total END), 2) as avg_order_value'),
            ])
            ->addSelect([
                'last_order_amount' => Order::select('total')
                    ->where('user_id', $userId)
                    ->latest()
                    ->limit(1),
            ])
            ->where('user_id', $userId)
            ->first();

        $topProducts = OrderItem::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with('product:id,slug,name,image')
            ->selectRaw('product_id, sum(quantity) as total_quantity')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->take(3)
            ->get();

        return [
            'stats' => $stats,
            'topProducts' => $topProducts,
            'ordersByStatus' => $this->getOrderStatusCounts(),
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
                    'error' => $e->getMessage(),
                ]);

                // Return a safe, UI-friendly default
                return [
                    'days' => null,
                    'label' => 'Estimate unavailable — contact support',
                ];
            }
        );
    }
}
