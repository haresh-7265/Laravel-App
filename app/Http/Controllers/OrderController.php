<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    // Show checkout page
    public function checkout()
    {
        return view('orders.checkout');
    }

    // Place order from cart
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|size:10',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'pincode' => 'required|string|size:6',
        ]);

        $order = $this->orderService->placeOrder($request->all());


        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order placed successfully!');
    }

    // Customer order listing
    public function index()
    {
        $stats = $this->orderService->getCustomerOrdersAndStats(auth()->id());

        return view('orders.index', $stats);
    }

    // Customer order detail
    public function show(Order $order)
    {
        // Ensure customer can only see their own orders
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load('items.product');

        return view('orders.show', compact('order'));
    }

    // cancel the order

    public function cancel(Order $order)
    {
        // Must own the order
        abort_if($order->user_id !== auth()->id(), 403);

        // Only cancellable before shipping
        abort_if(!in_array($order->status, ['pending', 'processing']), 403, 'Order cannot be cancelled at this stage.');

        $this->orderService->cancelOrder($order);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Your order has been cancelled');
    }
}