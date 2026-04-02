@extends('layouts.app')

@section('title', 'Orders')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-bag-check me-1"></i> All Orders
    </h4>
    <span class="badge bg-secondary fs-6">{{ $orders->count() }} Total Orders</span>
</div>

{{-- Filters --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.index') }}">
            <div class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label class="form-label small fw-medium">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Order number or customer name..."
                            class="form-control" />
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-medium">Order Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-medium">Payment Status</label>
                    <select name="payment_status" class="form-select">
                        <option value="">All</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-medium">Date</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-control" />
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Stats Row --}}
<div class="row g-3 mb-4">
    @php
        $stats = [
            ['label' => 'Pending',    'status' => 'pending',    'color' => 'warning', 'icon' => 'bi-clock'],
            ['label' => 'Processing', 'status' => 'processing', 'color' => 'primary', 'icon' => 'bi-gear'],
            ['label' => 'Shipped',    'status' => 'shipped',    'color' => 'info',    'icon' => 'bi-truck'],
            ['label' => 'Delivered',  'status' => 'delivered',  'color' => 'success', 'icon' => 'bi-check-circle'],
            ['label' => 'Cancelled',  'status' => 'cancelled',  'color' => 'danger',  'icon' => 'bi-x-circle'],
        ];
    @endphp
    @foreach($stats as $stat)
    <div class="col-6 col-md">
        <a href="{{ route('admin.orders.index', ['status' => $stat['status']]) }}"
            class="card shadow-sm text-decoration-none h-100 {{ request('status') == $stat['status'] ? 'border-' . $stat['color'] . ' border-2' : '' }}">
            <div class="card-body text-center py-3">
                <i class="bi {{ $stat['icon'] }} text-{{ $stat['color'] }} fs-4"></i>
                <h5 class="fw-bold mb-0 mt-1">{{ $allCounts[$stat['status']] ?? 0 }}</h5>
                <small class="text-muted">{{ $stat['label'] }}</small>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Orders Table --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        @if($orders->isEmpty())

        {{-- Empty State --}}
        <div class="text-center py-5">
            <i class="bi bi-bag-x display-1 text-muted"></i>
            <h5 class="mt-3 fw-bold">No Orders Found</h5>
            <p class="text-muted mb-4">
                @if(request()->hasAny(['search', 'status', 'payment_status', 'date']))
                    No orders match your current filters.
                    <a href="{{ route('admin.orders.index') }}">Clear filters</a>
                @else
                    No orders have been placed yet.
                @endif
            </p>
        </div>

        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    @php
                        $statusMap = [
                            'pending'    => 'warning',
                            'processing' => 'primary',
                            'shipped'    => 'info',
                            'delivered'  => 'success',
                            'cancelled'  => 'danger',
                        ];
                        $badge = $statusMap[$order->status] ?? 'secondary';
                    @endphp
                    <tr>
                        <td class="ps-4 text-muted small">{{ $loop->iteration }}</td>

                        <td>
                            <span class="fw-semibold">{{ $order->order_number }}</span>
                        </td>

                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                    style="width:32px;height:32px;font-size:13px;">
                                    {{ strtoupper(substr($order->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="mb-0 small fw-medium">{{ $order->user->name }}</p>
                                    <small class="text-muted">{{ $order->user->email }}</small>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div class="d-flex align-items-center gap-1">
                                @foreach($order->items->take(2) as $item)
                                    @if($item->ima)
                                    <img src="{{ asset('storage/' . $item->product->image) }}"
                                        class="rounded border object-fit-cover"
                                        style="width:32px;height:32px;"
                                        title="{{ $item->product_name }}" />
                                    @else
                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center"
                                    style="width:50px;height:50px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                    @endif
                                @endforeach
                                @if($order->items->count() > 2)
                                <span class="text-muted small">+{{ $order->items->count() - 2 }}</span>
                                @endif
                            </div>
                        </td>

                        <td class="fw-semibold">@currency($order->total)</td>

                        <td>
                            <span class="badge {{ $order->payment_status == 'paid' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                            <small class="text-muted d-block">{{ strtoupper($order->payment_method) }}</small>
                        </td>

                        <td>
                            <span class="badge bg-{{ $badge }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>

                        <td class="small text-muted">
                            {{ $order->created_at->format('d M Y') }}
                            <small class="d-block">{{ $order->created_at->format('h:i A') }}</small>
                        </td>

                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}"
                                class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection