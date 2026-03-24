<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Products extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'products';
    }
}