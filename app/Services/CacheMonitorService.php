<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CacheMonitorService
{
    // ─────────────────────────────────────────────────────────────
    //  Public API
    // ─────────────────────────────────────────────────────────────

    /**
     * Gather all cache statistics for the monitor dashboard.
     *
     * @return array{totalHits: int, totalMisses: int, hitRate: float,
     *               mostCached: array, recentEvents: array,
     *               cacheStore: string, cacheSize: array}
     */
    public function getStatistics(): array
    {
        $logStats   = $this->parseCacheLogs();
        $cacheStore = config('cache.default');
        $cacheSize  = $this->getCacheSize($cacheStore);

        return [
            'totalHits'    => $logStats['hits'],
            'totalMisses'  => $logStats['misses'],
            'hitRate'      => $logStats['hitRate'],
            'mostCached'   => $logStats['mostCached'],
            'recentEvents' => $logStats['recentEvents'],
            'cacheStore'   => $cacheStore,
            'cacheSize'    => $cacheSize,
        ];
    }

    /**
     * Flush all cache entries.
     */
    public function clearAll(): void
    {
        Cache::flush();
    }

    // ─────────────────────────────────────────────────────────────
    //  Log Parsing
    // ─────────────────────────────────────────────────────────────

    /**
     * Parse today's cache log file and calculate hit/miss stats.
     */
    private function parseCacheLogs(): array
    {
        $hits         = 0;
        $misses       = 0;
        $keyCounts    = [];   // key => hit count
        $recentEvents = [];

        // Daily driver log: cache-YYYY-MM-DD.log
        $logFile = storage_path('logs/cache/cache-' . now()->format('Y-m-d') . '.log');

        if (File::exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $isHit  = str_contains($line, 'CACHE_HIT');
                $isMiss = str_contains($line, 'CACHE_MISS');

                if (!$isHit && !$isMiss) {
                    continue;
                }

                $key = $this->extractJsonField($line, 'key');

                if ($isHit) {
                    $hits++;
                    $keyCounts[$key] = ($keyCounts[$key] ?? 0) + 1;
                }

                if ($isMiss) {
                    $misses++;
                }

                // Timestamp sits at position [1..19] in the log line
                $recentEvents[] = [
                    'type'      => $isHit ? 'HIT' : 'MISS',
                    'key'       => $key,
                    'timestamp' => substr($line, 1, 19),
                ];
            }
        }

        $total   = $hits + $misses;
        $hitRate = $total > 0 ? round(($hits / $total) * 100, 1) : 0;

        // Top 10 most-hit keys
        arsort($keyCounts);
        $mostCached = array_slice($keyCounts, 0, 10, true);

        // Last 20 events, newest first
        $recentEvents = array_slice(array_reverse($recentEvents), 0, 20);

        return compact('hits', 'misses', 'hitRate', 'mostCached', 'recentEvents');
    }

    /**
     * Extract a JSON field value from a log line.
     *
     * Log format: [timestamp] local.INFO: CACHE_HIT {"key":"some.key","tags":[]}
     */
    private function extractJsonField(string $line, string $field): string
    {
        if (preg_match('/\{.*\}/', $line, $m)) {
            $json = json_decode($m[0], true);
            return $json[$field] ?? 'unknown';
        }

        return 'unknown';
    }

    // ─────────────────────────────────────────────────────────────
    //  Cache-Size Inspection
    // ─────────────────────────────────────────────────────────────

    /**
     * Inspect the current cache store and return size / key info.
     */
    private function getCacheSize(string $store): array
    {
        $info = [
            'display'    => 'N/A',
            'keys'       => 0,
            'memoryUsed' => 'N/A',
            'memoryPeak' => 'N/A',
        ];

        try {
            if ($store === 'redis') {
                $info = $this->getRedisInfo();
            } elseif ($store === 'file') {
                $info = $this->getFileCacheInfo();
            } elseif ($store === 'database') {
                $info = $this->getDatabaseCacheInfo();
            }
        } catch (\Exception $e) {
            $info['display'] = 'Unable to read (' . class_basename($e) . ')';
        }

        return $info;
    }

    // ─── Redis ──────────────────────────────────────────────────

    /**
     * Query Redis for memory and key-count statistics.
     */
    private function getRedisInfo(): array
    {
        try {
            $redis     = Redis::connection('cache');
            $redisInfo = $redis->info();

            // dd($redisInfo);
            $memory   = $redisInfo['Memory'] ?? [];
            $keyspace = $redisInfo['Keyspace'] ?? [];

            // Sum keys across all databases
            $keyCount = collect($keyspace)->sum(function ($dbInfo) {
                return $dbInfo['keys'];
            });

            $memUsed = (int) ($memory['used_memory']      ?? 0);
            $memPeak = (int) ($memory['used_memory_peak']  ?? 0);

            return [
                'display'    => $memory['used_memory_human'] ?? $this->formatBytes($memUsed),
                'keys'       => $keyCount,
                'memoryUsed' => $memory['used_memory_human']      ?? $this->formatBytes($memUsed),
                'memoryPeak' => $memory['used_memory_peak_human'] ?? $this->formatBytes($memPeak),
            ];

        } catch (\Exception $e) {
            Log::error('CacheMonitor: Redis error', ['error' => $e->getMessage()]);

            return [
                'display'    => 'Redis unavailable',
                'keys'       => 0,
                'memoryUsed' => 'N/A',
                'memoryPeak' => 'N/A',
            ];
        }
    }

    // ─── File Cache ─────────────────────────────────────────────

    private function getFileCacheInfo(): array
    {
        $info = ['display' => 'N/A', 'keys' => 0, 'memoryUsed' => 'N/A', 'memoryPeak' => 'N/A'];
        $path = config('cache.stores.file.path');

        if (File::isDirectory($path)) {
            $size  = 0;
            $count = 0;
            foreach (File::allFiles($path) as $file) {
                $size += $file->getSize();
                $count++;
            }
            $info['display'] = $this->formatBytes($size);
            $info['keys']    = $count;
        }

        return $info;
    }

    // ─── Database Cache ─────────────────────────────────────────

    private function getDatabaseCacheInfo(): array
    {
        $table = config('cache.stores.database.table', 'cache');
        $count = \DB::table($table)->count();

        return [
            'display'    => "{$count} entries",
            'keys'       => $count,
            'memoryUsed' => 'N/A',
            'memoryPeak' => 'N/A',
        ];
    }

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Human-readable byte string.
     */
    private function formatBytes(int $bytes): string
    {
        return match (true) {
            $bytes >= 1073741824 => round($bytes / 1073741824, 2) . ' GB',
            $bytes >= 1048576   => round($bytes / 1048576,   2) . ' MB',
            $bytes >= 1024      => round($bytes / 1024,      2) . ' KB',
            default             => $bytes . ' B',
        };
    }
}
