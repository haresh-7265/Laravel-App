<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }
    public function index(Request $request)
    {
        // Count per status for stats row
        $allCounts = Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $orders = Order::with(['user', 'items.product'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas(
                        'user',
                        fn($q) =>
                        $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('email', 'like', '%' . $request->search . '%')
                    );
            })
            ->when(
                $request->status,
                fn($q) =>
                $q->where('status', $request->status)
            )
            ->when(
                $request->payment_status,
                fn($q) =>
                $q->where('payment_status', $request->payment_status)
            )
            ->when(
                $request->date,
                fn($q) =>
                $q->whereDate('created_at', $request->date)
            )
            ->latest()
            ->get();

        return view('admin.orders.index', compact('orders', 'allCounts'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'user');
        return view('admin.orders.show', compact('order'));
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

        if (!in_array($request->status, $allowed)) {
            return back()->with('warning', "Cannot move order from {$order->status} to {$request->status}.");
        }

        // Route cancellation through the service (restores stock)
        if ($request->status === 'cancelled') {
            $this->orderService->cancelOrder($order);
        } else {
            $order->update(['status' => $request->status]);
        }

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
}