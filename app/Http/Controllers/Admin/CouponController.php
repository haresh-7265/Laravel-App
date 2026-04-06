<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    // List all coupons
    public function index()
    {
        $coupons = Coupon::with('users')->latest()->get();
        return view('admin.coupons.index', compact('coupons'));
    }

    // Create coupon
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:1',
            'min_order_amount' => 'nullable|numeric',
            'expires_at' => 'nullable|date',
        ]);

        Coupon::create($request->all());

        return back()->with('success', 'Coupon created.');
    }

    // Assign coupon to a user with usage limit
    public function assignToUser(Request $request)
    {
        $request->validate([
            'coupon_id' => 'required|exists:coupons,id',
            'user_id' => 'required|exists:users,id',
            'usage_limit' => 'required|integer|min:1',
        ]);

        $coupon = Coupon::findOrFail($request->coupon_id);

        // Sync or attach with usage limit
        $existing = $coupon->users()->where('user_id', $request->user_id)->first();

        if ($existing) {
            // Update usage limit if already assigned
            $coupon->users()->updateExistingPivot($request->user_id, [
                'usage_limit' => $request->usage_limit,
            ]);
        } else {
            $coupon->users()->attach($request->user_id, [
                'usage_limit' => $request->usage_limit,
                'used_count' => 0,
            ]);
        }

        return back()->with('success', 'Coupon assigned to user.');
    }

    // Revoke coupon from user
    public function revokeFromUser(Request $request)
    {
        $coupon = Coupon::findOrFail($request->coupon_id);
        $coupon->users()->detach($request->user_id);

        return back()->with('success', 'Coupon revoked from user.');
    }
}
