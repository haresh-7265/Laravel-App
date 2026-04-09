<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'stock',
        'image',
        'category_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getFinalPriceAttribute()
    {
        return $this->discount_price ?? $this->price;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return Cache::remember(
            "product.{$value}",
            now()->addMinutes(30),
            fn() => $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail()
        );
    }

    protected static function booted(): void
    {
        $flush = fn($product) => app(CacheService::class)->forgetProduct($product->slug);

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }
}
