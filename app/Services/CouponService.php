<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate coupon for a user
     */
    public function validate(string $code, User $user, float $cartTotal): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            return $this->response(false, 'Coupon not found.');
        }

        if (!$coupon->is_active) {
            return $this->response(false, 'Coupon is inactive.');
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return $this->response(false, 'Coupon has expired.');
        }

        $pivot = $coupon->users()->where('user_id', $user->id)->first()?->pivot;

        if (!$pivot) {
            return $this->response(false, 'Coupon is not assigned to your account.');
        }

        if ($pivot->used_count >= $pivot->usage_limit) {
            return $this->response(false, 'You have exhausted your usage limit for this coupon.');
        }

        if ($cartTotal < $coupon->min_order_amount) {
            return $this->response(false, "Minimum order amount is ₹{$coupon->min_order_amount}");
        }

        $discount = $this->calculateDiscount($coupon, $cartTotal);

        return $this->response(true, 'Coupon is valid.', [
            'coupon'    => $coupon,
            'discount'  => $discount,
            'new_total' => $cartTotal - $discount,
        ]);
    }

    /**
     * Assign coupon to a user
     */
    public function assignToUser(Coupon $coupon, User $user, int $usageLimit = 1): array
    {
        $existing = $coupon->users()->where('user_id', $user->id)->first();

        if ($existing) {
            $coupon->users()->updateExistingPivot($user->id, [
                'usage_limit' => $usageLimit,
            ]);

            return $this->response(true, "Coupon usage limit updated for {$user->name}.");
        }

        $coupon->users()->attach($user->id, [
            'usage_limit' => $usageLimit,
            'used_count'  => 0,
        ]);

        return $this->response(true, "Coupon assigned to {$user->name}.");
    }

    /**
     * Assign coupon to multiple users at once
     */
    public function assignToUsers(Coupon $coupon, array $userIds, int $usageLimit = 1): array
    {
        $assigned = 0;
        $updated  = 0;

        foreach ($userIds as $userId) {
            $existing = $coupon->users()->where('user_id', $userId)->first();

            if ($existing) {
                $coupon->users()->updateExistingPivot($userId, [
                    'usage_limit' => $usageLimit,
                ]);
                $updated++;
            } else {
                $coupon->users()->attach($userId, [
                    'usage_limit' => $usageLimit,
                    'used_count'  => 0,
                ]);
                $assigned++;
            }
        }

        return $this->response(true, "Assigned to {$assigned} users, updated {$updated} users.");
    }

    /**
     * Redeem coupon — increment used count after order placed
     */
    public function redeem(Coupon $coupon, User $user): array
    {
        $pivot = $coupon->users()->where('user_id', $user->id)->first()?->pivot;

        if (!$pivot) {
            return $this->response(false, 'Coupon not assigned to this user.');
        }

        if ($pivot->used_count >= $pivot->usage_limit) {
            return $this->response(false, 'Usage limit already reached.');
        }

        $coupon->users()->updateExistingPivot($user->id, [
            'used_count' => DB::raw('used_count + 1'),
        ]);

        return $this->response(true, 'Coupon redeemed successfully.');
    }

    /**
     * Revoke coupon from a user
     */
    public function revokeFromUser(Coupon $coupon, User $user): array
    {
        $exists = $coupon->users()->where('user_id', $user->id)->exists();

        if (!$exists) {
            return $this->response(false, 'Coupon was not assigned to this user.');
        }

        $coupon->users()->detach($user->id);

        return $this->response(true, "Coupon revoked from {$user->name}.");
    }

    /**
     * Revoke expired coupons from all users
     */
    public function revokeExpired(): array
    {
        $expired = Coupon::where('expires_at', '<', now())
                         ->where('is_active', true)
                         ->get();

        foreach ($expired as $coupon) {
            $coupon->update(['is_active' => false]);
        }

        return $this->response(true, "{$expired->count()} expired coupons deactivated.");
    }

    /**
     * Get all coupons assigned to a user
     */
    public function getUserCoupons(User $user): array
    {
        $coupons = $user->coupons()
                        ->withPivot('usage_limit', 'used_count')
                        ->where('is_active', true)
                        ->get()
                        ->map(fn($c) => [
                            'code'        => $c->code,
                            'type'        => $c->type,
                            'value'       => $c->value,
                            'used'        => $c->pivot->used_count,
                            'limit'       => $c->pivot->usage_limit,
                            'remaining'   => $c->pivot->usage_limit - $c->pivot->used_count,
                            'expires_at'  => $c->expires_at?->toDateString(),
                        ]);

        return $this->response(true, 'User coupons fetched.', ['coupons' => $coupons]);
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(Coupon $coupon, float $cartTotal): float
    {
        if ($coupon->type === 'percentage') {
            return round(($cartTotal * $coupon->value) / 100, 2);
        }

        return min($coupon->value, $cartTotal);
    }

    /**
     * Consistent response format
     */
    private function response(bool $success, string $message, array $data = []): array
    {
        return array_merge(['success' => $success, 'message' => $message], $data);
    }
}