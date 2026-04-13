<?php
namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use function Illuminate\Support\years;

class SalesAnalyticsService
{
    public function getMonthlySales(int $year): Collection
    {
        return Order::whereYear('created_at', $year)
            ->where('status', 'delivered')
            ->get()
            ->groupBy(fn($o) => $o->created_at->format('M'))
            ->map(fn($group) => (object) [
                'revenue' => $group->sum('total'),
                'orders' => $group->count(),
                'avg' => round($group->avg('total'), 2),
            ]);
    }

    public function getTopProducts(int $limit = 10): Collection
    {
        return OrderItem::with('order')
            ->with('product')
            ->get()
            ->filter(fn($item) => $item->order->status === 'delivered')
            ->groupBy('product_id')
            ->map(fn($item) => (object) [
                'product' => $item->first()->product,
                'total_sold' => $item->sum('quantity'),
                'revenue' => $item->sum('subtotal')
            ])
            ->sortByDesc('total_sold')
            ->take($limit)
            ->values();
    }

    public function getTopCustomers(int $limit = 10): Collection
    {
        return Order::with('user')
            ->where('status', 'delivered')
            ->get()
            ->groupBy('user_id')
            ->map(fn($orders) => (object) [
                'customer' => $orders->first()->user,
                'total_spent' => $orders->sum('total'),
                'order_count' => $orders->count(),
                'avg_order' => round($orders->sum('total') / $orders->count(), 2),
            ])
            ->sortByDesc('total_spent')
            ->take($limit)
            ->values();
    }

    public function getSalesByCategory(): Collection
    {
        return OrderItem::with('product.category')
            ->get()
            ->groupBy(fn($item) => $item->product->category->name)
            ->map(fn($item) => (object) [
                'category' => $item->first()->product->category,
                'total_quantity' => $item->sum('quantity'),
                'total_revenue' => $item->sum(fn($i) => $i->price * $i->quantity),
                'total_orders' => $item->pluck('order_id')->unique()->count(),
            ])
            ->sortByDesc('total_revenue')
            ->values();
    }

    public function getSummaryMetrics(): array
    {
        return [
            'totalRevenue' => Order::where('status', 'delivered')->sum('total'),
            'totalOrders' => Order::where('status', 'delivered')->count(),
            'avgOrderValue' => round(Order::where('status', 'delivered')->avg('total'), 2),
            'uniqueCustomers' => Order::where('status', 'delivered')->distinct('user_id')->count('user_id'),
        ];
    }

    public function getAvailableYears(): Collection
    {
        $years = Order::all()
            ->pluck('created_at')
            ->map(fn($date) => $date->year)
            ->unique()
            ->sortDesc()
            ->values();

        return $years;
    }
}