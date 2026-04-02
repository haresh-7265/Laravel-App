@extends('layouts.app')

@section('title', 'My Orders')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">My Orders</h4>
            <a href="{{ route('products.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-bag me-1"></i> Continue Shopping
            </a>
        </div>

        @forelse($orders as $order)
        <div class="card mb-3 shadow-sm">

            {{-- Order Header --}}
            <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex gap-4 flex-wrap">
                    <div>
                        <small class="text-muted d-block">Order Number</small>
                        <strong>{{ $order->order_number }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Date</small>
                        <strong>{{ $order->created_at->format('d M Y') }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total</small>
                        <strong>@currency($order->total)</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Payment</small>
                        <strong class="{{ $order->payment_status == 'paid' ? 'text-success' : 'text-danger' }}">
                            {{ ucfirst($order->payment_status) }}
                        </strong>
                    </div>
                </div>

                @php
                    $badgeMap = [
                        'pending'    => 'warning',
                        'processing' => 'primary',
                        'shipped'    => 'info',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                    ];
                    $badge = $badgeMap[$order->status] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $badge }} fs-6">{{ ucfirst($order->status) }}</span>
            </div>

            {{-- Order Items Preview --}}
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    @foreach($order->items->take(3) as $item)
                        @if($item->product?->image)
                        <img src="{{ asset('storage/' . $item->product->image) }}"
                            class="rounded border object-fit-cover"
                            style="width:50px;height:50px;"
                            title="{{ $item->product_name }}" />
                        @else
                        <div class="rounded border bg-light d-flex align-items-center justify-content-center"
                            style="width:50px;height:50px;">
                            <i class="bi bi-image text-muted"></i>
                        </div>
                        @endif
                    @endforeach

                    @if($order->items->count() > 3)
                    <div class="rounded border bg-light d-flex align-items-center justify-content-center"
                        style="width:50px;height:50px;">
                        <small class="text-muted">+{{ $order->items->count() - 3 }}</small>
                    </div>
                    @endif

                    <div class="ms-2 d-flex align-items-center text-muted small">
                        {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                    </div>
                </div>

                <a href="{{ route('orders.show', $order) }}"
                    class="btn btn-outline-primary btn-sm">
                    View Details <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
        @empty

        {{-- Empty State --}}
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bag-x display-1 text-muted"></i>
                <h5 class="mt-3 fw-bold">No Orders Yet</h5>
                <p class="text-muted mb-4">Looks like you haven't placed any orders yet. Start shopping!</p>
                <a href="{{ route('products.index') }}" class="btn btn-dark">
                    <i class="bi bi-bag me-1"></i> Browse Products
                </a>
            </div>
        </div>

        @endforelse

    </div>
</div>
@endsection