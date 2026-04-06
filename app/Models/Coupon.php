<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'min_order_amount', 'expires_at', 'is_active'];

    protected $casts = ['expires_at' => 'datetime'];

    public function users()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('usage_limit', 'used_count')
                    ->withTimestamps();
    }

    // Check if coupon is valid for a specific user
    public function isValidForUser(User $user): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'Coupon is inactive.'];
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return ['valid' => false, 'message' => 'Coupon has expired.'];
        }

        // Check if coupon is assigned to this user
        $pivot = $this->users()->where('user_id', $user->id)->first()?->pivot;

        if (!$pivot) {
            return ['valid' => false, 'message' => 'Coupon is not assigned to your account.'];
        }

        if ($pivot->used_count >= $pivot->usage_limit) {
            return ['valid' => false, 'message' => 'You have exhausted your usage limit for this coupon.'];
        }

        return ['valid' => true, 'message' => 'Coupon is valid.'];
    }

    public function calculateDiscount($cartTotal): float
    {
        if ($cartTotal < $this->min_order_amount) return 0;

        if ($this->type === 'percentage') {
            return round(($cartTotal * $this->value) / 100, 2);
        }

        return min($this->value, $cartTotal);
    }
}
