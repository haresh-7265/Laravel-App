<?php
namespace App\Services\Reports;

use App\Models\Product;

class InventoryReportService
{
    private const LOW_STOCK_THRESHOLD = 10;

    public function generate(): array
    {
        // Low stock: stock > 0 but below threshold
        $lowStockItems = Product::where('stock', '>', 0)
            ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            ->get(['name', 'stock', 'price', 'discount_price', 'category_id'])
            ->map(fn($p) => [
                'name' => $p->name,
                'stock' => $p->stock,
                'price' => number_format($p->price, 2),
                'threshold' => self::LOW_STOCK_THRESHOLD
            ])->toArray();

        // Out of stock
        $outOfStockItems = Product::where('stock', 0)
            ->get(['name', 'stock', 'price', 'discount_price'])
            ->map(fn($p) => [
                'name' => $p->name,
                'price' => number_format($p->price, 2),
                'last_updated_at' => $p->updated_at,
            ])->toArray();

        // Total inventory value using effective price (discount_price if set, else price)
        $totalValue = Product::all()->sum(function ($product) {
            $effectivePrice = $product->discount_price ?? $product->price;
            return $effectivePrice * $product->stock;
        });


        return [
            'generated_at' => now()->toDateTimeString(),
            'total_value' => number_format($totalValue, 2),
            'out_of_stock_count' => count($outOfStockItems),
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
        ];
    }

    public function summaryRows(array $data): array
    {
        return [
            ['Low Stock Items', count($data['low_stock_items'])],
            ['Out of Stock', $data['out_of_stock_count']],
            ['Total Inventory Value', config('admin.currency') . $data['total_value']],
        ];
    }

    public function csvRows(array $data): array
    {
        $rows = [];

        // === SUMMARY SECTION ===
        $rows[] = ['Inventory Report'];
        $rows[] = ['Generated At', $data['generated_at']];
        $rows[] = [];

        // Summary block
        $rows[] = ['Summary'];
        foreach ($this->summaryRows($data) as $row) {
            $rows[] = $row;
        }
        $rows[] = [];

        // === LOW STOCK SECTION ===
        $rows[] = ['Low Stock Items'];
        $rows[] = ['Name', 'Stock', 'Price', 'Threshold'];

        if (!empty($data['low_stock_items'])) {
            foreach ($data['low_stock_items'] as $item) {
                $rows[] = [
                    $item['name'],
                    $item['stock'],
                    $item['price'],
                    $item['threshold'],
                ];
            }
        } else {
            $rows[] = ['No low stock items'];
        }

        $rows[] = [];

        // === OUT OF STOCK SECTION ===
        $rows[] = ['Out of Stock Items'];
        $rows[] = ['Name', 'Price', 'Last Updated'];

        if (!empty($data['out_of_stock_items'])) {
            foreach ($data['out_of_stock_items'] as $item) {
                $rows[] = [
                    $item['name'],
                    $item['price'],
                    $item['last_updated_at'] ?? 'N/A',
                ];
            }
        } else {
            $rows[] = ['No out of stock items'];
        }

        return $rows;
    }
}