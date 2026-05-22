@extends('layouts.app')

@section('title', 'Orders')

@section('content')

<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h4 class="flex items-center gap-2 text-xl font-semibold text-gray-900">
            <i class="bi bi-bag-check text-gray-700"></i>
            All Orders
        </h4>
        <span id="total-badge"
              class="inline-block px-3 py-1 text-sm font-medium bg-gray-100 text-gray-600 rounded-full border border-gray-200">
            {{ array_sum($allCounts) }} Total Orders
        </span>
    </div>

    {{-- Filters --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mb-5">
        <div class="flex flex-wrap gap-3 items-end">

            {{-- Search --}}
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" id="filter-search"
                           value="{{ request('search') }}"
                           placeholder="Order number or customer..."
                           class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-gray-400 bg-white">
                </div>
            </div>

            {{-- Order Status --}}
            <div class="min-w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Order Status</label>
                <select id="filter-status"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-gray-400 bg-white">
                    <option value="">All Status</option>
                    @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Payment Status --}}
            <div class="min-w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Payment</label>
                <select id="filter-payment"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-gray-400 bg-white">
                    <option value="">All</option>
                    <option value="paid"   {{ request('payment_status') == 'paid'   ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>

            {{-- Date --}}
            <div class="min-w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Date</label>
                <input type="date" id="filter-date"
                       value="{{ request('date') }}"
                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-gray-400 bg-white">
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button id="btn-filter"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium bg-gray-900 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <button id="btn-clear"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium border border-gray-200 text-gray-500 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
        @php
            $stats = [
                ['label' => 'Pending',    'status' => 'pending',    'color' => 'amber',  'icon' => 'bi-clock'],
                ['label' => 'Processing', 'status' => 'processing', 'color' => 'blue',   'icon' => 'bi-gear'],
                ['label' => 'Shipped',    'status' => 'shipped',    'color' => 'sky',    'icon' => 'bi-truck'],
                ['label' => 'Delivered',  'status' => 'delivered',  'color' => 'green',  'icon' => 'bi-check-circle'],
                ['label' => 'Cancelled',  'status' => 'cancelled',  'color' => 'red',    'icon' => 'bi-x-circle'],
            ];
            $colorMap = [
                'amber' => ['bg' => 'bg-amber-50',  'text' => 'text-amber-500',  'border' => 'border-amber-300'],
                'blue'  => ['bg' => 'bg-blue-50',   'text' => 'text-blue-500',   'border' => 'border-blue-300'],
                'sky'   => ['bg' => 'bg-sky-50',     'text' => 'text-sky-500',    'border' => 'border-sky-300'],
                'green' => ['bg' => 'bg-green-50',  'text' => 'text-green-500',  'border' => 'border-green-300'],
                'red'   => ['bg' => 'bg-red-50',    'text' => 'text-red-500',    'border' => 'border-red-300'],
            ];
        @endphp

        @foreach($stats as $stat)
        @php $c = $colorMap[$stat['color']]; @endphp
        <button data-stat-status="{{ $stat['status'] }}"
                class="stat-card group text-center bg-white border border-gray-200 rounded-xl p-3 hover:border-gray-300 transition-all cursor-pointer">
            <i class="bi {{ $stat['icon'] }} {{ $c['text'] }} text-xl block mb-1"></i>
            <p class="text-lg font-bold text-gray-900 leading-none">{{ $allCounts[$stat['status']] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $stat['label'] }}</p>
        </button>
        @endforeach
    </div>

    {{-- Table card --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

        {{-- Table --}}
        <div class="overflow-x-auto" id="table-wrap">
            <table class="w-full text-sm" id="orders-table">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide w-10">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Payment</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wide w-16">Action</th>
                    </tr>
                </thead>
                <tbody id="orders-tbody" class="divide-y divide-gray-100">
                    {{-- rows injected by JS --}}
                </tbody>
            </table>
        </div>

        {{-- Loading spinner --}}
        <div id="loading-row" class="hidden py-8 flex justify-center items-center gap-2 text-sm text-gray-400">
            <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            Loading orders...
        </div>

        {{-- Empty state --}}
        <div id="empty-state" class="hidden py-16 text-center">
            <i class="bi bi-bag-x text-5xl text-gray-300 block mb-4"></i>
            <h5 class="text-base font-medium text-gray-600 mb-1">No Orders Found</h5>
            <p class="text-sm text-gray-400" id="empty-message">No orders have been placed yet.</p>
            <button id="btn-clear-empty"
                    class="mt-4 text-sm text-blue-500 hover:underline hidden">
                Clear filters
            </button>
        </div>

        {{-- End of results --}}
        <div id="end-of-results" class="hidden py-4 text-center text-xs text-gray-400 border-t border-gray-100">
            All orders loaded
        </div>

        {{-- Scroll sentinel --}}
        <div id="scroll-sentinel" class="h-2"></div>

    </div>
</div>

{{-- Row template (hidden) --}}
<template id="row-template">
    <tr class="hover:bg-gray-50 transition-colors order-row">
        <td class="px-4 py-3 text-xs text-gray-400 tabular-nums row-index"></td>
        <td class="px-4 py-3">
            <span class="font-medium text-gray-800 row-order-number"></span>
        </td>
        <td class="px-4 py-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600 flex-shrink-0 row-avatar"></div>
                <div>
                    <p class="font-medium text-gray-800 text-xs leading-snug row-customer-name"></p>
                    <p class="text-xs text-gray-400 row-customer-email"></p>
                </div>
            </div>
        </td>
        <td class="px-4 py-3">
            <div class="flex items-center gap-1 row-items"></div>
        </td>
        <td class="px-4 py-3 font-semibold text-gray-800 row-total"></td>
        <td class="px-4 py-3">
            <span class="row-payment-badge"></span>
            <p class="text-xs text-gray-400 mt-0.5 row-payment-method"></p>
        </td>
        <td class="px-4 py-3">
            <span class="row-status-badge"></span>
        </td>
        <td class="px-4 py-3">
            <p class="text-xs text-gray-700 row-date"></p>
            <p class="text-xs text-gray-400 row-time"></p>
            <p class="text-xs text-gray-400 row-ago"></p>
        </td>
        <td class="px-4 py-3">
            <a href="#" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium border border-blue-200 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors row-view-link">
                <i class="bi bi-eye"></i> View
            </a>
        </td>
    </tr>
</template>

{{-- Scroll to top button --}}
<button id="scroll-top"
        onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-6 right-6 z-50 w-10 h-10 bg-gray-900 text-white rounded-full shadow-lg items-center justify-center hidden hover:bg-gray-700 transition-all">
    <i class="bi bi-arrow-up text-sm"></i>
</button>

{{-- Pass state to JS --}}
<script>
window._ordersState = {
    fetchUrl:   "{{ route('api.orders.index') }}",
    showUrl:    "{{ route('admin.orders.show', ['order' => '__ID__']) }}",
    csrfToken:  "{{ csrf_token() }}",
    cursor:     null,
    hasMore:    true,
    loading:    false,
    rowCount:   0,
    filters:    {
        search:         "{{ request('search') }}",
        status:         "{{ request('status') }}",
        payment_status: "{{ request('payment_status') }}",
        date:           "{{ request('date') }}",
    },
};
</script>

@push('scripts')
    @vite('resources/js/admin/orders-infinite.js')
@endpush

@endsection