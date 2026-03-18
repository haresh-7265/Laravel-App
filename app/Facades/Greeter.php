<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Greeter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'greeter';
    }
}