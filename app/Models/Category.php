<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected static function booted(): void
    {
        $flush = fn() => app(CacheService::class)->forgetCategories();

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }
}
