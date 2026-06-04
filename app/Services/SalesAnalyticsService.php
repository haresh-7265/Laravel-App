<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsService
{
    public function getMonthlySales(int $year): Collection
    {
        return DB::table('orders')
            ->select([
                DB::raw('DATE_FORMAT(created_at, "%b") as month'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(id) as orders'),
                DB::raw('ROUND(AVG(total), 2) as avg'),
            ])
            ->whereYear('created_at', $year)
            ->where('status', 'delivered')
            ->groupBy(
                DB::raw('DATE_FORMAT(created_at, "%b")'),
                DB::raw('MONTH(created_at)')
            )
            ->orderBy(DB::raw('MONTH(created_at)'), 'asc')
            ->get()
            ->keyBy('month');
    }

    public function getTopProducts(int $year, int $limit = 10): Collection
    {
        return DB::table('order_items')
            ->select([
                'order_items.product_id',
                'order_items.product_name',
                'order_items.category_id',    // ← from order_items
                'order_items.category_name',  // ← from order_items
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.subtotal) as revenue'),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'delivered')
            ->whereYear('orders.created_at', $year)
            ->groupBy(
                'order_items.product_id',
                'order_items.product_name',
                'order_items.category_id',
                'order_items.category_name',
            )
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    public function getTopCustomers(int $year, int $limit = 10): Collection
    {
        return DB::table('orders')
            ->select([
                'orders.user_id',
                'users.name as customer_name',
                'users.email as customer_email',
                DB::raw('SUM(orders.total) as total_spent'),
                DB::raw('COUNT(orders.id) as order_count'),
            ])
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.status', 'delivered')
            ->whereYear('orders.created_at', $year)
            ->groupBy(
                'orders.user_id',
                'users.name',
                'users.email'
            )
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }

    public function getSalesByCategory(int $year): Collection
    {
        return DB::table('order_items')
            ->select([
                'order_items.category_id',
                'order_items.category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as total_orders'),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'delivered')
            ->whereYear('orders.created_at', $year)
            ->groupBy(
                'order_items.category_id',
                'order_items.category_name',
            )
            ->orderByDesc('total_revenue')
            ->get();
    }

    public function getSummaryMetrics(int $year): array
    {
        $metrics = DB::table('orders')
            ->select([
                DB::raw('SUM(total)                  as total_revenue'),
                DB::raw('COUNT(*)                    as total_orders'),
                DB::raw('ROUND(AVG(total), 2)        as avg_order_value'),
                DB::raw('COUNT(DISTINCT user_id)     as unique_customers'),
            ])
            ->where('status', 'delivered')
            ->whereYear('created_at', $year)
            ->first();

        return [
            'totalRevenue' => $metrics->total_revenue,
            'totalOrders' => $metrics->total_orders,
            'avgOrderValue' => $metrics->avg_order_value,
            'uniqueCustomers' => $metrics->unique_customers,
        ];
    }

    public function getAvailableYears(): Collection
    {
        $years = Order::all()
            ->pluck('created_at')
            ->map(fn ($date) => $date->year)
            ->unique()
            ->sortDesc()
            ->values();

        return $years;
    }

    /**
     * DB::select with ? bindings for a complex aggregation
     * Raw SQL is necessary here if we needed complex window functions, CTEs, or highly
     * specific DB features not supported out of the box by Laravel's Query Builder.
     */
    public function getCustomerAggregation(int $userId): array
    {
        $result = DB::select('
            SELECT 
                COUNT(id) as total_orders,
                SUM(total) as lifetime_value,
                MAX(created_at) as last_order_date
            FROM orders 
            WHERE user_id = ? AND status = ?
        ', [$userId, 'delivered']);

        return (array) ($result[0] ?? []);
    }

    /**
     * Named bindings (:user_id)
     * Query Builder is preferable for simple SELECTs, eager loading relations, and dynamic WHERE clauses.
     */
    public function getCustomerAggregationNamed(int $userId): array
    {
        $result = DB::select('
            SELECT 
                COUNT(id) as total_orders,
                SUM(total) as lifetime_value,
                MAX(created_at) as last_order_date
            FROM orders 
            WHERE user_id = :user_id AND status = :status
        ', [
            'user_id' => $userId,
            'status' => 'delivered',
        ]);

        return (array) ($result[0] ?? []);
    }

    /**
     * Use DB::statement() for a one-off DDL operation (e.g. TRUNCATE TABLE)
     */
    public function truncateOrderAnalytics(): void
    {
        // Example of a one-off DDL operation
        DB::connection('analytics')->statement('TRUNCATE TABLE order_analytics'); // Example truncate
    }
}
