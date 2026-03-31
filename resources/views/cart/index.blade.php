@extends('layouts.app')

@push('styles')
    @vite("resources/css/cart.css")
@endpush

@section('content')
<div class="container py-4">


    {{-- ── Page Title ── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-bold mb-0">
            <i class="bi bi-cart3 me-2 text-primary"></i>Your Cart
        </h1>
        @if (!empty($items))
            <span class="badge bg-primary rounded-pill fs-6">
                {{ $count }} item(s)
            </span>
        @endif
    </div>

    @if (empty($items))

        {{-- ── Empty Cart ── --}}
        <div class="text-center py-5">
            <div class="empty-icon-wrap">
                <i class="bi bi-cart-x" style="font-size: 40px; color: #adb5bd;"></i>
            </div>
            <h4 class="fw-bold mb-2">Your cart is empty</h4>
            <p class="text-muted mb-4">
                Looks like you haven't added anything yet.<br>
                Browse our products and add something you like!
            </p>
            <a href="{{ route('products.index') }}" class="btn btn-primary px-4">
                <i class="bi bi-grid me-2"></i>Browse Products
            </a>
        </div>

    @else

        @php
            $freeShippingThreshold = 399;
            $shippingCost          = $total >= $freeShippingThreshold ? 0 : 50;
            $grandTotal            = $total + $shippingCost;
            $remaining             = max(0, $freeShippingThreshold - $total);
            $progressPercent       = min(100, ($total / $freeShippingThreshold) * 100);
        @endphp

        <div class="row g-4 align-items-start">

            {{-- ── Left: Cart Items ── --}}
            <div class="col-lg-8">

                {{-- Free Shipping Progress Bar --}}
                <div class="shipping-bar mb-3 p-3 bg-white rounded-3 border">
                    @if($total >= $freeShippingThreshold)
                        <div class="d-flex align-items-center gap-2 text-success">
                            <i class="bi bi-truck fs-5"></i>
                            <span class="fw-semibold small">
                                You get free shipping  on this order!
                            </span>
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
                </div>

                {{-- Clear Cart --}}
                <div class="d-flex justify-content-end mb-2">
                    <form action="{{ route('cart.clear') }}" method="POST"
                          onsubmit="return confirm('Clear all items from cart?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash3 me-1"></i>Clear Cart
                        </button>
                    </form>
                </div>

                {{-- ── Items List ── --}}
                <div class="cart-card">
                    @foreach ($items as $item)
                        <div class="cart-item p-3">
                            <div class="d-flex align-items-center gap-3">

                                {{-- Product Image --}}
                                <div class="item-img-placeholder">
                                    @if(!empty($item['image_url']))
                                        <img
                                            src="{{ $item['image_url'] }}"
                                            alt="{{ $item['name'] }}"
                                            onerror="this.style.display='none';
                                                     this.nextElementSibling.style.display='flex'"
                                        >
                                    @else
                                        <i class="bi bi-box-seam text-secondary"
                                           style="font-size:22px"></i>
                                    @endif
                                </div>

                                {{-- Product Info --}}
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-semibold text-truncate">
                                        {{ $item['name'] }}
                                    </div>
                                    <div class="text-muted small">
                                        Unit price:
                                        @if(!empty($item['original_price']) && $item['original_price'] > $item['price'])
                                            {{-- Has discount --}}
                                            <span class="text-decoration-line-through text-muted me-1">
                                                @currency($item['original_price'])
                                            </span>
                                            <strong class="text-success">
                                                @currency($item['price'])
                                            </strong>
                                            <span class="badge bg-success ms-1" style="font-size: 10px;">
                                                {{ round((($item['original_price'] - $item['price']) / $item['original_price']) * 100) }}% OFF
                                            </span>
                                        @else
                                            {{-- No discount --}}
                                            <strong class="text-dark">
                                                @currency($item['price'])
                                            </strong>
                                        @endif
                                    </div>
                                    {{--  Stock warning if low --}}
                                    @if($item['stock'] <= 5 && $item['stock'] > 0)
                                        <div class="text-warning small">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            Only {{ $item['stock'] }} left in stock
                                        </div>
                                    @elseif($item['stock'] === 0)
                                        <div class="text-danger small">
                                            <i class="bi bi-x-circle me-1"></i>
                                            Out of stock
                                        </div>
                                    @endif
                                </div>

                                {{-- ── Qty Controls ── --}}
                                <div class="d-flex align-items-center gap-1">

                                    {{--  Decrease — update quantity by current - 1 --}}
                                    <form action="{{ route('cart.update', $item['product_id']) }}"
                                          method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden"
                                               name="quantity"
                                               value="{{ $item['quantity'] - 1 }}">
                                        <button type="submit"
                                                class="btn btn-outline-secondary qty-btn"
                                                title="Decrease quantity"
                                                {{ $item['quantity'] <= 1 ? 'disabled' : '' }}>
                                            <i class="bi bi-dash"></i>
                                        </button>
                                    </form>

                                    {{--  Quantity display --}}
                                    <span class="qty-display">{{ $item['quantity'] }}</span>

                                    {{--  Increase — update quantity by current + 1 --}}
                                    <form action="{{ route('cart.update', $item['product_id']) }}"
                                          method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden"
                                               name="quantity"
                                               value="{{ $item['quantity'] + 1 }}">
                                        <button type="submit"
                                                class="btn btn-outline-secondary qty-btn"
                                                title="Increase quantity"
                                                {{ $item['quantity'] >= $item['stock'] ? 'disabled' : '' }}>
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </form>

                                </div>

                                {{--  Line subtotal --}}
                                <div class="text-end" style="min-width: 90px;">
                                    @if(!empty($item['original_price']) && $item['original_price'] > $item['price'])
                                        {{-- Show original subtotal crossed out --}}
                                        <div class="text-decoration-line-through text-muted small">
                                            @currency($item['original_price'] * $item['quantity'])
                                        </div>
                                        <div class="fw-bold text-success">
                                            @currency($item['subtotal'])
                                        </div>
                                    @else
                                        <div class="fw-bold text-dark">
                                            @currency($item['subtotal'])
                                        </div>
                                    @endif
                                    <div class="text-muted small">
                                        {{ $item['quantity'] }} × @currency($item['price'])
                                    </div>
                                </div>

                                {{--  Remove item --}}
                                <form action="{{ route('cart.remove', $item['product_id']) }}"
                                      method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger ms-1"
                                            title="Remove item"
                                            onclick="return confirm('Remove {{ $item['name'] }} from cart?')">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Continue Shopping --}}
                <div class="mt-3">
                    <a href="{{ route('products.index') }}"
                       class="text-decoration-none text-muted small">
                        <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                    </a>
                </div>

            </div>

            {{-- ── Right: Order Summary ── --}}
            <div class="col-lg-4">
                <div class="summary-card p-4">
                    <h5 class="fw-bold mb-3">Order Summary</h5>

                    {{-- ── Per-item subtotals ── --}}
                    @foreach ($items as $item)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small text-truncate me-2" style="max-width: 60%">
                                {{ $item['name'] }}
                                <span class="badge bg-light text-secondary border ms-1">
                                    ×{{ $item['quantity'] }}
                                </span>
                            </span>
                            <span class="small fw-medium text-end">
                                @if(!empty($item['original_price']) && $item['original_price'] > $item['price'])
                                    <span class="text-decoration-line-through text-muted d-block"
                                          style="font-size: 11px;">
                                        @currency($item['original_price'] * $item['quantity'])
                                    </span>
                                    <span class="text-success">
                                        @currency($item['subtotal'])
                                    </span>
                                @else
                                    @currency($item['subtotal'])
                                @endif
                            </span>
                        </div>
                    @endforeach

                    <div class="divider my-3"></div>

                    {{-- Subtotal --}}
                    <div class="d-flex justify-content-between mb-2 text-muted small">
                        <span>Subtotal ({{ $count }} items)</span>
                        <span>@currency($total)</span>
                    </div>

                    {{--  Discount row — only shows when discount is available --}}
                    @php
                        $totalOriginal = collect($items)->sum(fn($i) =>
                            ($i['original_price'] ?? $i['price']) * $i['quantity']
                        );
                        $totalSavings = $totalOriginal - $total;
                    @endphp
                    @if($totalSavings > 0)
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-success">
                                <i class="bi bi-tag me-1"></i>Discount
                            </span>
                            <span class="text-success fw-medium">
                                − @currency($totalSavings)
                            </span>
                        </div>
                    @endif

                    {{--  Shipping row --}}
                    <div class="d-flex justify-content-between mb-2 text-muted small">
                        <span>
                            <i class="bi bi-truck me-1"></i>Shipping
                        </span>
                        @if($shippingCost === 0)
                            <span class="text-success fw-medium">Free</span>
                        @else
                            <span>@currency($shippingCost)</span>
                        @endif
                    </div>

                    {{--  Discount earned badge --}}
                    @if($total >= $freeShippingThreshold)
                        <div class="discount-badge mb-3">
                            <i class="bi bi-gift me-1"></i>
                            free shipping on this order!
                        </div>
                    @else
                        <div class="discount-badge mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Spend <strong>@currency($remaining)</strong> more
                            to unlock free shipping
                        </div>
                    @endif

                    <div class="divider my-3"></div>

                    {{-- Grand Total --}}
                    <div class="d-flex justify-content-between total-row mb-4">
                        <span>Total</span>
                        <span class="text-primary">@currency($grandTotal)</span>
                    </div>

                </div>
            </div>

        </div>
    @endif

</div>
@endsection