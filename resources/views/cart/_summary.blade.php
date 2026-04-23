{{-- resources/views/cart/_summary.blade.php --}}
@php
    $freeShippingThreshold = (float) config('admin.freeShippingThreshold');
    $shippingCost = $total >= $freeShippingThreshold ? 0 : 50;
    $couponDiscountAmount = $couponDiscount ?? 0;
    $grandTotal = $total - $couponDiscountAmount + $shippingCost;
    $remaining = max(0, $freeShippingThreshold - $total);

    $totalOriginal = collect($items)->sum(
        fn($i) =>
        ($i['original_price'] ?? $i['price']) * $i['quantity']
    );
    $totalSavings = $totalOriginal - $total;
    $appliedCoupon = $coupon ?? null;
@endphp

<h5 class="fw-bold mb-3">Order Summary</h5>

{{-- Per-item rows --}}
@foreach ($items as $item)
    <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="text-muted small text-truncate me-2" style="max-width:60%">
            {{ $item['name'] }}
            <span class="badge bg-light text-secondary border ms-1">×{{ $item['quantity'] }}</span>
        </span>
        <span class="small fw-medium text-end">
            @if(!empty($item['original_price']) && $item['original_price'] > $item['price'])
                <span class="text-decoration-line-through text-muted d-block" style="font-size:11px;">
                    @currency($item['original_price'] * $item['quantity'])
                </span>
                <span class="text-success">@currency($item['subtotal'])</span>
            @else
                @currency($item['subtotal'])
            @endif
        </span>
    </div>
@endforeach

<hr class="summary-divider">

{{-- Subtotal --}}
<div class="d-flex justify-content-between mb-2 text-muted small">
    <span>Subtotal ({{ $count }} items)</span>
    <span>@currency($total)</span>
</div>

{{-- Discount --}}
@if($totalSavings > 0)
    <div class="d-flex justify-content-between mb-2 small">
        <span class="text-success"><i class="bi bi-tag me-1"></i>Discount</span>
        <span class="text-success fw-medium">− @currency($totalSavings)</span>
    </div>
@endif

{{-- Coupon Discount --}}
@if($appliedCoupon && $couponDiscountAmount > 0)
    <div class="d-flex justify-content-between mb-2 small" id="coupon-discount-row">
        <span class="text-success">
            <i class="bi bi-ticket-perforated me-1"></i>Coupon
            <span class="badge bg-success bg-opacity-10 text-success ms-1">{{ $appliedCoupon['code'] }}</span>
        </span>
        <span class="text-success fw-medium">− @currency($couponDiscountAmount)</span>
    </div>
@endif

{{-- Shipping --}}
<div class="d-flex justify-content-between mb-2 text-muted small">
    <span><i class="bi bi-truck me-1"></i>Shipping</span>
    @if($shippingCost === 0)
        <span class="text-success fw-medium">Free</span>
    @else
        <span>@currency($shippingCost)</span>
    @endif
</div>

{{-- Free shipping nudge --}}
@if($total >= $freeShippingThreshold)
    <div class="discount-badge mb-3">
        <i class="bi bi-gift me-1"></i>You've unlocked free shipping!
    </div>
@else
    <div class="discount-badge mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Spend <strong>@currency($remaining)</strong> more for free shipping
    </div>
@endif

{{-- ═══ Coupon Input / Applied Badge ═══ --}}
@auth
    <div class="coupon-section mb-3" id="coupon-section">
        @if($appliedCoupon)
            {{-- Applied coupon badge --}}
            <div class="coupon-applied-badge" id="coupon-applied">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div>
                            <span class="fw-semibold text-success">{{ $appliedCoupon['code'] }}</span>
                            <br>
                            <small class="text-muted">
                                @if($appliedCoupon['type'] === 'percentage')
                                    {{ $appliedCoupon['value'] }}% off
                                @else
                                    ₹{{ number_format($appliedCoupon['value'], 2) }} off
                                @endif
                                — saving ₹{{ number_format($couponDiscountAmount, 2) }}
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" id="remove-coupon-btn"
                        data-url="{{ route('cart.coupon.remove') }}" title="Remove coupon">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        @else
            {{-- Coupon input --}}
            <div id="coupon-input-wrap">
                <label class="form-label small fw-semibold text-muted mb-1">
                    <i class="bi bi-ticket-perforated me-1"></i>Have a coupon?
                </label>
                <div class="input-group coupon-input-group">
                    <input type="text" id="coupon-code-input" class="form-control form-control-sm"
                        placeholder="Enter coupon code" maxlength="50" autocomplete="off" />
                    <button type="button" class="btn btn-sm btn-primary" id="apply-coupon-btn"
                        data-url="{{ route('cart.coupon.apply') }}">
                        Apply
                    </button>
                </div>
                <div id="coupon-feedback" class="small mt-1" style="display:none;"></div>
            </div>
        @endif
    </div>
@endauth

<hr class="summary-divider">

{{-- Grand Total --}}
<div class="d-flex justify-content-between total-row">
    <span>Total</span>
    <span class="text-primary">@currency($grandTotal)</span>
</div>