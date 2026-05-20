<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request)
    {
        $filters = [
            'date' => $request->query('date', ''),
            'status' => $request->query('status', ''),
            'search' => $request->query('search', ''),
            'payment_status' => $request->query('payment_status', ''),
        ];

        // Count per status for stats row
        $allCounts = $this->orderService->getOrderStatusCounts();

        $orders = $this->orderService->getFilteredProducts($filters);

        return view('admin.orders.index', compact('orders', 'allCounts'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'user');
        $productNames = Arr::pluck($order->items->toArray(), 'product_name');
        $summary = 'Items: '.implode(', ', $productNames);

        return view('admin.orders.show', compact('order', 'summary'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

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
        $request->validate([
            'payment_status' => 'required|in:paid,unpaid',
        ]);

        $order->update(['payment_status' => $request->payment_status]);

        return back()->with('success', 'Payment status updated successfully.');
    }

    public function invoices()
    {
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
}
