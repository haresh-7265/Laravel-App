{{-- resources/views/cart/_shipping_bar.blade.php --}}
@php
    $freeShippingThreshold = (float) config('admin.freeShippingThreshold');
    $progressPercent = min(100, ($total / $freeShippingThreshold) * 100);
    $remaining       = max(0, $freeShippingThreshold - $total);
@endphp

@if($total >= $freeShippingThreshold)
    <div class="d-flex align-items-center gap-2 text-success">
        <i class="bi bi-truck fs-5"></i>
        <span class="fw-semibold small">You get free shipping on this order!</span>
    </div>
@else
    <div class="d-flex align-items-center justify-content-between mb-1">
        <span class="small text-muted">
            <i class="bi bi-truck me-1"></i>
            Add <strong>@currency($remaining)</strong> more for free shipping
        </span>
        <span class="small text-muted">
            @currency($total) / @currency($freeShippingThreshold)
        </span>
    </div>
    <div class="progress">
        <div class="progress-bar bg-success"
             role="progressbar"
             style="width: {{ $progressPercent }}%">
        </div>
    </div>
@endif