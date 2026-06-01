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
        'created_by_id', 'created_by_type',
        'updated_by_id', 'updated_by_type',
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

    public function createdBy()
    {
        return $this->morphTo('created_by');
    }

    public function updatedBy()
    {
        return $this->morphTo('updated_by');
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
        return $query->where('is_active', true);
    }

    public function getImageSizeAttribute(){
        return human_file_size(Storage::disk('public')->size($this->image ?? 'products/default.png'));
    }

    // ── Scout: versioned index name ───────────────────────────────────────────
    public function searchableAs(): string
    {
        return config('scout.product_index');
    }

    // ── Scout: exclude unpublished + soft-deleted ─────────────────────────────
    public function shouldBeSearchable(): bool
    {
        return $this->is_active && ! $this->trashed();
    }

    // ── Scout: eager-load category before bulk import ─────────────────────────
    public function makeAllSearchableUsing($query)
    {
        return $query->with('category');
    }

    // ── Scout: document shape ─────────────────────────────────────────────────
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'tags' => $this->tags,
            'category_id'     => $this->category?->id,
            'category_name'   => $this->category?->name,
        ];
    }

   
}
