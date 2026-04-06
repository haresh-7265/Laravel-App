@component('mail::message')

# Hello, {{ $user->name }}!

We have an exclusive discount ready just for you. Use the coupon code below at checkout and save on your next order.

---

@component('mail::panel')
**Your Coupon Code**

# {{ $coupon->code }}

{{-- Discount value --}}
@if($coupon->type === 'percentage')
Save **{{ $coupon->value }}%** off your order
@else
Save **@currency($coupon->value)** off your order
@endif

{{-- Minimum order --}}
@if($coupon->min_order_amount > 0)
Minimum order amount: **@currency($coupon->min_order_amount)**
@endif

{{-- Expiry --}}
@if($coupon->expires_at)
Expires on: **{{ \Carbon\Carbon::parse($coupon->expires_at)->format('d M Y') }}**
@else
No expiry date — use it anytime!
@endif

{{-- Usage limit --}}
This coupon can be used **{{ $usageLimit }} time{{ $usageLimit > 1 ? 's' : '' }}**.
@endcomponent

@component('mail::button', ['url' => route('cart.index'), 'color' => 'primary'])
Shop Now
@endcomponent

---

If you have any questions, just reply to this email — we are happy to help.

Thanks,
**{{ config('app.name') }}**

@component('mail::subcopy')
You are receiving this email because an exclusive coupon was assigned to your account.
If you did not expect this, you can safely ignore it.
@endcomponent

@endcomponent