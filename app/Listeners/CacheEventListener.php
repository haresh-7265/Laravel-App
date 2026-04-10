<?php

namespace App\Listeners;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Support\Facades\Log;

class CacheEventListener
{
    /**
     * Handle cache hit events.
     */
    public function handleCacheHit(CacheHit $event): void
    {
        Log::channel('cache')->info('CACHE_HIT', [
            'key'   => $event->key,
            'tags'  => $event->tags ?? [],
        ]);
    }

    /**
     * Handle cache miss events.
     */
    public function handleCacheMissed(CacheMissed $event): void
    {
        Log::channel('cache')->info('CACHE_MISS', [
            'key'   => $event->key,
            'tags'  => $event->tags ?? [],
        ]);
    }
}
