<?php
namespace App\Services\Reports;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    public function generate(): array
    {
        // Total revenue from completed orders using your 'total' column
        $totalRevenue = Order::where('status', 'delivered')->sum('total');
        $orderCount = Order::where('status', 'delivered')->count();

        // Top products using 'product_name' stored directly in order_items
        $topProducts = OrderItem::select(
            'product_name',
            DB::raw('SUM(quantity) as units_sold'),
            DB::raw('SUM(subtotal) as revenue')
        )
            ->groupBy('product_name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'name' => $item->product_name,
                'units_sold' => $item->units_sold,
                'revenue' => number_format($item->revenue, 2),
            ])->toArray();



        // Total discounts given
        $totalDiscount = Order::where('status', 'delivered')->sum('discount');

        return [
            'generated_at' => now()->toDateTimeString(),
            'total_revenue' => number_format($totalRevenue, 2),
            'total_discount' => number_format($totalDiscount, 2),
            'order_count' => $orderCount,
            'top_products' => $topProducts,
        ];
    }

    public function summaryRows(array $data): array
    {
        return [
            ['Total Revenue', config('admin.currency') . $data['total_revenue']],
            ['Total Discounts', config('admin.currency') . $data['total_discount']],
            ['Order Count', $data['order_count']],
            ['Top Product', $data['top_products'][0]['name'] ?? 'N/A'],
        ];
    }

    public function csvRows(array $data): array
    {
        $rows = [];

        // === HEADER ===
        $rows[] = ['Sales Report'];
        $rows[] = ['Generated At', $data['generated_at']];
        $rows[] = [];

        // === SUMMARY ===
        $rows[] = ['Summary'];
        foreach ($this->summaryRows($data) as $row) {
            $rows[] = $row;
        }
        $rows[] = [];

        // === TOP PRODUCTS ===
        $rows[] = ['Top 5 Products by Units Sold'];
        $rows[] = ['Product Name', 'Units Sold', 'Revenue'];

        if (!empty($data['top_products'])) {
            foreach ($data['top_products'] as $product) {
                $rows[] = [
                    $product['name'],
                    $product['units_sold'],
                    $product['revenue'],
                ];
            }
        } else {
            $rows[] = ['No product data available'];
        }

        return $rows;
    }
}