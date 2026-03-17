<?php

namespace App\Http\Controllers;

use App\Services\TestService1;
use App\Services\TestService2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    protected $bindService1;
    protected $singletonService1;
    public function __construct(TestService1 $bindService1, TestService2 $singletonService1)
    {
        $this->bindService1 = $bindService1;
        $this->singletonService1 = $singletonService1;
    }
    public function index()
    {
        Log::info('TestController Executed');
        return "Hello from Controller";
    }

    public function test(TestService1 $bindService2, TestService2 $singletonService2)
    {
        return [
            'bind_same?' => $this->bindService1 === $bindService2,
            'singleton_same?' => $this->singletonService1 === $singletonService2,
        ];
    }
}
