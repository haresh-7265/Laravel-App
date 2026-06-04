<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportProductRowJob;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class ProductImportController extends Controller
{
    /**
     * Show the CSV upload form.
     */
    public function index()
    {
        Gate::authorize('manage_products');

        $batchIds = session()->get('batch_ids', []);

        return view('admin.import', compact('batchIds'));
    }

    /**
     * Accept a CSV upload, read it with LazyCollection, and dispatch a batch.
     */
    public function store(Request $request)
    {
        Gate::authorize('manage_products');

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv', 'max:10240'],
        ]);

        $path = $request->file('csv_file')->getRealPath();

        // 1. Open the file to read and validate the header row first
        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Failed to open the uploaded CSV file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'The CSV file has no header row.');
        }

        // Strip BOM + whitespace from headers
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/\x{FEFF}/u', '', $h))), $header);

        $allowedHeaders = ['name', 'price', 'discount_price', 'stock', 'category', 'description', 'is_active', 'tags'];
        $requiredHeaders = ['name', 'price', 'stock', 'category'];

        $missingRequired = array_diff($requiredHeaders, $header);
        $unknownHeaders = array_diff($header, $allowedHeaders);

        if (!empty($missingRequired)) {
            fclose($handle);
            return back()->withErrors([
                'csv_file' => 'Missing required columns: ' . implode(', ', $missingRequired),
            ])->with('error', 'Missing required columns: ' . implode(', ', $missingRequired));
        }

        if (!empty($unknownHeaders)) {
            fclose($handle);
            return back()->withErrors([
                'csv_file' => 'Unknown columns found: ' . implode(', ', $unknownHeaders),
            ])->with('error', 'Unknown columns found: ' . implode(', ', $unknownHeaders));
        }

        fclose($handle);

        // 2. Build jobs from the CSV using LazyCollection
        $jobs = LazyCollection::make(function () use ($path, $header) {
            $handle = fopen($path, 'r');

            // Skip header row since we already validated it
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Combine header + row into associative array
                yield array_combine($header, $row);
            }

            fclose($handle);
        })
            ->chunk(100)
            ->map(fn ($chunk) => $chunk->map(fn ($row) => new ImportProductRowJob($row))->all())
            ->all();

        // Flatten chunks into a single array of jobs
        $allJobs = array_merge(...$jobs);

        if (empty($allJobs)) {
            return back()->with('error', 'The CSV file is empty or has no data rows.');
        }

        // Dispatch the batch with callbacks
        $batch = Bus::batch($allJobs)
            ->then(function (Batch $batch) {
                Log::info("✅ Import batch [{$batch->id}] completed successfully. {$batch->totalJobs} products processed.");
            })
            ->catch(function (Batch $batch, \Throwable $e) {
                Log::error("❌ Import batch [{$batch->id}] encountered failure: {$e->getMessage()}");
            })
            ->finally(function (Batch $batch) {
                Log::info("📦 Import batch [{$batch->id}] finished. Total: {$batch->totalJobs}, Failed: {$batch->failedJobs}");
            })
            ->name('Product CSV Import — '.now()->format('Y-m-d H:i'))
            ->allowFailures()
            ->dispatch();

        // Store batch ID in session to show progress on redirect
        session()->push('batch_ids', $batch->id);

        return redirect()
            ->route('products.import.index')
            ->with('success', "Batch dispatched with {$batch->totalJobs} jobs!");
    }

    /**
     * JSON endpoint polled by the progress page JS.
     */
    public function status(string $batchId)
    {
        Gate::authorize('manage_products');

        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return response()->json([
                'status' => 'error',
                'message' => 'Batch not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'id' => $batch->id,
            'name' => $batch->name,
            'total' => $batch->totalJobs,
            'processed' => $batch->processedJobs(),
            'failed' => $batch->failedJobs,
            'pending' => $batch->pendingJobs,
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
            'hasFailures' => $batch->hasFailures(),
            'createdAt' => $batch->createdAt->toDateTimeString(),
            'finishedAt' => $batch->finishedAt?->toDateTimeString(),
            'cancelledAt' => $batch->cancelledAt?->toDateTimeString(),
        ]);
    }

    /**
     * Cancel an in-progress batch.
     */
    public function cancel(string $batchId)
    {
        Gate::authorize('manage_products');

        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return response()->json([
                'status' => 'error',
                'message' => 'Batch not found',
            ], 404);
        }

        $batch->cancel();

        return response()->json([
            'status' => 'success',
            'message' => 'Batch cancelled successfully.',
            'cancelled' => true,
        ]);
    }
}
