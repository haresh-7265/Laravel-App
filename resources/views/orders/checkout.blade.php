@extends('layouts.app')

@section('title', 'Checkout')

@section('content')

@php $cartService = app(\App\Services\CartService::class); @endphp

{{-- Empty Cart Check --}}
@if($cartService->count() === 0)
<div class="row justify-content-center">
    <div class="col-md-6 text-center py-5">
        <i class="bi bi-cart-x display-1 text-muted"></i>
        <h5 class="mt-3 fw-bold">Your Cart is Empty</h5>
        <p class="text-muted mb-4">Add some products to your cart before checkout.</p>
        <a href="{{ route('products.index') }}" class="btn btn-dark">
            <i class="bi bi-bag me-1"></i> Browse Products
        </a>
    </div>
</div>
@else

<div class="row justify-content-center">
    <div class="col-lg-10">

        <h4 class="fw-bold mb-4">Checkout</h4>

        <form method="POST" action="{{ route('orders.store') }}">
            @csrf
            <div class="row g-4">

                {{-- LEFT: Shipping + Payment --}}
                <div class="col-lg-8">

                    {{-- Shipping Details --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light fw-bold">
                            <i class="bi bi-geo-alt me-1"></i> Shipping Details
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name"
                                        value="{{ old('name', auth()->user()->name) }}"
                                        class="form-control @error('name') is-invalid @enderror" />
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email"
                                        value="{{ old('email', auth()->user()->email) }}"
                                        class="form-control @error('email') is-invalid @enderror" />
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone"
                                        value="{{ old('phone') }}"
                                        placeholder="Enter phone number"
                                        class="form-control @error('phone') is-invalid @enderror" />
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="pincode"
                                        value="{{ old('pincode') }}"
                                        placeholder="Enter pincode"
                                        class="form-control @error('pincode') is-invalid @enderror" />
                                    @error('pincode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" rows="2"
                                        placeholder="House no, Street, Area..."
                                        class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city"
                                        value="{{ old('city') }}"
                                        placeholder="Enter city"
                                        class="form-control @error('city') is-invalid @enderror" />
                                    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state"
                                        value="{{ old('state') }}"
                                        placeholder="Enter state"
                                        class="form-control @error('state') is-invalid @enderror" />
                                    @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light fw-bold">
                            <i class="bi bi-credit-card me-1"></i> Payment Method
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-3">

                                <label class="d-flex align-items-center gap-3 border rounded p-3 cursor-pointer"
                                    style="cursor:pointer">
                                    <input type="radio" name="payment_method" value="cod"
                                        class="form-check-input mt-0" checked />
                                    <div>
                                        <p class="fw-semibold mb-0"><i class="bi bi-cash-coin me-1"></i> Cash on Delivery</p>
                                        <small class="text-muted">Pay when you receive your order</small>
                                    </div>
                                </label>

                                <label class="d-flex align-items-center gap-3 border rounded p-3"
                                    style="cursor:pointer">
                                    <input type="radio" name="payment_method" value="online"
                                        class="form-check-input mt-0" />
                                    <div>
                                        <p class="fw-semibold mb-0"><i class="bi bi-phone me-1"></i> Online Payment</p>
                                        <small class="text-muted">Pay via UPI, Card, or Net Banking</small>
                                    </div>
                                </label>

                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light fw-bold">
                            <i class="bi bi-chat-left-text me-1"></i> Order Notes
                            <span class="fw-normal text-muted small">(Optional)</span>
                        </div>
                        <div class="card-body">
                            <textarea name="notes" rows="3"
                                placeholder="Any special instructions for your order..."
                                class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                </div>

                {{-- RIGHT: Order Summary --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top:20px">
                        <div class="card-header bg-light fw-bold">
                            <i class="bi bi-receipt me-1"></i> Order Summary
                        </div>
                        <div class="card-body">

                            {{-- Cart Items --}}
                            <div class="mb-3">
                                @foreach($cartService->get() as $item)
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    @if($item['image'] ?? null)
                                    <img src="{{ asset('storage/' . $item['image']) }}"
                                        class="rounded border object-fit-cover"
                                        style="width:48px;height:48px;" />
                                    @else
                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center"
                                        style="width:48px;height:48px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                    @endif
                                    <div class="flex-fill">
                                        <p class="fw-medium mb-0 small">{{ $item['name'] }}</p>
                                        <small class="text-muted">Qty: {{ $item['quantity'] }}</small>
                                    </div>
                                    <span class="small fw-semibold">
                                        @currency(($item['discount_price'] ?? $item['price']) * $item['quantity'])
                                    </span>
                                </div>
                                @endforeach
                            </div>

                            {{-- Totals --}}
                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Subtotal</span>
                                    <span>@currency($cartService->totalPrice())</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Shipping</span>
                                    <span class="text-success fw-semibold">Free</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2">
                                    <span>Total</span>
                                    <span class="fs-5">@currency($cartService->totalPrice())</span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 mt-3">
                                <i class="bi bi-bag-check me-1"></i> Place Order
                            </button>

                            <a href="{{ route('cart.index') }}"
                                class="btn btn-outline-secondary w-100 mt-2 btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> Back to Cart
                            </a>

                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
@endif

@endsection