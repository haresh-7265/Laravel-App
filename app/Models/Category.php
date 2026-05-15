<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];
    protected static function booted(): void
    {
        $flush = fn () => app(CacheService::class)->forgetCategories();

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst($value),
            set: fn ($value) => strtolower($value),
        );
    }
}
