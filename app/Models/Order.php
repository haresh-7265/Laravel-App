<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal',
        'discount', 'coupon_code', 'coupon_discount', 'total',
        'payment_method', 'payment_status', 'invoice_path',
        'notes', 'shipping_name', 'shipping_email', 'shipping_phone',
        'shipping_address', 'billing_address', 'shipping_city', 'shipping_state', 'shipping_pincode',
        'created_by', 'updated_by',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::saving(function ($order) {
            if ($order->isDirty('shipping_phone')) {
                $order->shipping_phone_blind_index = $order->shipping_phone
                    ? hash_hmac('sha256', $order->shipping_phone, env('BLIND_INDEX_SECRET', 'blind_index_secret_key'))
                    : null;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'shipping_address' => 'encrypted',
            'billing_address' => 'encrypted',
            'shipping_phone' => 'encrypted',
        ];
    }

    /**
     * Scope a query to find orders by shipping phone number using the blind index.
     */
    public function scopeWhereShippingPhone($query, $phone)
    {
        $hash = $phone ? hash_hmac('sha256', $phone, env('BLIND_INDEX_SECRET', 'blind_index_secret_key')) : null;

        return $query->where('shipping_phone_blind_index', $hash);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper
    public function generateOrderNumber(): string
    {
        return 'ORD-'.str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    // Status badge color for blade
    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'shipped' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getRouteKeyName()
    {
        return 'order_number';
    }
}
