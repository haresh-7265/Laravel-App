@extends('layouts.app')

@push('styles')
    @vite("resources/css/cart.css")
@endpush

@section('content')
<div class="container py-4" id="cart-root">

    {{-- ── Page Title ── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-bold mb-0">
            <i class="bi bi-cart3 me-2 text-primary"></i>Your Cart
        </h1>
        <span class="badge bg-primary rounded-pill fs-6" id="cart-count-badge"
              style="{{ empty($items) ? 'display:none' : '' }}">
            <span id="cart-count-text">{{ $count ?? 0 }}</span> item(s)
        </span>
    </div>

    {{-- ── Empty State (shown/hidden via JS) ── --}}
    <div id="empty-cart" class="{{ !empty($items) ? 'd-none' : '' }} text-center py-5">
        <div class="empty-cart-icon">
            <i class="bi bi-cart-x" style="font-size:36px; color:#94a3b8;"></i>
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

    {{-- ── Main Cart Layout ── --}}
    <div id="cart-layout" class="{{ empty($items) ? 'd-none' : '' }} row g-4 align-items-start">

        {{-- ══════════════════════════════════
             LEFT — Cart Items
        ══════════════════════════════════ --}}
        <div class="col-lg-8">

            {{-- Free-shipping progress bar --}}
            <div class="shipping-bar mb-3 p-3" id="shipping-bar">
                @include('cart._shipping_bar', [
                    'total'                  => $total ?? 0,
                ])
            </div>

            {{-- Clear cart button --}}
            <div class="d-flex justify-content-end mb-2">
                <button id="clear-cart-btn"
                        class="btn btn-sm btn-outline-danger"
                        data-url="{{ route('cart.clear') }}">
                    <i class="bi bi-trash3 me-1"></i>Clear Cart
                </button>
            </div>

            {{-- Items list --}}
            <div class="cart-card" id="cart-items-list">
                @forelse ($items as $item)
                    @include('cart._item', ['item' => $item])
                @empty
                @endforelse
            </div>

            <div class="mt-3">
                <a href="{{ route('products.index') }}" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                </a>
            </div>
        </div>

        {{-- ══════════════════════════════════
             RIGHT — Order Summary
        ══════════════════════════════════ --}}
        <div class="col-lg-4">
            <div class="summary-card p-4" id="order-summary">
                @include('cart._summary', [
                    'items' => $items ?? [],
                    'total' => $total ?? 0,
                    'count' => $count ?? 0,
                ])
            </div>

            <a href="{{ route('orders.checkout') }}"
               class="btn btn-primary w-100 fw-semibold mt-3"
               id="checkout-btn">
                <i class="bi bi-lock me-2"></i>Proceed to Checkout
            </a>
        </div>

    </div>{{-- /cart-layout --}}

</div>{{-- /container --}}

{{-- Toast container --}}
<div id="cart-toast-container"></div>
@endsection


@push('scripts')
    @vite('resources/js/cart.js')
@endpush