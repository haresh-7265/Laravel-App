<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\SalesAnalyticsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesAnalyticsController extends Controller
{
    public function __construct(private SalesAnalyticsService $salesAnalyticsService)
    {
    }
    public function index(Request $request)
    {
        $selectedYear = $request->input('year', now()->year);

        // Monthly sales 
        $monthlySales = $this->salesAnalyticsService->getMonthlySales($selectedYear);

        // Top 10 products 
        $topProducts = $this->salesAnalyticsService->getTopProducts();

        // Top 10 customers 
        $topCustomers = $this->salesAnalyticsService->getTopCustomers();

        // Sales by category
        $byCategory = $this->salesAnalyticsService->getSalesByCategory();

        // Summary metrics
        extract($this->salesAnalyticsService->getSummaryMetrics());

        // Available years
        $years = $this->salesAnalyticsService->getAvailableYears();

        return view('admin.sales-analytics', compact(
            'monthlySales',
            'topProducts',
            'topCustomers',
            'byCategory',
            'totalRevenue',
            'totalOrders',
            'avgOrderValue',
            'uniqueCustomers',
            'selectedYear',
            'years'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $type = $request->input('type', 'monthly');
        $year = $request->input('year', now()->year);

        $filename = "{$type}_report.csv";

        return response()->streamDownload(function () use ($type, $year) {
            $handle = fopen('php://output', 'w');

            if ($type === 'monthly') {
                fputcsv($handle, ['Month', 'Revenue', 'Orders', 'Avg Order Value']);
                $rows = Order::whereYear('created_at', $year)->where('status', 'completed')
                    ->get()->groupBy(fn($o) => $o->created_at->format('M'))
                    ->map(fn($g) => [$g->sum('total'), $g->count(), round($g->avg('total'), 2)]);
                foreach ($rows as $month => $data) {
                    fputcsv($handle, [$month, ...$data]);
                }

            } elseif ($type === 'products') {
                fputcsv($handle, ['Rank', 'Product', 'Qty Sold', 'Revenue', 'Category']);
                $rows = OrderItem::with('product.category')
                    ->selectRaw('product_id, SUM(quantity) as qty_sold, SUM(subtotal) as revenue')
                    ->groupBy('product_id')->orderByDesc('qty_sold')->take(10)->get();
                foreach ($rows as $i => $r) {
                    fputcsv($handle, [$i + 1, $r->product->name, $r->qty_sold, $r->revenue, $r->product->category->name ?? '-']);
                }

            } elseif ($type === 'customers') {
                fputcsv($handle, ['Rank', 'Customer', 'Email', 'Orders', 'Total Spent']);
                $rows = Order::with('user')->where('status', 'completed')
                    ->selectRaw('user_id, COUNT(*) as total_orders, SUM(total) as total_spent')
                    ->groupBy('user_id')->orderByDesc('total_spent')->take(10)->get();
                foreach ($rows as $i => $r) {
                    fputcsv($handle, [$i + 1, $r->user->name, $r->user->email, $r->total_orders, $r->total_spent]);
                }

            } elseif ($type === 'category') {
                fputcsv($handle, ['Category', 'Revenue', 'Orders']);
                $rows = OrderItem::with('product.category')
                    ->selectRaw('products.category_id, SUM(order_items.subtotal) as revenue, COUNT(DISTINCT order_items.order_id) as orders')
                    ->join('products', 'products.id', '=', 'order_items.product_id')
                    ->groupBy('products.category_id')->orderByDesc('revenue')->get();
                foreach ($rows as $r) {
                    fputcsv($handle, [$r->product->category->name ?? '-', $r->revenue, $r->orders]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}