@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('content')

@php
    $statusMap = [
        'pending'    => ['color' => 'warning', 'icon' => 'bi-clock'],
        'processing' => ['color' => 'primary', 'icon' => 'bi-gear'],
        'shipped'    => ['color' => 'info',    'icon' => 'bi-truck'],
        'delivered'  => ['color' => 'success', 'icon' => 'bi-check-circle'],
        'cancelled'  => ['color' => 'danger',  'icon' => 'bi-x-circle'],
    ];
    $badge = $statusMap[$order->status] ?? ['color' => 'secondary', 'icon' => 'bi-circle'];
@endphp

{{-- Page Header --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.orders.index') }}" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Back to Orders
        </a>
        <h4 class="fw-bold mb-0 mt-1">
            <i class="bi bi-bag-check me-1"></i> {{ $order->order_number }}
        </h4>
    </div>

    {{-- Update Status Form --}}
    <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="d-flex gap-2 align-items-center">
        @csrf
        @method('PATCH')
        <select name="status" class="form-select form-select-sm" style="min-width: 150px;"
            {{ in_array($order->status, ['delivered', 'cancelled']) ? 'disabled' : '' }}>
        @php
            $allowedTransitions = [
                'pending'    => ['processing', 'cancelled'],
                'processing' => ['shipped',    'cancelled'],
                'shipped'    => ['delivered'],
                'delivered'  => [],
                'cancelled'  => [],
            ];
            $allowed = $allowedTransitions[$order->status] ?? [];
        @endphp

        @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
            <option value="{{ $status }}"
                {{ $order->status  == $status ? 'selected' : '' }}
                {{ !in_array($status, $allowed) && $order->status !== $status ? 'disabled' : '' }}>
                {{ ucfirst($status) }}
            </option>
        @endforeach
        </select>
        <button type="submit" class="btn btn-dark btn-sm px-3" {{ empty($allowed) ? 'disabled' : '' }}>
            <i class="bi bi-check2 me-1"></i> Update
        </button>
    </form>
</div>

{{-- Status + Meta Bar --}}
<div class="card shadow-sm mb-4 border-start border-4 border-{{ $badge['color'] }}">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-4 align-items-center">
            <div>
                <small class="text-muted d-block">Order Status</small>
                <span class="badge bg-{{ $badge['color'] }} fs-6 mt-1">
                    <i class="bi {{ $badge['icon'] }} me-1"></i>
                    {{ ucfirst($order->status) }}
                </span>
            </div>
            <div>
                <small class="text-muted d-block">Payment</small>
                <span class="badge {{ $order->payment_status == 'paid' ? 'bg-success' : 'bg-danger' }} fs-6 mt-1">
                    {{ ucfirst($order->payment_status) }}
                </span>
                <small class="text-muted ms-1">{{ strtoupper($order->payment_method) }}</small>
            </div>
            <div>
                <small class="text-muted d-block">Order Date</small>
                <span class="fw-medium">{{ $order->created_at->format('d M Y') }}</span>
                <small class="text-muted ms-1">{{ $order->created_at->format('h:i A') }}</small>
            </div>
            <div class="ms-auto text-end">
                <small class="text-muted d-block">Order Total</small>
                <span class="fw-bold fs-5">@currency($order->subtotal)</span>
                @if($order->discount > 0)
                    <small class="text-success d-block">
                        <i class="bi bi-tag me-1"></i>Saved @currency($order->discount)
                    </small>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Customer & Shipping --}}
<div class="row g-4 mb-4">

    {{-- Customer --}}
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light fw-semibold py-2">
                <i class="bi bi-person me-1"></i> Customer
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold"
                        style="width:46px;height:46px;font-size:18px;flex-shrink:0;">
                        {{ strtoupper(substr($order->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="mb-0 fw-semibold">{{ $order->user->name }}</p>
                        <small class="text-muted">{{ $order->user->email }}</small>
                    </div>
                </div>
                @if($order->notes)
                    <div class="alert alert-light border mt-3 mb-0 small">
                        <i class="bi bi-chat-left-text me-1 text-muted"></i>
                        <span class="fw-medium text-muted">Note:</span> {{ $order->notes }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Shipping --}}
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light fw-semibold py-2">
                <i class="bi bi-geo-alt me-1"></i> Shipping Address
            </div>
            <div class="card-body">
                <p class="mb-1 fw-semibold">{{ $order->shipping_name }}</p>
                <p class="mb-1 text-muted small">
                    <i class="bi bi-envelope me-1"></i>{{ $order->shipping_email }}
                </p>
                <p class="mb-1 text-muted small">
                    <i class="bi bi-telephone me-1"></i>{{ $order->shipping_phone }}
                </p>
                <p class="mb-1 text-muted small">
                    <i class="bi bi-house me-1"></i>{{ $order->shipping_address }}, {{ $order->shipping_city }}
                </p>
                <p class="mb-0 text-muted small">
                    <i class="bi bi-map me-1"></i>{{ $order->shipping_state }} &mdash; {{ $order->shipping_pincode }}
                </p>
            </div>
        </div>
    </div>

</div>

{{-- Order Items --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light fw-semibold py-2">
        <i class="bi bi-box me-1"></i> Order Items
        <span class="badge bg-secondary ms-2">{{ $order->items->count() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 45%">Product</th>
                        <th>Unit Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    @php
                        $hasDiscount = !is_null($item->product->discount_price) && $item->product->discount_price < $item->product->price;
                        $effectivePrice = $hasDiscount ? $item->product->discount_price : $item->product->price;
                    @endphp
                    <tr>
                        {{-- Product --}}
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                @if($item->product?->image)
                                    <img src="{{ asset('storage/' . $item->product->image) }}"
                                        class="rounded border object-fit-cover"
                                        style="width:40px;height:40px;" />
                                @else
                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center"
                                        style="width:40px;height:40px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                                <div>
                                    <span class="fw-medium d-block">{{ $item->product_name }}</span>
                                    @if($hasDiscount)
                                        <small class="text-success">
                                            <i class="bi bi-tag me-1"></i>Saved
                                            @currency(($item->product->price - $item->product->discount_price) * $item->quantity)
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Unit Price --}}
                        <td>
                            @if($hasDiscount)
                                <small class="text-muted text-decoration-line-through d-block">
                                    @currency($item->product->price)
                                </small>
                                <span class="fw-semibold text-success">
                                    @currency($item->product->discount_price)
                                </span>
                                @php
                                    $pct = round((($item->product->price - $item->product->discount_price) / $item->price) * 100);
                                @endphp
                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"
                                    style="font-size: 10px;">
                                    -{{ $pct }}%
                                </span>
                            @else
                                <span class="fw-medium">@currency($item->product->price)</span>
                            @endif
                        </td>

                        {{-- Qty --}}
                        <td>
                            <span class="badge bg-light text-dark border">{{ $item->quantity }}</span>
                        </td>

                        {{-- Subtotal --}}
                        <td class="fw-semibold">@currency($item->subtotal)</td>
                    </tr>
                    @endforeach
                </tbody>

                {{-- Totals Footer --}}
                <tfoot>
                    <tr class="table-light">
                        <td colspan="3" class="text-end text-muted py-2 pe-3">Subtotal</td>
                        <td class="py-2">@currency($order->subtotal)</td>
                    </tr>

                    @if($order->discount > 0)
                    <tr class="table-light">
                        <td colspan="3" class="text-end text-success py-2 pe-3">
                            <i class="bi bi-tag me-1"></i>Discount
                        </td>
                        <td class="text-success py-2">&minus; @currency($order->discount)</td>
                    </tr>
                    @endif

                    <tr class="table-light border-top border-2">
                        <td colspan="3" class="text-end fw-bold py-3 pe-3">Total</td>
                        <td class="fw-bold fs-5 py-3">@currency($order->total)</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection