<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    /**
     * Cache key for the authenticated user's unread notification count.
     */
    private function cacheKey(): string
    {
        return 'user.' . current_user()->id . '.unread_notifications_count';
    }

    /**
     * Paginated list of all notifications.
     */
    public function index(Request $request)
    {
        $notifications = current_user()
            ->notifications()
            ->paginate(10);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($notifications);
        }

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Return the cached unread count (JSON — consumed by the bell badge).
     */
    public function unread(Request $request)
    {
        $count = Cache::remember($this->cacheKey(), 60, function () use ($request) {
            return current_user()->unreadNotifications()->count();
        });

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a single notification as read and redirect to the related resource.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = current_user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        Cache::forget($this->cacheKey());

        // Determine redirect URL from notification data
        $url = $this->resolveRedirectUrl($notification);

        return redirect($url);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        current_user()->unreadNotifications->markAsRead();

        Cache::forget($this->cacheKey());

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Resolve a redirect URL from the notification payload.
     */
    private function resolveRedirectUrl($notification): string
    {
        $data = $notification->data;

        // OrderShipped / NewOrderReceived or any notification with an order_id
        if (!empty($data['order_id'])) {
            $order = Order::find($data['order_id']);

            if ($order) {
                // Admin sees admin order detail, customer sees customer order detail
                if (is_admin()) {
                    return route('admin.orders.show', $order);
                }

                return route('orders.show', $order);
            }
        }

        // ProductLowStock or any notification with a product_id
        if (!empty($data['product_id'])) {
            $product = Product::find($data['product_id']);

            if ($product) {
                return route('products.edit', $product);
            }
        }

        // Fallback: notification index page
        return route('notifications.index');
    }
}
