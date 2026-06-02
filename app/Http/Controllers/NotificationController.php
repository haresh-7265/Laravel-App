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
        $user = current_user();
        $modelType = strtolower(class_basename($user));
        return $modelType . '.' . $user->id . '.unread_notifications_count';
    }

    /**
     * Paginated or full list of all notifications.
     */
    public function index(Request $request)
    {
        $user = current_user();

        // If AJAX or JSON request, handle filters, search, sorting and return formatted items
        if ($request->ajax() || $request->wantsJson()) {
            $query = $user->notifications();

            // Status filter
            if ($request->status === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->status === 'read') {
                $query->whereNotNull('read_at');
            }

            // Unread first, then read, then newest first
            $query->orderByRaw('read_at IS NULL DESC')->orderBy('created_at', 'desc');

            // Limit for dropdown vs paginate for panel
            if ($request->has('limit')) {
                $notifications = $query->limit((int)$request->limit)->get();
                $formatted = $notifications->map(fn($n) => $this->formatNotification($n));
                
                return response()->json([
                    'data' => $formatted,
                    'stats' => $this->getStats($user)
                ]);
            }

            // Normal paginated panel view
            $notifications = $query->paginate(15);
            $formatted = collect($notifications->items())->map(fn($n) => $this->formatNotification($n));

            return response()->json([
                'data' => $formatted,
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'stats' => $this->getStats($user)
            ]);
        }

        // Standard request: load view
        return view('notifications.index');
    }

    /**
     * Return the cached unread count.
     */
    public function unread(Request $request)
    {
        $count = Cache::remember($this->cacheKey(), 60, function () {
            return current_user()->unreadNotifications()->count();
        });

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = current_user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        Cache::forget($this->cacheKey());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $this->getStats($user),
                'redirect_url' => $this->resolveRedirectUrl($notification)
            ]);
        }

        $url = $this->resolveRedirectUrl($notification);
        return redirect($url);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $user = current_user();
        $user->unreadNotifications->markAsRead();

        Cache::forget($this->cacheKey());

        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $this->getStats($user)
            ]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Get real-time stats count.
     */
    private function getStats($user): array
    {
        return [
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'read' => $user->readNotifications()->count(),
        ];
    }

    /**
     * Format notification for UI.
     */
    private function formatNotification($n): array
    {
        $data = $n->data;
        $type = $n->type;
        
        // Default values
        $category = $data['category'] ?? 'System';
        $theme = 'blue';
        $icon = 'bi-bell';
        $description = 'System notification update.';
        
        // Map known categories to themes and icons
        $categoryLower = strtolower($category);
        if ($categoryLower === 'order') {
            $theme = 'blue';
            $icon = 'bi-bag';
            $description = 'Order update notification.';
        } elseif ($categoryLower === 'payment') {
            $theme = 'green';
            $icon = 'bi-credit-card';
            $description = 'Payment status update.';
        } elseif ($categoryLower === 'alert') {
            $theme = 'amber';
            $icon = 'bi-exclamation-triangle';
            $description = 'Warning or action required alert.';
        } elseif ($categoryLower === 'system') {
            $theme = 'teal';
            $icon = 'bi-arrow-repeat';
            $description = 'System level event notification.';
        } elseif ($categoryLower === 'social') {
            $theme = 'purple';
            $icon = 'bi-chat-left-text';
            $description = 'Social or community notification.';
        } elseif ($categoryLower === 'report') {
            $theme = 'blue';
            $icon = 'bi-file-earmark-bar-graph';
            $description = 'Activity or performance report.';
        } elseif ($categoryLower === 'security') {
            $theme = 'red';
            $icon = 'bi-shield-exclamation';
            $description = 'Security alert notification.';
        }
        
        // Categorization fallback based on class name or data properties
        if (str_contains($type, 'OrderReceived') || ($data['icon'] ?? '') === 'order') {
            $category = $data['category'] ?? 'Order';
            $theme = 'blue';
            $icon = 'bi-bag';
            $description = 'A new order has been received and is awaiting fulfillment.';
        } elseif (str_contains($type, 'OrderShipped') || ($data['icon'] ?? '') === 'truck') {
            $category = $data['category'] ?? 'Order';
            $theme = 'green';
            $icon = 'bi-truck';
            $description = 'Order has been dispatched and is on the way.';
        } elseif (str_contains($type, 'LowStock') || ($data['icon'] ?? '') === 'alert') {
            $category = $data['category'] ?? 'Alert';
            $theme = 'amber';
            $icon = 'bi-exclamation-triangle';
            $description = 'Product inventory level fell below safety threshold.';
        } elseif (str_contains($type, 'Restocked')) {
            $category = $data['category'] ?? 'System';
            $theme = 'teal';
            $icon = 'bi-arrow-repeat';
            $description = 'Inventory restocked to standard capacity.';
        } elseif (str_contains($type, 'SupportTicket')) {
            $category = $data['category'] ?? 'Social';
            $theme = 'purple';
            $icon = 'bi-chat-left-text';
            $description = 'A new support ticket activity requires attention.';
        } elseif (str_contains($type, 'Failed') || str_contains($type, 'Error')) {
            $category = $data['category'] ?? 'Security';
            $theme = 'red';
            $icon = 'bi-shield-exclamation';
            $description = 'An system level alert occurred during background operations.';
        }

        // Allow explicit custom overrides from database payload
        if (!empty($data['icon'])) {
            $icon = $data['icon'];
        }
        if (!empty($data['theme'])) {
            $theme = $data['theme'];
        }
        if (!empty($data['description'])) {
            $description = $data['description'];
        }
        
        $message = $data['message'] ?? 'Notification alert.';
        
        // Format message by wrapping numbers, order numbers, pricing, and actions in bold tags.
        $formattedMessage = preg_replace(
            '/([#][a-zA-Z0-9_-]+|\$\d+(?:\.\d{2})?|placed|shipped|failed|low stock|restocked|admin|error)/i',
            '<strong>$1</strong>',
            $message
        );
        
        if (!empty($data['customer_name'])) {
            $formattedMessage = str_ireplace($data['customer_name'], '<strong>' . e($data['customer_name']) . '</strong>', $formattedMessage);
        }
        if (!empty($data['order_number'])) {
            $formattedMessage = str_ireplace($data['order_number'], '<strong>' . e($data['order_number']) . '</strong>', $formattedMessage);
        }

        $createdAt = $n->created_at;
        if ($createdAt->isToday()) {
            $group = 'Today';
        } elseif ($createdAt->isYesterday()) {
            $group = 'Yesterday';
        } else {
            $group = 'Earlier';
        }
        
        // Circular Avatar colors (blue, green, amber, red, purple, teal)
        $themeColors = [
            'blue'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-600',   'tag' => 'bg-blue-100 text-blue-800 border-transparent'],
            'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-600',  'tag' => 'bg-green-100 text-green-800 border-transparent'],
            'amber'  => ['bg' => 'bg-amber-100',  'text' => 'text-amber-600',  'tag' => 'bg-amber-100 text-amber-800 border-transparent'],
            'red'    => ['bg' => 'bg-red-100',    'text' => 'text-red-600',    'tag' => 'bg-red-100 text-red-800 border-transparent'],
            'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'tag' => 'bg-purple-100 text-purple-800 border-transparent'],
            'teal'   => ['bg' => 'bg-teal-100',   'text' => 'text-teal-600',   'tag' => 'bg-teal-100 text-teal-800 border-transparent']
        ];
        
        $colors = $themeColors[$theme] ?? $themeColors['blue'];
        
        return [
            'id' => $n->id,
            'message' => $formattedMessage,
            'description' => $description,
            'category' => $category,
            'category_tag_class' => $colors['tag'],
            'avatar_bg' => $colors['bg'],
            'avatar_text' => $colors['text'],
            'icon' => $icon,
            'time' => $createdAt->diffForHumans(),
            'group' => $group,
            'is_unread' => is_null($n->read_at),
        ];
    }

    /**
     * Resolve a redirect URL from the notification payload.
     */
    private function resolveRedirectUrl($notification): string
    {
        $data = $notification->data;

        // Prioritize explicit redirect_url if present
        if (!empty($data['redirect_url'])) {
            return $data['redirect_url'];
        }

        if (!empty($data['order_id'])) {
            $order = Order::find($data['order_id']);

            if ($order) {
                if (is_admin()) {
                    return route('admin.orders.show', $order);
                }

                return route('orders.show', $order);
            }
        }

        if (!empty($data['product_id'])) {
            $product = Product::find($data['product_id']);

            if ($product) {
                return route('products.edit', $product);
            }
        }

        return route('notifications.index');
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id)
    {
        $user = current_user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        Cache::forget($this->cacheKey());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $this->getStats($user)
            ]);
        }

        return back()->with('success', 'Notification deleted successfully.');
    }
}
