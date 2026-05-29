<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Order;
use App\Services\OrderService;
use Arr;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\DeadlockException;
use Illuminate\Http\Request;
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
        $this->authorize('viewAny', Order::class);

        $userId = current_user()->id ;
        $filters = $request->only(['search', 'status', 'payment_status', 'date']);
        $cursor = $request->input('cursor');
        $perPage = min((int) $request->input('perPage', 20), 100);

        $orders = $this->orderService->getFilteredOrders(
            filters: $filters,
            role: is_admin() ? 'admin' : 'customer',
            userId: $userId,
            cursor: $cursor,
            perPage: $perPage,
        );

        extract($this->orderService->getCustomerOrderStats($userId));
    
        return view('orders.index', compact('stats', 'topProducts', 'ordersByStatus', 'orders'));
    }

    // Customer order detail
    public function show(Order $order)
    {
        // Ensure customer can only see their own orders
        $this->authorize('view', $order);

        $order->load('items.product');

        $signedUrl = $this->generateSignedUrl($order);

        // Generate temporary encrypted shared payload (valid for 2 hours)
        // json_encode avoids PHP object serialization/deserialization risks
        $jsonData = json_encode([
            'order_id' => $order->id,
            'expires_at' => now()->addHours(2)->timestamp,
        ]);
        $payload = \Crypt::encrypt($jsonData, false);
        $sharedUrl = route('shared-invoice.download', ['payload' => $payload]);

        return view('orders.show', compact('order', 'signedUrl', 'sharedUrl'));
    }

    // cancel the order

    public function cancel(Order $order)
    {
        // Must own the order
        $this->authorize('cancel', $order);

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

        $this->authorize('view', $order);

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

    /**
     * Download shared invoice via temporary encrypted payload (Guest access).
     */
    public function downloadSharedInvoice(Request $request)
    {
        $payload = $request->query('payload');

        if (! $payload) {
            abort(400, 'Missing encrypted payload.');
        }

        try {
            // Decrypt the raw string without deserializing PHP objects
            $decrypted = \Crypt::decrypt($payload, false);
            $data = json_decode($decrypted, true);
        } catch (DecryptException $e) {
            abort(403, 'Invalid or tampered payload.');
        }

        // Validate expiration
        if (! isset($data['expires_at']) || now()->timestamp > $data['expires_at']) {
            abort(403, 'This shared link has expired.');
        }

        $order = Order::findOrFail($data['order_id']);
        $path = $order->invoice_path;

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
        $this->authorize('view', $order);

        $signedUrl = URL::temporarySignedRoute(
            'invoices.download',
            now()->addMinutes(10),
            ['order' => $order]
        );

        return $signedUrl;
    }
}
