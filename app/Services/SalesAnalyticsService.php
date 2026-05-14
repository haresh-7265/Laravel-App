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

    public function getTopProducts(int $year, int $limit = 10): Collection
    {
        return OrderItem::with('product')
            ->whereHas('order', fn($q) => $q
                ->where('status', 'delivered')
                ->whereYear('created_at', $year))
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

    public function getTopCustomers(int $year, int $limit = 10): Collection
    {
        return Order::with('user')
            ->where('status', 'delivered')
            ->whereYear('created_at', $year)
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

    public function getSalesByCategory(int $year): Collection
    {
        return OrderItem::with('product.category')
            ->whereHas('order', fn($q) => $q
                ->where('status', 'delivered')
                ->whereYear('created_at', $year))
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

    public function getSummaryMetrics(int $year): array
    {
        $metrics = Order::where('status', 'delivered')
        ->whereYear('created_at', $year)
        ->selectRaw('
            SUM(total)                    as total_revenue,
            COUNT(*)                      as total_orders,
            ROUND(AVG(total), 2)          as avg_order_value,
            COUNT(DISTINCT user_id)       as unique_customers
        ')
        ->first();
        
        return [
            'totalRevenue'     => $metrics->total_revenue,
            'totalOrders'      => $metrics->total_orders,
            'avgOrderValue'    => $metrics->avg_order_value,
            'uniqueCustomers'  => $metrics->unique_customers,
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