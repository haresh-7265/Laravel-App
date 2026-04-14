<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::tags(['admin', 'products'])->remember('admin.dashboard.stats', now()->addMinutes(10), function () {
            [$todayOrders, $monthRevenue, $newCustomers, $lowStockCount] = Concurrency::run([
                fn () => Order::whereDate('created_at', today())->count(),
                fn () => Order::where('status', 'delivered')
                            ->whereYear('created_at', now()->year)
                            ->whereMonth('created_at', now()->month)
                            ->sum('total'),
                fn () => User::where('role', 'customer')
                            ->whereDate('created_at', today())
                            ->count(),
                fn () => Product::where('stock', '<=', 5)->count(),
            ]);

            return [
                'today_orders'    => $todayOrders,
                'monthly_revenue' => $monthRevenue,
                'new_customers'   => $newCustomers,
                'low_stock_count' => $lowStockCount,
            ];
        });

        $recentOrders = Cache::tags(['admin', 'products'])->remember('admin.dashboard.recent_orders', now()->addMinutes(10), function () {
            return Order::with('user')
                ->latest()
                ->take(5)
                ->get();
        });

        $lowStockProducts = Cache::tags(['admin', 'products'])->remember('admin.dashboard.low_stock', now()->addMinutes(10), function () {
            return Product::where('stock', '<=', 5)
                ->orderBy('stock')
                ->take(5)
                ->get();
        });

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockProducts'));
    }
}
