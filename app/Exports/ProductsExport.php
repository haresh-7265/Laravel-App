<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithEvents
{
    private int $totalStock = 0;
    private float $totalRevenue = 0;
    private float $totalDiscount = 0;
    private int $rowCount = 0;

    public function __construct(
        private ?int $categoryId = null,
        private ?float $minPrice = null,
        private ?float $maxPrice = null,
        private ?int $minStock = null,
        private ?int $maxStock = null,
    ) {
    }

    public function query(): Builder
    {
        return Product::query()
            ->with('category')
            ->when($this->categoryId, fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->minPrice, fn($q) => $q->where('price', '>=', $this->minPrice))
            ->when($this->maxPrice, fn($q) => $q->where('price', '<=', $this->maxPrice))
            ->when($this->minStock, fn($q) => $q->where('stock', '>=', $this->minStock))
            ->when($this->maxStock, fn($q) => $q->where('stock', '<=', $this->maxStock))
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            '#',
            'Product Name',
            'Slug',
            'Category',
            'Price (₹)',
            'Discount Price (₹)',
            'Savings (₹)',
            'Stock',
            'Stock Value (₹)',
            'Tags',
            'Created At',
        ];
    }

    public function map($product): array
    {
        $savings = $product->discount_price
            ? round($product->price - $product->discount_price, 2)
            : 0;
        $stockValue = round($product->price * $product->stock, 2);

        // Accumulate totals
        $this->totalStock += $product->stock;
        $this->totalRevenue += $stockValue;
        $this->totalDiscount += $savings * $product->stock;
        $this->rowCount++;

        return [
            $product->id,
            $product->name,
            $product->slug,
            $product->category->name ?? 'N/A',
            number_format($product->price, 2),
            $product->discount_price ? number_format($product->discount_price, 2) : '-',
            $savings > 0 ? number_format($savings, 2) : '-',
            $product->stock,
            number_format($stockValue, 2),
            $product->tags ? implode(', ', $product->tags) : '-',
            $product->created_at->format('Y-m-d'),
        ];
    }

    public function title(): string
    {
        return 'Products';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->rowCount + 2; // +1 heading +1 for next row
                $totalRow = $lastRow + 1;

                // Empty separator row
                $sheet->setCellValue("A{$lastRow}", '');

                // Totals row
                $sheet->setCellValue("A{$totalRow}", 'TOTALS');
                $sheet->setCellValue("D{$totalRow}", 'Total Products: ' . $this->rowCount);
                $sheet->setCellValue("H{$totalRow}", $this->totalStock);
                $sheet->setCellValue("I{$totalRow}", number_format($this->totalRevenue, 2));

                // Bold the totals row
                $sheet->getStyle("A{$totalRow}:K{$totalRow}")->getFont()->setBold(true);

                // Summary block below totals
                $summaryStart = $totalRow + 2;
                $sheet->setCellValue("A{$summaryStart}", '--- SUMMARY ---');
                $sheet->setCellValue("A" . ($summaryStart + 1), 'Total Products Exported:');
                $sheet->setCellValue("B" . ($summaryStart + 1), $this->rowCount);
                $sheet->setCellValue("A" . ($summaryStart + 2), 'Total Stock Units:');
                $sheet->setCellValue("B" . ($summaryStart + 2), $this->totalStock);
                $sheet->setCellValue("A" . ($summaryStart + 3), 'Total Stock Value (₹):');
                $sheet->setCellValue("B" . ($summaryStart + 3), number_format($this->totalRevenue, 2));
                $sheet->setCellValue("A" . ($summaryStart + 4), 'Total Discount Savings (₹):');
                $sheet->setCellValue("B" . ($summaryStart + 4), number_format($this->totalDiscount, 2));
                $sheet->setCellValue("A" . ($summaryStart + 5), 'Exported At:');
                $sheet->setCellValue("B" . ($summaryStart + 5), now()->format('Y-m-d H:i:s'));

                // Bold summary headers
                $sheet->getStyle("A{$summaryStart}:A" . ($summaryStart + 5))->getFont()->setBold(true);
            },
        ];
    }
}