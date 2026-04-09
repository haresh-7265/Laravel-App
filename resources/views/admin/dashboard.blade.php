@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('style')
<style>
    .stat-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,.15);
    }
    .stat-card .card-body {
        padding: 1.5rem;
    }
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.7;
    }
    .dashboard-table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-top: none;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">
        <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
    </h2>
    <span class="text-muted">{{ now()->format('D, d M Y') }}</span>
</div>

{{-- ═══════ STAT CARDS ═══════ --}}
<div class="row g-3 mb-4">

    {{-- Total Orders --}}
    <div class="col-6 col-lg-4 col-xxl">
        <div class="card stat-card bg-primary bg-gradient text-white">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($stats['total_orders']) }}</div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Revenue --}}
    <div class="col-6 col-lg-4 col-xxl">
        <div class="card stat-card bg-success bg-gradient text-white">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25">
                    <i class="bi bi-currency-rupee"></i>
                </div>
                <div>
                    <div class="stat-value">₹{{ number_format($stats['total_revenue'], 2) }}</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
        </div>
    </div>

    {{-- New Customers Today --}}
    <div class="col-6 col-lg-4 col-xxl">
        <div class="card stat-card bg-info bg-gradient text-white">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25">
                    <i class="bi bi-person-plus"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($stats['new_customers']) }}</div>
                    <div class="stat-label">New Customers Today</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Orders --}}
    <div class="col-6 col-lg-6 col-xxl">
        <div class="card stat-card bg-warning bg-gradient text-dark">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($stats['pending_orders']) }}</div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Low Stock Products --}}
    <div class="col-12 col-lg-6 col-xxl">
        <div class="card stat-card bg-danger bg-gradient text-white">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-white bg-opacity-25">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="stat-value">{{ number_format($stats['low_stock_count']) }}</div>
                    <div class="stat-label">Low Stock Products</div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row g-4">

    {{-- ═══════ RECENT ORDERS ═══════ --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3">
                <h5 class="fw-semibold mb-0">
                    <i class="bi bi-clock-history me-1"></i>Recent Orders
                </h5>
            </div>
            <div class="card-body p-0">
                @if($recentOrders->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">No orders yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover dashboard-table mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none fw-medium">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td>{{ $order->user->name ?? 'N/A' }}</td>
                                        <td>₹{{ number_format($order->total, 2) }}</td>
                                        <td>
                                            @php
                                                $badgeMap = [
                                                    'pending'    => 'warning',
                                                    'processing' => 'primary',
                                                    'shipped'    => 'info',
                                                    'delivered'  => 'success',
                                                    'cancelled'  => 'danger',
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $badgeMap[$order->status] ?? 'secondary' }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="text-muted">{{ $order->created_at->format('d M, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════ LOW STOCK PRODUCTS ═══════ --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3">
                <h5 class="fw-semibold mb-0">
                    <i class="bi bi-exclamation-triangle me-1 text-danger"></i>Low Stock
                </h5>
            </div>
            <div class="card-body p-0">
                @if($lowStockProducts->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">All products are well stocked!</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover dashboard-table mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProducts as $product)
                                    <tr>
                                        <td class="fw-medium">{{ Str::limit($product->name, 25) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $product->stock === 0 ? 'danger' : 'warning' }}">
                                                {{ $product->stock }}
                                            </span>
                                        </td>
                                        <td>₹{{ number_format($product->price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
