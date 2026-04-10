<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CacheMonitorService;

class CacheMonitorController extends Controller
{
    public function __construct(
        private CacheMonitorService $cacheMonitor
    ) {}

    /**
     * Display the cache performance monitor dashboard.
     */
    public function index()
    {
        $data = $this->cacheMonitor->getStatistics();

        return view('admin.cache-monitor', $data);
    }

    /**
     * Flush all cache and redirect back.
     */
    public function clearAll()
    {
        $this->cacheMonitor->clearAll();

        return redirect()
            ->route('admin.cache-monitor')
            ->with('success', 'All cache has been cleared successfully!');
    }
}
