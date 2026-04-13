<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'products:import
                            {file : Path to the CSV file}
                            {--chunk=500 : Number of records to process per chunk}
                            {--dry-run : Validate only, do not write to DB}';

    /**
     * The console command description.
     */
    protected $description = 'Bulk-import products from a CSV file using LazyCollection (memory-efficient)';

    // ──────────────────────────────────────────────────────────────────────────
    // Counters
    // ──────────────────────────────────────────────────────────────────────────
    private int $created  = 0;
    private int $updated  = 0;
    private int $skipped  = 0;
    private int $errors   = 0;

    /** @var array<int, array{row: int, name: string, messages: string[]}> */
    private array $errorLog = [];

    // ──────────────────────────────────────────────────────────────────────────
    // Entry point
    // ──────────────────────────────────────────────────────────────────────────
    public function handle(): int
    {
        $filePath  = $this->argument('file');
        $chunkSize = (int) $this->option('chunk');
        $dryRun    = (bool) $this->option('dry-run');

        // ── Validate the file path ────────────────────────────────────────────
        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'csv') {
            $this->error('Only CSV files are supported.');
            return self::FAILURE;
        }

        // ── Pre-load category slugs → ids for fast lookup ─────────────────────
        $categoryMap = Category::pluck('id', 'name')->mapWithKeys(function ($id, $name) {
            return [strtolower(trim($name)) => $id];
        })->all();

        $this->newLine();
        $this->info("📦  Product Import — <fg=cyan>{$filePath}</>");
        $this->info("    Chunk size : {$chunkSize}");
        $this->info('    Dry-run    : ' . ($dryRun ? '<fg=yellow>YES</>' : 'NO'));
        $this->newLine();

        $memBefore = memory_get_usage(true);
        $startedAt = microtime(true);

        // ── Count rows for the progress bar (fast pass) ───────────────────────
        $totalRows = $this->countCsvRows($filePath);

        if ($totalRows === 0) {
            $this->warn('CSV file is empty or has only a header row.');
            return self::SUCCESS;
        }

        $this->info("    Total rows : {$totalRows}");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalRows);
        $bar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% — ' .
            '<fg=green>✔ %created%</> <fg=yellow>↺ %updated%</> <fg=red>✖ %errors%</>'
        );

        // Register custom placeholders so the progress bar shows live counters.
        $bar->setPlaceholderFormatterDefinition('created',  fn () => $this->created);
        $bar->setPlaceholderFormatterDefinition('updated',  fn () => $this->updated);
        $bar->setPlaceholderFormatterDefinition('errors',   fn () => $this->errors);

        $bar->start();

        // ── Stream the CSV as a LazyCollection, process in chunks ─────────────
        $this->csvLazyCollection($filePath)
            ->chunk($chunkSize)
            ->each(function ($chunk) use ($bar, &$categoryMap, $dryRun) {

                $rows = $chunk->all();   // materialise the tiny chunk

                if (! $dryRun) {
                    // Batch-fetch existing slugs inside this chunk for upsert logic.
                    $slugsInChunk = [];
                    foreach ($rows as $r) {
                        $s = trim($r['slug'] ?? '') ?: Str::slug(trim($r['name'] ?? ''));
                        if ($s !== '') {
                            $slugsInChunk[] = $s;
                        }
                    }

                    $existingSlugs = Product::whereIn('slug', $slugsInChunk)
                        ->pluck('id', 'slug')
                        ->all();
                } else {
                    $existingSlugs = [];
                }

                foreach ($rows as $row) {
                    $this->processRow($row, $categoryMap, $existingSlugs, $dryRun);
                    $bar->advance();
                }
            });

        $bar->finish();

        // ── Summary ───────────────────────────────────────────────────────────
        $elapsed   = round(microtime(true) - $startedAt, 2);
        $memAfter  = memory_get_usage(true);
        $memPeak   = memory_get_peak_usage(true);

        $this->newLine(2);
        $this->line('─────────────────────────────────────────');
        $this->info("  ✅  Created  : {$this->created}");
        $this->info("  ♻️   Updated  : {$this->updated}");
        $this->warn("  ⏭️   Skipped  : {$this->skipped}");
        $this->error("  ❌  Errors   : {$this->errors}");
        $this->line('─────────────────────────────────────────');
        $this->line("  ⏱   Elapsed  : {$elapsed}s");
        $this->line('  🧠  Mem used : ' . $this->formatBytes($memAfter - $memBefore));
        $this->line('  🧠  Mem peak : ' . $this->formatBytes($memPeak));
        $this->line('─────────────────────────────────────────');
        $this->newLine();

        if ($this->errors > 0) {
            $this->printErrorLog();
        }

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LazyCollection: read CSV one line at a time — O(1) memory
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Yields one associative row array per CSV line without loading the whole
     * file into memory.
     *
     * @return LazyCollection<int, array<string, string>>
     */
    private function csvLazyCollection(string $path): LazyCollection
    {
        return LazyCollection::make(function () use ($path) {
            $handle = fopen($path, 'r');

            if ($handle === false) {
                return;
            }

            try {
                // First line → headers
                $headers = fgetcsv($handle);

                if ($headers === false) {
                    return;
                }

                // Normalise header names (trim whitespace, lowercase)
                $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

                while (($row = fgetcsv($handle)) !== false) {
                    // Skip blank lines
                    if ($row === [null]) {
                        continue;
                    }

                    // Pad / truncate to match header count
                    $row = array_slice(array_pad($row, count($headers), ''), 0, count($headers));

                    yield array_combine($headers, $row);
                }
            } finally {
                fclose($handle);
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Process one CSV row
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @param array<string, string>  $row
     * @param array<string, int>     $categoryMap    slug → id
     * @param array<string, int>     $existingSlugs  slug → product_id (batch-fetched)
     */
    private function processRow(
        array $row,
        array &$categoryMap,
        array &$existingSlugs,
        bool  $dryRun
    ): void {
        static $rowNumber = 1;   // header was row 1
        $rowNumber++;

        // ── Normalise & coerce ────────────────────────────────────────────────
        $name          = trim($row['name']          ?? '');
        $slug          = trim($row['slug']          ?? '') ?: Str::slug($name);
        $price         = trim($row['price']         ?? '');
        $discountPrice = trim($row['discount_price'] ?? '') ?: null;
        $stock         = trim($row['stock']         ?? '0');
        $categoryName  = trim($row['category']      ?? '');
        $categoryKey   = strtolower($categoryName);
        $description   = trim($row['description']   ?? '') ?: null;
        $tagsRaw       = trim($row['tags']          ?? '') ?: null;
        $image         = trim($row['image']         ?? '') ?: null;

        // Auto-create category if missing
        if ($categoryName !== '' && !isset($categoryMap[$categoryKey])) {
            if (!$dryRun) {
                // use forceCreate in case name is not fillable
                $category = Category::forceCreate(['name' => $categoryName]);
                $categoryMap[$categoryKey] = $category->id;
            } else {
                $categoryMap[$categoryKey] = rand(9999, 99999);
            }
        }

        // Resolve category_id
        $categoryId = $categoryMap[$categoryKey] ?? null;

        // Parse tags: accept comma-separated string or JSON array
        $tags = null;
        if ($tagsRaw !== null) {
            $decoded = json_decode($tagsRaw, true);
            $tags    = json_last_error() === JSON_ERROR_NONE
                ? $decoded
                : array_map('trim', explode(',', $tagsRaw));
        }

        // ── Validate ──────────────────────────────────────────────────────────
        $validator = Validator::make(
            [
                'name'           => $name,
                'slug'           => $slug,
                'price'          => $price,
                'discount_price' => $discountPrice,
                'stock'          => $stock,
                'category_id'    => $categoryId,
                'description'    => $description,
                'image'          => $image,
            ],
            [
                'name'           => ['required', 'string', 'max:255'],
                'slug'           => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/'],
                'price'          => ['required', 'numeric', 'min:0'],
                'discount_price' => ['nullable', 'numeric', 'min:0'],
                'stock'          => ['required', 'integer', 'min:0'],
                'category_id'    => ['required', 'integer'],
                'description'    => ['nullable', 'string'],
                'image'          => ['nullable', 'string', 'max:255'],
            ]
        );

        if ($validator->fails()) {
            $this->recordError($rowNumber, $name, $validator->errors()->all());
            return;
        }

        // Extra business rule: discount must be less than base price
        if ($discountPrice !== null && (float) $discountPrice >= (float) $price) {
            $this->recordError($rowNumber, $name, ['discount_price must be less than price']);
            return;
        }

        if ($dryRun) {
            $this->created++;   // treat as "would create" in dry-run
            return;
        }

        // ── Upsert ───────────────────────────────────────────────────────────
        try {
            $data = [
                'name'           => $name,
                'price'          => (float) $price,
                'discount_price' => $discountPrice !== null ? (float) $discountPrice : null,
                'stock'          => (int) $stock,
                'category_id'    => (int) $categoryId,
                'description'    => $description,
                'tags'           => $tags ? json_encode($tags) : null,
                'image'          => $image,
            ];

            if (isset($existingSlugs[$slug])) {
                Product::where('slug', $slug)->update($data);
                $this->updated++;
            } else {
                $product = Product::create(array_merge($data, ['slug' => $slug]));
                $existingSlugs[$slug] = $product->id;
                $this->created++;
            }
        } catch (Throwable $e) {
            $this->recordError($rowNumber, $name, [$e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Count data rows in CSV (excludes the header). */
    private function countCsvRows(string $path): int
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return 0;
        }

        $count = -1;   // -1 to skip the header line

        while (fgetcsv($handle) !== false) {
            $count++;
        }

        fclose($handle);

        return max(0, $count);
    }

    /**
     * @param string[] $messages
     */
    private function recordError(int $row, string $name, array $messages): void
    {
        $this->errors++;
        $this->errorLog[] = [
            'row'      => $row,
            'name'     => $name ?: '(empty)',
            'messages' => $messages,
        ];
    }

    private function printErrorLog(): void
    {
        $this->line('<fg=red>── Validation / Import Errors ──────────────────</>');
        $this->newLine();

        // Show at most 25 errors inline; write the rest to a log file.
        $display = array_slice($this->errorLog, 0, 25);

        foreach ($display as $entry) {
            $this->line(
                sprintf(
                    '  Row <fg=yellow>%d</> | <fg=cyan>%s</>',
                    $entry['row'],
                    $entry['name']
                )
            );

            foreach ($entry['messages'] as $msg) {
                $this->line("    → {$msg}");
            }

            $this->newLine();
        }

        if (count($this->errorLog) > 25) {
            $remaining = count($this->errorLog) - 25;
            $logPath   = storage_path('logs/import_errors_' . date('Ymd_His') . '.json');
            file_put_contents($logPath, json_encode($this->errorLog, JSON_PRETTY_PRINT));
            $this->warn("  … and {$remaining} more. Full log written to: {$logPath}");
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 2) . ' MB';
    }
}