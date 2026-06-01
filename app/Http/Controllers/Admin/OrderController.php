<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $user = current_user();
        $filters = $request->only(['search', 'status', 'payment_status', 'date']);
        $cursor = $request->input('cursor');
        $perPage = min((int) $request->input('perPage', 20), 100);

        // Count per status for stats row
        $allCounts = $this->orderService->getAllOrdersStatusCounts();

        if (! $request->expectsJson()) {
            return view('admin.orders.index', compact('allCounts'));
        }

        $orders = $this->orderService->getAllOrders(
            filters: $filters,
            user: $user,
            cursor: $cursor,
            perPage: $perPage,
        );

        $nextCursor = $orders->nextCursor()?->encode();
        $hasMore = $orders->hasMorePages();

        return response()->json([
            'data' => $orders->through(fn ($order) => $this->formatOrder($order))->items(),
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ]);

    }

    public function indexApi(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $user = current_user();
        $filters = $request->only(['search', 'status', 'payment_status', 'date']);
        $cursor = $request->input('cursor');
        $perPage = min((int) $request->input('perPage', 20), 100);

        $orders = $this->orderService->getAllOrders(
            filters: $filters,
            user: $user,
            cursor: $cursor,
            perPage: $perPage,
        );

        $nextCursor = $orders->nextCursor()?->encode();
        $hasMore = $orders->hasMorePages();

        return response()->json([
            'data' => $orders->through(fn ($order) => $this->formatOrder($order))->items(),
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ]);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load('items.product', 'user');
        $productNames = Arr::pluck($order->items->toArray(), 'product_name');
        $summary = 'Items: '.implode(', ', $productNames);

        return view('admin.orders.show', compact('order', 'summary'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if($request->status == 'cancelled'){
            $this->authorize('cancel', $order);
        }
        // Statuses that are considered final — no further updates allowed
        $finalStatuses = ['delivered', 'cancelled'];

        if (in_array($order->status, $finalStatuses)) {
            return back()->with('warning', "Order is already {$order->status} and cannot be updated.");
        }

        // Allowed transitions from each status
        $allowedTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
        ];

        $allowed = $allowedTransitions[$order->status] ?? [];

        if (! in_array($request->status, $allowed)) {
            return back()->with('warning', "Cannot move order from {$order->status} to {$request->status}.");
        }

        $this->orderService->updateOrderStatus($order, $request->status);

        return back()->with('success', 'Order status updated successfully.');
    }

    public function updatePayment(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'payment_status' => 'required|in:paid,unpaid',
        ]);

        $order->update(['payment_status' => $request->payment_status]);

        return back()->with('success', 'Payment status updated successfully.');
    }

    public function invoices()
    {
        $this->authorize('viewAny', Order::class);

        $files = Storage::disk('public')->files('invoices');

        $invoices = collect($files)->map(function ($path) {
            return [
                'filename' => basename($path),
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'size' => Storage::disk('public')->size($path),
                'lastModified' => Storage::disk('public')->lastModified($path),
            ];
        })->sortByDesc('lastModified');

        return view('admin.invoices', compact('invoices'));
    }

    private function formatOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->user?->name,
            'customer_email' => $order->user?->email,
            'total' => $order->total,
            'total_formatted' => format_price($order->total),
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'status' => $order->status,
            'created_date' => $order->created_at->isoFormat('LL'),
            'created_time' => $order->created_at->isoFormat('LT'),
            'created_ago' => $order->created_at->diffForHumans(),
            'items' => $order->items->map(fn ($item) => [
                'product_name' => str($item->product_name)->limit(20),
                'image_url' => $item->product?->image
                                    ? asset('storage/'.$item->product->image)
                                    : null,
                'image' => (bool) $item->product?->image,
            ])->toArray(),
        ];
    }
}
