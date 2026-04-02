<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal',
        'discount', 'total', 'payment_method', 'payment_status',
        'notes', 'shipping_name', 'shipping_email', 'shipping_phone',
        'shipping_address', 'shipping_city', 'shipping_state', 'shipping_pincode',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Helper
    public function generateOrderNumber(): string
    {
        return 'ORD-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    // Status badge color for blade
    public function statusColor(): string
    {
        return match($this->status) {
            'pending'    => 'yellow',
            'processing' => 'blue',
            'shipped'    => 'purple',
            'delivered'  => 'green',
            'cancelled'  => 'red',
            default      => 'gray',
        };
    }

    public function getRouteKeyName()
    {
        return 'order_number';
    }
}