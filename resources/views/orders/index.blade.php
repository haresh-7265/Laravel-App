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

        {{-- Order Statistics --}}
        @if($totalOrders > 0)
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-primary text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-white text-primary rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 50px; height: 50px;">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-white-50">Total Orders</h6>
                            <h4 class="mb-0 fw-bold">{{ $totalOrders }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-success text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-white text-success rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 50px; height: 50px;">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-white-50">Total Spent</h6>
                            <h4 class="mb-0 fw-bold">@currency($totalSpent)</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-info text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-white text-info rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 50px; height: 50px;">
                            <i class="bi bi-calculator fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-white-50">Average Value</h6>
                            <h4 class="mb-0 fw-bold">@currency($averageOrderValue)</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            {{-- Order Status Breakdown --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-0">
                        <h6 class="fw-bold"><i class="bi bi-pie-chart me-2"></i>Orders by Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @php
                                $badgeMap = [
                                    'pending'    => 'warning',
                                    'processing' => 'primary',
                                    'shipped'    => 'info',
                                    'delivered'  => 'success',
                                    'cancelled'  => 'danger',
                                ];
                            @endphp
                            @foreach($ordersByStatus as $status => $count)
                                @php $badge = $badgeMap[$status] ?? 'secondary'; @endphp
                                <div class="border rounded px-3 py-2 d-flex align-items-center flex-grow-1">
                                    <span class="badge bg-{{ $badge }} me-2" style="width: 10px; height: 10px; border-radius: 50%; padding: 0;">&nbsp;</span>
                                    <span class="text-capitalize flex-grow-1">{{ $status }}</span>
                                    <strong class="ms-2 fs-5">{{ $count }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Ordered Products --}}
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-0">
                        <h6 class="fw-bold"><i class="bi bi-star-fill text-warning me-2"></i>Favorite Products</h6>
                    </div>
                    <div class="card-body">
                        @if($topProducts->isEmpty())
                            <p class="text-muted text-center py-2 mb-0">No product history available.</p>
                        @else
                            <div class="d-flex flex-column gap-2">
                                @foreach($topProducts as $item)
                                    <div class="d-flex align-items-center p-2 border rounded">
                                        @if($item->product && $item->product->image)
                                            <img src="{{ asset('storage/' . $item->product->image) }}" class="rounded object-fit-cover me-3 border" style="width: 40px; height: 40px;" alt="{{ $item->product->name ?? 'Product' }}">
                                        @else
                                            <div class="rounded bg-light text-muted d-flex justify-content-center align-items-center me-3 border" style="width: 40px; height: 40px;">
                                                <i class="bi bi-image fs-5"></i>
                                            </div>
                                        @endif
                                        
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 fw-bold text-truncate" style="max-width: 180px; font-size: 0.9rem;">
                                                @if($item->product)
                                                    <a href="{{ route('products.show', $item->product) }}" class="text-dark text-decoration-none">
                                                        {{ $item->product->name }}
                                                    </a>
                                                @else
                                                    Unknown Product
                                                @endif
                                            </h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">Purchased <strong>{{ $item->total_quantity }}</strong> times</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <h5 class="fw-bold mb-3">Order History</h5>
        @endif

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
        {{ $order->link }}
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