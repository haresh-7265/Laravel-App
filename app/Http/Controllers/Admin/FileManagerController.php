<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class FileManagerController extends Controller
{
    private string $disk = 'reports';

    public function index()
    {
        Gate::authorize('view_reports');

        $perPage = min((int) request()->input('perPage', 10), 100);

        $allFiles = collect(Storage::disk($this->disk)->files())
            ->map(fn ($path) => [
                'path' => $path,
                'name' => basename($path),
                'size' => Storage::disk($this->disk)->size($path),
                'url' => $this->generateSignedUrl(basename($path)),
                'lastModified' => Storage::disk($this->disk)->lastModified($path),
                'age_days' => now()->diffInDays(
                    \Carbon\Carbon::createFromTimestamp(
                        Storage::disk($this->disk)->lastModified($path)
                    ), true
                ),
            ])
            ->sortByDesc('lastModified')
            ->values();

        $totalSize = $allFiles->sum('size');
        $olderFilesCount = $allFiles->where('age_days', '>=', 30)->count();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = count($allFiles);

        // Slice correct chunk
        $sliced = $allFiles->slice(
            ($currentPage - 1) * $perPage,
            $perPage
        )->values();

        $paginator = new LengthAwarePaginator(
            $sliced,          // items for this page
            $total,           // total items
            $perPage,         // per page
            $currentPage,     // current page
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return view('admin.files.index', compact('paginator', 'totalSize', 'olderFilesCount'));
    }

    public function archive(Request $request)
    {
        Gate::authorize('view_reports');

        $path = $request->input('path');

        // guard — file must exist
        if (! Storage::disk($this->disk)->exists($path)) {
            return back()->with('warning', "File [{$path}] not found — already gone.");
        }

        $archivePath = 'archive/'.basename($path);

        // copy to archive/
        Storage::disk($this->disk)->copy($path, $archivePath);

        // delete original
        Storage::disk($this->disk)->delete($path);

        return back()->with('success', basename($path).' archived successfully.');
    }

    public function cleanup()
    {
        Gate::authorize('view_reports');

        $files = Storage::disk($this->disk)->files();
        $cutoff = now()->subDays(30)->timestamp;
        $deleted = 0;
        $missing = 0;

        foreach ($files as $path) {

            // skip archive folder
            if (str_starts_with($path, 'archive/')) {
                continue;
            }

            if (! Storage::disk($this->disk)->exists($path)) {
                $missing++;

                continue;
            }

            if (Storage::disk($this->disk)->lastModified($path) < $cutoff) {
                Storage::disk($this->disk)->delete($path);
                $deleted++;
            }
        }

        $msg = "{$deleted} file(s) deleted older than 30 days.";
        if ($missing) {
            $msg .= " {$missing} file(s) already missing.";
        }

        return back()->with('success', $msg);
    }

    public function download(string $filename)
    {
        abort_unless(request()->hasValidSignature(), 403);

        if (! Storage::disk($this->disk)->exists($filename)) {
            return back()->with('warning', "File [{$filename}] not found — already gone.");
        }

        return response()->download(Storage::disk($this->disk)->path($filename));
    }

    private function generateSignedUrl($filename)
    {
        $url = URL::temporarySignedRoute(
            'admin.files.download',
            now()->addMinutes(10),
            ['filename' => $filename]
        );

        return $url;
    }
}
