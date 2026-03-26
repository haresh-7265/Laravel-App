@extends('layouts.app')


@section('style')
<style>
        body { background-color: #f8f9fa; }

        .cart-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
        }

        .cart-item {
            border-bottom: 1px solid #f1f3f5;
            transition: background .15s;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item:hover { background-color: #fafafa; }

        .qty-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 16px;
            line-height: 1;
        }

        .qty-display {
            width: 40px;
            text-align: center;
            font-weight: 600;
            font-size: 15px;
        }

        .summary-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background: #fff;
            position: sticky;
            top: 20px;
        }

        .empty-icon-wrap {
            width: 90px;
            height: 90px;
            background: #f1f3f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .item-img-placeholder {
            width: 64px;
            height: 64px;
            background: #f1f3f5;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .divider { border-top: 1px solid #e9ecef; }
        .total-row { font-size: 18px; font-weight: 700; }

        @media (max-width: 767px) {
            .summary-card { position: static; margin-top: 20px; }
        }
    </style>
@endsection

@section('content')

    {{-- ── Page Title ── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-bold mb-0">
            <i class="bi bi-cart3 me-2 text-primary"></i>Your Cart
        </h1>
        @if (!empty($cart))
            <span class="badge bg-primary rounded-pill fs-6">
                {{ array_sum(array_column($cart, 'qty')) }} item(s)
            </span>
        @endif
    </div>

    @if (empty($cart))

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

        <div class="row g-4 align-items-start">

            {{-- ── Left: Cart Items ── --}}
            <div class="col-lg-8">

                {{-- Clear Cart Button --}}
                <div class="d-flex justify-content-end mb-2">
                    <form action="{{ route('cart.clear') }}" method="POST"
                          onsubmit="return confirm('Clear all items from cart?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash3 me-1"></i>Clear Cart
                        </button>
                    </form>
                </div>

                {{-- Items List --}}
                <div class="cart-card">
                    @foreach ($cart as $id => $item)
                        <div class="cart-item p-3">
                            <div class="d-flex align-items-center gap-3">

                                {{-- Product Image Placeholder --}}
                                <div class="item-img-placeholder">
                                    <i class="bi bi-box-seam text-secondary" style="font-size:22px"></i>
                                </div>

                                {{-- Product Info --}}
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-semibold text-truncate">{{ $item['name'] }}</div>
                                    <div class="text-muted small">
                                        Unit price: <strong class="text-dark">@currency($item['price'])</strong>
                                    </div>
                                </div>

                                {{-- Qty Controls --}}
                                <div class="d-flex align-items-center gap-1">

                                    {{-- Decrease --}}
                                    <form action="{{ route('cart.remove', $id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-outline-secondary qty-btn"
                                                title="Decrease quantity">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                    </form>

                                    {{-- Quantity Display --}}
                                    <span class="qty-display">{{ $item['qty'] }}</span>

                                    {{-- Increase --}}
                                    <form action="{{ route('cart.add', $id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-outline-secondary qty-btn"
                                                title="Increase quantity">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </form>

                                </div>

                                {{-- Line Total --}}
                                <div class="text-end" style="min-width: 90px;">
                                    <div class="fw-bold text-dark">
                                        @currency($item['price'] * $item['qty'])
                                    </div>
                                    <div class="text-muted small">
                                        {{ $item['qty'] }} × @currency($item['price'])
                                    </div>
                                </div>

                                {{-- Remove Button --}}
                                <form action="{{ route('cart.delete', $id) }}" method="POST">
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
                    <a href="{{ route('products.index') }}" class="text-decoration-none text-muted small">
                        <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                    </a>
                </div>

            </div>

            {{-- ── Right: Order Summary ── --}}
            <div class="col-lg-4">
                <div class="summary-card p-4">
                    <h5 class="fw-bold mb-3">Order Summary</h5>

                    {{-- Per-item Subtotals --}}
                    @php
                        $grandTotal = 0;
                        $totalQty   = 0;
                    @endphp

                    @foreach ($cart as $id => $item)
                        @php
                            $lineTotal   = $item['price'] * $item['qty'];
                            $grandTotal += $lineTotal;
                            $totalQty   += $item['qty'];
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small text-truncate me-2" style="max-width:60%">
                                {{ $item['name'] }}
                                <span class="badge bg-light text-secondary border ms-1">×{{ $item['qty'] }}</span>
                            </span>
                            <span class="small fw-medium">@currency($lineTotal)</span>
                        </div>
                    @endforeach

                    <div class="divider my-3"></div>

                    {{-- Totals --}}
                    <div class="d-flex justify-content-between mb-2 text-muted small">
                        <span>Subtotal ({{ $totalQty }} items)</span>
                        <span>@currency($grandTotal)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-muted small">
                        <span>Shipping</span>
                        <span class="text-success fw-medium">Free</span>
                    </div>

                    <div class="divider my-3"></div>

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