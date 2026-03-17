<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TestService1
{
    public function __construct()
    {
        Log::info("TestService1 instance created");
    }
}