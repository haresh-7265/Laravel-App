<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('admin.dashboard.stats', now()->addMinutes(10), function () {
            return [
                'total_orders'      => Order::count(),
                'total_revenue'     => Order::where('status', 'delivered')->sum('total'),
                'new_customers'     => User::where('role', 'customer')
                                           ->whereDate('created_at', today())
                                           ->count(),
                'pending_orders'    => Order::where('status', 'pending')->count(),
                'low_stock_count'   => Product::where('stock', '<=', 5)->count(),
            ];
        });

        $recentOrders = Cache::remember('admin.dashboard.recent_orders', now()->addMinutes(10), function () {
            return Order::with('user')
                ->latest()
                ->take(5)
                ->get();
        });

        $lowStockProducts = Cache::remember('admin.dashboard.low_stock', now()->addMinutes(10), function () {
            return Product::where('stock', '<=', 5)
                ->orderBy('stock')
                ->take(5)
                ->get();
        });

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockProducts'));
    }
}
