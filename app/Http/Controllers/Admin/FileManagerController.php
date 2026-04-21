<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileManagerController extends Controller
{
    private string $disk = 'reports';

    public function index()
    {
        $files = collect(Storage::disk($this->disk)->files())
            ->map(fn($path) => [
                'path'         => $path,
                'name'         => basename($path),
                'size'         => Storage::disk($this->disk)->size($path),
                'lastModified' => Storage::disk($this->disk)->lastModified($path),
                'age_days'     => now()->diffInDays(
                                    \Carbon\Carbon::createFromTimestamp(
                                        Storage::disk($this->disk)->lastModified($path)
                                    )
                                ),
            ])
            ->sortByDesc('lastModified')
            ->values();

        return view('admin.files.index', compact('files'));
    }

    public function archive(Request $request)
    {
        $path = $request->input('path');

        // guard — file must exist
        if (!Storage::disk($this->disk)->exists($path)) {
            return back()->with('warning', "File [{$path}] not found — already gone.");
        }

        $archivePath = 'archive/' . basename($path);

        // copy to archive/
        Storage::disk($this->disk)->copy($path, $archivePath);

        // delete original
        Storage::disk($this->disk)->delete($path);

        return back()->with('success', basename($path) . ' archived successfully.');
    }

    public function cleanup()
    {
        $files     = Storage::disk($this->disk)->files();
        $cutoff    = now()->subDays(30)->timestamp;
        $deleted   = 0;
        $missing   = 0;

        foreach ($files as $path) {

            // skip archive folder
            if (str_starts_with($path, 'archive/')) continue;

            if (!Storage::disk($this->disk)->exists($path)) {
                $missing++;
                continue;
            }

            if (Storage::disk($this->disk)->lastModified($path) < $cutoff) {
                Storage::disk($this->disk)->delete($path);
                $deleted++;
            }
        }

        $msg = "{$deleted} file(s) deleted older than 30 days.";
        if ($missing) $msg .= " {$missing} file(s) already missing.";

        return back()->with('success', $msg);
    }
}
