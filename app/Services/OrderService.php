<?php

namespace App\Services;

use App\Events\Order\OrderPlaced;
use App\Exceptions\EmptyCartException;
use App\Exceptions\ProductOutOfStockException;
use App\Jobs\ChargePayment;
use App\Jobs\GenerateInvoicePdf;
use App\Jobs\ReleaseStock;
use App\Jobs\ReserveStock;
use App\Jobs\SendOrderConfirmation;
use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderShipped;
use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private CartService $cartService,
        private CouponService $couponService
    ) {}

    public function getAllOrders(
        array $filters,
        Admin|User|null $user = null,
        ?string $cursor = null,
        int $perPage = 20)
    {
        ksort($filters);

        $filterHash = md5(json_encode($filters));

        $ordersCacheKey = "orders.all.{$filterHash}.{$perPage}.{$cursor}";

        return Cache::tags(['orders'])->remember($ordersCacheKey, now()->addMinutes(10), function () use ($filters, $perPage, $cursor) {
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
                    'created_at',
                ])
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

    public function getOwnOrders(
        array $filters,
        User $user,
        ?string $cursor = null,
        int $perPage = 20)
    {
        ksort($filters);

        $filterHash = md5(json_encode($filters));

        $ordersCacheKey = "orders.own.{$user->id}.{$filterHash}.{$perPage}.{$cursor}";

        return Cache::tags(['orders'])->remember($ordersCacheKey, now()->addMinutes(10), function () use ($filters, $user, $perPage, $cursor) {
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
                    'created_at',
                ])
                ->where('user_id', $user->id)
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

    public function getAllOrdersStatusCounts()
    {
        $countsCacheKey = 'orders.all.status_counts';

        return Cache::tags(['orders'])->remember($countsCacheKey, now()->addMinutes(10), function () {
            return Order::selectRaw('status, count(*) as count')
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
                'user_id' => current_user()->id,
                'order_number' => strtoupper(Str::ulid()),
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
            $productIds = collect($cartItems)->pluck('product_id')->toArray();
            $products = Product::with('category')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');
            // Create order items + decrement stock
            foreach ($cartItems as $item) {
                $product = $products[$item['product_id']];
                $category = $product->category;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'price' => $item['price'],
                    'discount_price' => $item['discount_price'] ?? null,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['quantity'] * ($item['discount_price'] ?? $item['price']),
                ]);
            }

            // Clear cart after order (also clears applied coupon)
            $this->cartService->clear();

            DB::afterCommit(function () use ($order) {
                OrderPlaced::dispatch($order);

                // ── Post-checkout Job Chain ─────────────────────────
                // Sequenced steps: each runs only if the previous succeeded.
                // If any step fails, the catch() rolls back the order.
                $chain = [
                    new ChargePayment($order),
                    new ReserveStock($order),
                ];

                // unless() — skip invoice generation for COD orders
                // (COD invoices are generated on delivery confirmation instead)
                if ($order->payment_method !== 'cod') {
                    $chain[] = new GenerateInvoicePdf($order);
                }

                $chain[] = new SendOrderConfirmation($order);

                Bus::chain($chain)
                    ->catch(function (\Throwable $e) use ($order) {
                        Log::channel('order')->error("🔴 Checkout chain FAILED for order #{$order->order_number}: {$e->getMessage()}");

                        // Roll back: mark order as failed so admin can investigate
                        $order->update([
                            'status' => 'cancelled',
                            'payment_status' => $order->payment_status === 'paid' ? 'refund_pending' : $order->payment_status,
                        ]);
                    })
                    ->dispatch();
            });

            return $order;
        }, attempts: 3);
    }

    public function cancelOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {

            $order->update(['status' => 'cancelled']);

            DB::afterCommit(function () use ($order) {

                ReleaseStock::dispatch($order);
                Log::channel('order')->info('Order Cancelled', [
                    'order_number' => $order->order_number,
                    'canceled_by' => current_user()?->id ?? 'system',
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

        if ($status === 'shipped' && $order->user_id) {
            $order->user->notify((new OrderShipped($order))->locale($order->user->preferredLocale()));
        } else {
            Notification::route('mail', [
                $order->shipping_email => $order->shipping_name,
            ])->notify(new OrderShipped($order));
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
            'ordersByStatus' => $this->getOwnOrdersStatusCounts($userId),
        ];
    }

    public function getOwnOrdersStatusCounts(int $userId)
    {
        $countsCacheKey = 'orders.own.status_counts';

        return Cache::tags(['orders'])->remember($countsCacheKey, now()->addMinutes(10), function () use ($userId) {
            return Order::selectRaw('status, count(*) as count')
                ->where('user_id', $userId)
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        });
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
