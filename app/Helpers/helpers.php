<?php

use App\Services\AnalyticsService;

if (!function_exists('analytics')) {
    function analytics(): AnalyticsService
    {
        return app(AnalyticsService::class);
    }
}