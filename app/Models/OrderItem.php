<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name',
        'price', 'discount_price', 'quantity', 'subtotal',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Get effective price
    public function effectivePrice(): float
    {
        return $this->discount_price ?? $this->price;
    }
}