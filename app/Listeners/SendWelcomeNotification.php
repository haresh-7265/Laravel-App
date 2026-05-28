<?php

namespace App\Listeners;

use App\Events\UserVerified;
use App\Models\Coupon;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendWelcomeNotification
{
    /**
     * Handle the event.
     */
    public function handle(UserVerified $event): void
    {
        $user = $event->user;

        if(!($user instanceof User) || !$user->hasRole('customer') ){
            return;
        }
        // 1. Get or create first-purchase discount code
        $coupon = Coupon::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_amount' => 0.00,
                'expires_at' => now()->addDays(30),
                'is_active' => true,
            ]
        );

        // 2. Grant coupon to the user if not already granted
        $alreadyHasCoupon = $user->coupons()->where('coupon_id', $coupon->id)->exists();
        if (!$alreadyHasCoupon) {
            $user->coupons()->attach($coupon->id, [
                'usage_limit' => 1,
                'used_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Send welcome email
        Mail::to($user->email)->send(new WelcomeUserMail($user, $coupon));
    }
}
