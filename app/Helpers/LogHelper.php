<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Context;

class LogHelper
{
    public static function log($level, $message, array $context = [])
    {
        $userType = Context::get('user_type');

        $channel = match ($userType) {
            'admin' => 'admin',
            'customer' => 'customer',
            default => config('logging.default'),
        };

        Log::channel($channel)->{$level}($message, $context);
    }
}
