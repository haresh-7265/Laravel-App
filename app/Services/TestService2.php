<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TestService2
{
    public function __construct()
    {
        Log::info("TestService2 instance created");
    }
}