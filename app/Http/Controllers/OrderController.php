<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Services\OrderService;
use Arr;
use Illuminate\Database\DeadlockException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    // Show checkout page
    public function checkout()
    {
        return view('orders.checkout');
    }

    // Place order from cart
    public function store(StoreCheckoutRequest $request)
    {

        try {

            $order = $this->orderService->placeOrder($request->validated());

            Mail::to($order->shipping_email, $order->shipping_name)->locale($order->user?->preferredLocale())->later(now()->addMinutes(5), new OrderConfirmation($order));

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Order placed successfully!');

        } catch (DeadlockException $e) {
            return back()->with('error', 'Too busy, try again');
        }
    }

    // Customer order listing
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['search', 'status', 'payment_status', 'date']);
        $cursor = $request->input('cursor');
        $perPage = min((int) $request->input('perPage', 20), 100);

        $orders = $this->orderService->getFilteredOrders(
            filters: $filters,
            role: $user?->role ?? 'guest',
            userId: $user?->id,
            cursor: $cursor,
            perPage: $perPage,
        );

        extract($this->orderService->getCustomerOrderStats($user?->id));
    
        return view('orders.index', compact('stats', 'topProducts', 'ordersByStatus', 'orders'));
    }

    // Customer order detail
    public function show(Order $order)
    {
        // Ensure customer can only see their own orders
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load('items.product');

        $signedUrl = $this->generateSignedUrl($order);

        return view('orders.show', compact('order', 'signedUrl'));
    }

    // cancel the order

    public function cancel(Order $order)
    {
        // Must own the order
        abort_if($order->user_id !== auth()->id(), 403);

        // Only cancellable before shipping
        abort_if(! in_array($order->status, ['pending', 'processing']), 403, 'Order cannot be cancelled at this stage.');

        $this->orderService->cancelOrder($order);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Your order has been cancelled');
    }

    public function downloadInvoice(Request $request, Order $order)
    {

        // validate signature — abort if expired or tampered
        if (! $request->hasValidSignature()) {
            abort(403, 'Link expired or invalid.');
        }

        $path = $order->invoice_path;

        // check file exists
        if ($path && ! Storage::disk('public')->exists($path)) {
            return back()->with('error', 'Invoice not found. Please contact support.');
        }

        return Storage::disk('public')->download(
            $path,
            'Invoice-'.$order->order_number.'.pdf'
        );
    }

    private function generateSignedUrl(Order $order)
    {
        // verify order belongs to auth user
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $signedUrl = URL::temporarySignedRoute(
            'invoices.download',
            now()->addMinutes(10),
            ['order' => $order]
        );

        return $signedUrl;
    }
}
