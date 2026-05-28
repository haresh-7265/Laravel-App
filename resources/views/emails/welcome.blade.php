@component('mail::message')

# Welcome, {{ $user->name }}!

Thank you for verifying your email address. We are excited to have you on board!

To celebrate, we have granted you an exclusive first-purchase discount code. Use it at checkout to save on your first order.

---

@component('mail::panel')
**Your First-Purchase Coupon Code**

# {{ $coupon->code }}

{{-- Discount value --}}
@if($coupon->type === 'percentage')
Save **{{ $coupon->value }}%** off your order
@else
Save **${{ number_format($coupon->value, 2) }}** off your order
@endif

{{-- Expiry --}}
@if($coupon->expires_at)
Expires on: **{{ \Carbon\Carbon::parse($coupon->expires_at)->format('d M Y') }}**
@else
No expiry date — use it anytime!
@endif

This coupon is valid for **1 use** on your first purchase.
@endcomponent

@component('mail::button', ['url' => route('products.index'), 'color' => 'success'])
Start Shopping
@endcomponent

---

If you have any questions or need assistance, feel free to reply directly to this email.

Thanks,  
**{{ config('app.name') }}**

@component('mail::subcopy')
You received this because you recently verified your email address on our platform.
@endcomponent

@endcomponent
