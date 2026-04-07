@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('content')
<input type="hidden" value="{{ $order->id }}" id="orderId">
<div class="row justify-content-center">
    <div class="col-lg-10">

        @php
            $badgeMap = [
                'pending'    => 'warning',
                'processing' => 'primary',
                'shipped'    => 'info',
                'delivered'  => 'success',
                'cancelled'  => 'danger',
            ];
            $badge = $badgeMap[$order->status] ?? 'secondary';

            // Cancel allowed only before shipped
            $canCancel = in_array($order->status, ['pending', 'processing']);
        @endphp

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-0">Order {{ $order->order_number }}</h4>
                <small class="text-muted">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span id="orderStatusBadge"  class="badge bg-{{ $badge }} fs-6 px-3 py-2">{{ ucfirst($order->status) }}</span>

                <div id="cancelBtn">
                @if($canCancel)
                <form method="POST" action="{{ route('orders.cancel', $order) }}"
                      onsubmit="return confirm('Are you sure you want to cancel this order?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Cancel Order
                    </button>
                </form>
                @endif
                </div>
            </div>
        </div>

        {{-- Progress Tracker --}}
        @if($order->status !== 'cancelled')
        <div class="card shadow-sm mb-4" id="orderProgressCard">
            <div class="card-body">
                <h6 class="fw-bold mb-4">Order Progress</h6>
                @php
                    $steps = [
                        'pending'    => ['label' => 'Pending',    'icon' => 'bi-clock'],
                        'processing' => ['label' => 'Processing', 'icon' => 'bi-gear'],
                        'shipped'    => ['label' => 'Shipped',    'icon' => 'bi-truck'],
                        'delivered'  => ['label' => 'Delivered',  'icon' => 'bi-check-circle'],
                    ];
                    $stepKeys = array_keys($steps);
                    $currentIndex = array_search($order->status, $stepKeys);
                    if ($currentIndex === false) $currentIndex = -1;
                @endphp

                <div class="d-flex align-items-center">
                    @foreach($steps as $key => $step)
                        @php $index = array_search($key, $stepKeys); @endphp

                        <div class="text-center" style="flex:1">
                            <div id="step-circle-{{ $index }}"
                                class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1
                                    {{ $currentIndex >= $index ? 'bg-primary text-white' : 'bg-light text-muted' }}"
                                style="width:42px;height:42px;">
                                <i class="bi {{ $step['icon'] }}"></i>
                            </div>
                            <div id="step-label-{{ $index }}"
                                class="small {{ $currentIndex >= $index ? 'text-primary fw-semibold' : 'text-muted' }}">
                                {{ $step['label'] }}
                            </div>
                        </div>

                        @if(!$loop->last)
                            <div id="step-connector-{{ $index }}"
                                class="flex-fill mx-1"
                                style="height:3px;background:{{ $currentIndex > $index ? '#0d6efd' : '#dee2e6' }}">
                            </div>
                        @endif
                    @endforeach
                </div>

                <div id="cancelOrderWrapper">
                    @if($canCancel)
                        <p class="text-muted small text-center mb-0 mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            You can cancel this order until it has been shipped.
                        </p>
                    @endif
                </div>

            </div>
        </div>
        @endif

        <div class="row g-4 mb-4">

            {{-- Shipping Address --}}
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">
                            <i class="bi bi-geo-alt me-1"></i> Shipping Address
                        </h6>
                        <p class="fw-semibold mb-1">{{ $order->shipping_name }}</p>
                        <p class="text-muted small mb-1">
                            <i class="bi bi-telephone me-1"></i>{{ $order->shipping_phone }}
                        </p>
                        <p class="text-muted small mb-1">
                            <i class="bi bi-envelope me-1"></i>{{ $order->shipping_email }}
                        </p>
                        <p class="text-muted small mb-1">{{ $order->shipping_address }}</p>
                        <p class="text-muted small mb-0">
                            {{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Payment Info --}}
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">
                            <i class="bi bi-credit-card me-1"></i> Payment Info
                        </h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Method</span>
                            <span class="fw-semibold small">{{ strtoupper($order->payment_method) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Status</span>
                            <span class="badge {{ $order->payment_status == 'paid' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                        @if($order->discount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">You Saved</span>
                            <span class="fw-semibold small text-success">
                                <i class="bi bi-tag me-1"></i>@currency($order->discount)
                            </span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between pt-2 border-top">
                            <span class="text-muted small fw-semibold">Order Total</span>
                            <span class="fw-bold">@currency($order->total)</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Order Items Table --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-box-seam me-1"></i> Order Items
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Product</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        @php
                            $hasDiscount = !is_null($item->discount_price) && $item->discount_price < $item->price;
                        @endphp
                        <tr>
                            {{-- Product --}}
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    @if($item->product?->image)
                                        <img src="{{ asset('storage/' . $item->product->image) }}"
                                            class="rounded object-fit-cover border"
                                            style="width:48px;height:48px;" />
                                    @else
                                        <div class="rounded bg-light border d-flex align-items-center justify-content-center"
                                            style="width:48px;height:48px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <span class="fw-medium d-block">{{ $item->product_name }}</span>
                                        @if($hasDiscount)
                                            <small class="text-success">
                                                <i class="bi bi-tag me-1"></i>Saved
                                                @currency(($item->price - $item->discount_price) * $item->quantity)
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Unit Price --}}
                            <td>
                                @if($hasDiscount)
                                    <small class="text-muted text-decoration-line-through d-block">
                                        @currency($item->price)
                                    </small>
                                    <span class="fw-semibold text-success">
                                        @currency($item->discount_price)
                                    </span>
                                    @php
                                        $pct = round((($item->price - $item->discount_price) / $item->price) * 100);
                                    @endphp
                                    <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"
                                        style="font-size:10px;">
                                        -{{ $pct }}%
                                    </span>
                                @else
                                    @currency($item->price)
                                @endif
                            </td>

                            <td>{{ $item->quantity }}</td>
                            <td class="fw-medium">@currency($item->subtotal)</td>
                        </tr>
                        @endforeach
                    </tbody>

                    {{-- Totals --}}
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end text-muted pe-3">Subtotal</td>
                            <td>@currency($order->subtotal)</td>
                        </tr>
                        @if($order->discount > 0)
                        <tr>
                            <td colspan="3" class="text-end text-success pe-3">
                                <i class="bi bi-tag me-1"></i>Discount
                            </td>
                            <td class="text-success">&minus; @currency($order->discount)</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="text-end text-muted pe-3">Shipping</td>
                            <td class="text-success fw-semibold">Free</td>
                        </tr>
                        <tr class="border-top border-2">
                            <td colspan="3" class="text-end fw-bold pe-3">Total</td>
                            <td class="fw-bold fs-5">@currency($order->total)</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Notes --}}
        @if($order->notes)
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-2">
                    <i class="bi bi-chat-left-text me-1"></i> Order Notes
                </h6>
                <p class="text-muted mb-0">{{ $order->notes }}</p>
            </div>
        </div>
        @endif

        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to My Orders
        </a>

    </div>
</div>
@endsection

@if(!in_array($order->status, ['cancelled', 'delivered']))
    @push('scripts')
        @vite('resources/js/customer/orderStatus.js')
    @endpush
@endif