<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class InvoiceManagerController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Order::class);

        $files = Storage::disk('local')->files('invoices');

        $invoices = collect($files)->map(function ($path) {
            return [
                'filename' => basename($path),
                'path' => $path,
                'url' => $this->generateSignedUrl($path),
                'size' => Storage::disk('local')->size($path),
                'lastModified' => Storage::disk('local')->lastModified($path),
            ];
        })->sortByDesc('lastModified');

        return view('admin.invoices', compact('invoices'));
    }

    public function downloadByFilename(Request $request, string $filename)
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(current_user()?->can('manage_orders'), 403);

        $filename = basename($filename);
        $path = "invoices/{$filename}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $filename);
    }

    // helper methods
    /**
     * Generate signed download URL for invoice manager
     */
    public function generateSignedUrl(string $filename)
    {

        $filename = basename($filename); // prevent directory traversal
        $path = "invoices/{$filename}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        $signedUrl = URL::signedRoute(
            'admin.invoice.download',
            ['filename' => $filename],
            now()->addMinutes(30) // expires in 30 min
        );

        return $signedUrl;
    }
}
