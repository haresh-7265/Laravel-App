<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
    ];

    // ─── Relationships ────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Accessors ────────────────────────────────────────────

    // Subtotal for this cart line
    public function getSubtotalAttribute(): float
    {
        return ($this->product->discount_price ?? $this->product->price) * $this->quantity;
    }

    // Image URL — works for both public and private disks
    public function getImageUrlAttribute(): string
    {
        if (!$this->product->image) {
            return Storage::disk('public')->url('products/default.png');
        }

        return Storage::url($this->product->image);
    }
}