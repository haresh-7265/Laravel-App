<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Gate;

class AdminDashboardController extends Controller
{
    public function index()
    {
        Gate::authorize('view-admin-dashboard');

        $stats = Cache::tags(['admin', 'products', 'products.list', 'orders', 'users'])->remember('admin.dashboard.stats', now()->addMinutes(10), function () {
            [$todayOrders, $monthRevenue, $newCustomers, $lowStockCount] = Concurrency::run([
                fn() => Order::whereDate('created_at', today())->count(),
                fn() => Order::where('status', 'delivered')
                    ->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    ])
                    ->sum('total'),
                fn() => User::customers()
                    ->whereDate('created_at', today())
                    ->count(),
                fn() => Product::where('stock', '<=', 5)->count(),
            ]);

            return [
                'today_orders' => $todayOrders,
                'monthly_revenue' => $monthRevenue,
                'new_customers' => $newCustomers,
                'low_stock_count' => $lowStockCount,
            ];
        });

        $recentOrders = Cache::tags(['admin', 'orders'])->remember('admin.dashboard.recent_orders', now()->addMinutes(10), function () {
            return Order::with('user')
                ->latest()
                ->take(5)
                ->get();
        });

        $lowStockProducts = Cache::tags(['admin', 'products.list', 'products'])->remember('admin.dashboard.low_stock', now()->addMinutes(10), function () {
            return Product::where('stock', '<=', 5)
                ->orderBy('stock')
                ->take(5)
                ->get();
        });

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockProducts'));
    }
}
