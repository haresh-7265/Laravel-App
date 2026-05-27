<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SalesAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesAnalyticsController extends Controller
{
    public function __construct(private SalesAnalyticsService $salesAnalyticsService)
    {
    }
    public function index(Request $request)
    {
        Gate::authorize('view-analytics');

        $selectedYear = $request->input('year', now()->year);

        // Monthly sales 
        $monthlySales = $this->salesAnalyticsService->getMonthlySales($selectedYear);

        // Top 10 products 
        $topProducts = $this->salesAnalyticsService->getTopProducts($selectedYear);

        // Top 10 customers 
        $topCustomers = $this->salesAnalyticsService->getTopCustomers($selectedYear);

        // Sales by category
        $byCategory = $this->salesAnalyticsService->getSalesByCategory($selectedYear);

        // Summary metrics
        extract($this->salesAnalyticsService->getSummaryMetrics($selectedYear));

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
        Gate::authorize('view-analytics');

        $type = $request->input('type', 'monthly');
        $year = $request->input('year', now()->year);

        $filename = "sales_{$type}_{$year}_report.csv";

        return response()->streamDownload(function () use ($type, $year) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Year', $year]);
            fputcsv($handle, []);

            if ($type === 'monthly') {
                fputcsv($handle, ['Month', 'Revenue', 'Orders', 'Avg Order Value']);
                $rows = $this->salesAnalyticsService->getMonthlySales($year);
                foreach ($rows as $month => $data) {
                    fputcsv($handle, [
                        $month,
                        format_price($data->revenue),
                        $data->orders,
                        format_price($data->avg),
                    ]);
                }

            } elseif ($type === 'products') {
                fputcsv($handle, ['Rank', 'Product', 'Qty Sold', 'Revenue', 'Category']);
                $rows = $this->salesAnalyticsService->getTopProducts($year);
                foreach ($rows as $i => $r) {
                    fputcsv($handle, [$i + 1, str($r->product_name)->limit(20), $r->total_sold, format_price($r->revenue), $r->category_name ?? '-']);
                }

            } elseif ($type === 'customers') {
                fputcsv($handle, ['Rank', 'Customer', 'Email', 'Orders', 'Total Spent']);
                $rows = $this->salesAnalyticsService->getTopCustomers($year);
                foreach ($rows as $i => $r) {
                    fputcsv($handle, [$i + 1, $r->customer_name, $r->customer_email, $r->order_count, format_price($r->total_spent)]);
                }

            } elseif ($type === 'category') {
                fputcsv($handle, ['Category', 'Revenue', 'Orders']);
                $rows = $this->salesAnalyticsService->getSalesByCategory($year);
                foreach ($rows as $r) {
                    fputcsv($handle, [$r->category_name ?? '-', format_price($r->total_revenue), $r->total_quantity]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
