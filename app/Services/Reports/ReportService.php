<?php
namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    public function __construct(
        protected SalesReportService $sales,
        protected InventoryReportService $inventory,
        protected CustomerReportService $customers,
    ) {
    }

    public function getData(string $type): array
    {
        return match ($type) {
            'sales' => $this->sales->generate(),
            'inventory' => $this->inventory->generate(),
            'customers' => $this->customers->generate(),
        };
    }

    public function getSummaryRows(string $type, array $data): array
    {
        return match ($type) {
            'sales' => $this->sales->summaryRows($data),
            'inventory' => $this->inventory->summaryRows($data),
            'customers' => $this->customers->summaryRows($data),
        };
    }

    public function getCsvRows(string $type, array $data): array
    {
        return match ($type) {
            'sales' => $this->sales->csvRows($data),
            'inventory' => $this->inventory->csvRows($data),
            'customers' => $this->customers->csvRows($data),
        };
    }

    public function save(string $type, string $format, array $data): string
    {
        $filename = "{$type}_report_" . now()->format('Y-m-d_His') . ".{$format}";
        $directory = 'reports';
        $path = "{$directory}/{$filename}";

        Storage::disk('local')->makeDirectory($directory);

        $content = match ($format) {
            'json' => $this->toJson($data),
            'csv' => $this->toCsv($data, $type),
            'pdf' => $this->toPdf($data, $type),
        };

        Storage::disk('local')->put($path, $content);

        return storage_path("app/{$path}");
    }

    // ── Formatters ──────────────────────────────────────────────

    private function toJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function toCsv(array $data, string $type): string
    {
        $rows = $this->getCsvRows($type, $data);
        $output = fopen('php://temp', 'r+');

        // Header row
        fputcsv($output, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    private function toPdf(array $data, string $type): string
    {
        $pdf = Pdf::loadView('reports.pdf', [
            'type' => $type,
            'data' => $data,
            'csvRows' => $this->getCsvRows($type, $data),
        ])->setPaper('a4', 'portrait');

        return $pdf->output();
    }
}