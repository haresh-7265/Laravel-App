<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
     public function index()
    {
        Log::info('TestController Executed');
        return "Hello from Controller";
    }
}
