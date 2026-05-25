<?php

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function products() : HasMany
    {
        return $this->hasMany(Product::class)->chaperone();
    }

    protected static function booted(): void
    {
        $flush = fn () => app(CacheService::class)->forgetCategories();

        static::created($flush);
        static::updated(function (Category $category) use ($flush) {
            $flush();
            if ($category->wasChanged('name')) {
                // re-push only this category's products
                $category->products()->searchable();
            }
        }
        );
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
