<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    public function track(string $event, array $data = [])
    {
        // log analitics
        Log::channel('analitics')->info("Analytics Event: {$event}", $data);
    }
}