<?php

namespace App\Models;

use App\Collections\ProductCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use SoftDeletes, HasFactory, Searchable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'stock',
        'image',
        'category_id',
        'is_active',
        'tags',
        'avg_rating',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function waitlistUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'product_waitlist')->withTimestamps();
    }

    public function averageRating(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
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
        return Cache::tags(['products'])->remember(
            "product.{$value}",
            now()->addMinutes(30),
            fn() => $this->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail()
        );
    }

    protected static function booted(): void
    {
    
    }

    public function newCollection(array $models = []): ProductCollection
    {
        return new ProductCollection($models);
    }

    public function scopeActive($query)
    {
        return match(request()->user()?->role){
            'admin' => $query,
            default => $query->where('is_active', true)
        } ;
    }

    public function getImageSizeAttribute(){
        return human_file_size(Storage::disk('public')->size($this->image ?? 'products/default.png'));
    }

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'tags' => $this->tags,
            // 'category_name' => $this->category?->name,  // # it is not working with database driver
        ];
    }
}
