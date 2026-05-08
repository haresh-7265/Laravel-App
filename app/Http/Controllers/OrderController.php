<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmation;

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

        Mail::to($order->shipping_email)->send(new OrderConfirmation($order));

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

        $signedUrl = $this->generateSignedUrl($order);

        return view('orders.show', compact('order', 'signedUrl'));
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

    public function downloadInvoice(Request $request, Order $order)
    {

        // validate signature — abort if expired or tampered
        if (!$request->hasValidSignature()) {
            abort(403, 'Link expired or invalid.');
        }


        $path = $order->invoice_path;

        // check file exists
        if ($path && !Storage::disk('public')->exists($path)) {
            return back()->with('danger', 'Invoice not found. Please contact support.');
        }

        return Storage::disk('public')->download(
            $path,
            'Invoice-' . $order->order_number . '.pdf'
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