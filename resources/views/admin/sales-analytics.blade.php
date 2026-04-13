@extends('layouts.app')
@section('title', 'Sales Analytics')

@section('content')
<div class="container-xl">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h5 fw-semibold text-dark mb-0">Sales Analytics</h1>
        <form method="GET" action="{{ route('admin.sales-analytics') }}">
            <select name="year" onchange="this.form.submit()"
                class="form-select form-select-sm" style="width: auto;">
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected($selectedYear == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Metric Cards --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['Total revenue',    config('admin.currency') . number_format($totalRevenue)],
            ['Total orders',     number_format($totalOrders)],
            ['Avg order value',  config('admin.currency') . number_format($avgOrderValue)],
            ['Unique customers', number_format($uniqueCustomers)],
        ] as [$label, $value])
        <div class="col-6 col-md-3">
            <div class="bg-light rounded-3 p-3">
                <p class="text-muted small mb-1">{{ $label }}</p>
                <p class="fs-4 fw-medium text-dark mb-0">{{ $value }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Monthly Sales Chart --}}
    <div class="card border-light rounded-3 p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h6 fw-medium text-secondary mb-0">Monthly sales — {{ $selectedYear }}</h2>
            <a href="{{ route('admin.sales-analytics.export', ['type' => 'monthly', 'year' => $selectedYear]) }}"
               class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 12px;">
               Export CSV ↓
            </a>
        </div>
        <div style="position: relative; height: 224px;">
            <canvas id="monthlyChart" role="img"
                aria-label="Monthly revenue chart for {{ $selectedYear }}"></canvas>
        </div>
    </div>

    {{-- Top Products & Top Customers --}}
    <div class="row g-4 mb-4">

        {{-- Top Products --}}
        <div class="col-12 col-md-6">
            <div class="card border-light rounded-3 p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h6 fw-medium text-secondary mb-0">Top 10 products</h2>
                    <a href="{{ route('admin.sales-analytics.export', ['type' => 'products']) }}"
                       class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 12px;">
                       Export CSV ↓
                    </a>
                </div>
                <table class="table table-sm table-borderless mb-0" style="font-size: 12px;">
                    <thead class="text-muted border-bottom">
                        <tr>
                            <th class="ps-0" style="width: 24px;">#</th>
                            <th class="ps-0">Product</th>
                            <th class="text-end pe-0">Qty</th>
                            <th class="text-end pe-0">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                    @php $maxQty = $topProducts->first()?->total_sold ?? 1; @endphp
                    @foreach($topProducts as $i => $p)
                        <tr class="border-bottom border-light">
                            <td class="ps-0 text-muted">{{ $i + 1 }}</td>
                            <td class="ps-0">
                                <div class="fw-medium text-dark text-truncate" style="max-width: 160px;">
                                    {{ $p->product->name ?? 'N/A' }}
                                </div>
                                <div class="progress mt-1" style="height: 4px; width: 96px;">
                                    <div class="progress-bar bg-primary"
                                         style="width: {{ round($p->total_sold / $maxQty * 100) }}%">
                                    </div>
                                </div>
                            </td>
                            <td class="text-end pe-0 text-dark">{{ number_format($p->total_sold) }}</td>
                            <td class="text-end pe-0 text-dark">@currency($p->revenue)</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Customers --}}
        <div class="col-12 col-md-6">
            <div class="card border-light rounded-3 p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h6 fw-medium text-secondary mb-0">Top 10 customers</h2>
                    <a href="{{ route('admin.sales-analytics.export', ['type' => 'customers']) }}"
                       class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 12px;">
                       Export CSV ↓
                    </a>
                </div>
                <table class="table table-sm table-borderless mb-0" style="font-size: 12px;">
                    <thead class="text-muted border-bottom">
                        <tr>
                            <th class="ps-0" style="width: 24px;">#</th>
                            <th class="ps-0">Customer</th>
                            <th class="text-end pe-0">Orders</th>
                            <th class="text-end pe-0">Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                    @php $maxSpent = $topCustomers->first()?->total_spent ?? 1; @endphp
                    @foreach($topCustomers as $i => $c)
                        <tr class="border-bottom border-light">
                            <td class="ps-0 text-muted">{{ $i + 1 }}</td>
                            <td class="ps-0">
                                <div class="fw-medium text-dark">{{ $c->customer->name ?? 'N/A' }}</div>
                                <div class="text-muted" style="font-size: 11px;">
                                    {{ $c->customer->email ?? '' }}
                                </div>
                                <div class="progress mt-1" style="height: 4px; width: 96px;">
                                    <div class="progress-bar bg-success"
                                         style="width: {{ round($c->total_spent / $maxSpent * 100) }}%">
                                    </div>
                                </div>
                            </td>
                            <td class="text-end pe-0 text-dark">{{ $c->order_count }}</td>
                            <td class="text-end pe-0 text-dark">@currency($c->total_spent, 0)</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Sales by Category --}}
    <div class="card border-light rounded-3 p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h6 fw-medium text-secondary mb-0">Sales by category</h2>
            <a href="{{ route('admin.sales-analytics.export', ['type' => 'category']) }}"
               class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 12px;">
               Export CSV ↓
            </a>
        </div>
        <div class="row g-4 align-items-start">
            <div class="col-12 col-md-5">
                <div style="position: relative; height: 200px;">
                    <canvas id="categoryChart" role="img"
                        aria-label="Sales by category donut chart"></canvas>
                </div>
            </div>
            <div class="col-12 col-md-7">
                <table class="table table-sm table-borderless mb-0" style="font-size: 12px;">
                    <thead class="text-muted border-bottom">
                        <tr>
                            <th class="ps-0">Category</th>
                            <th class="text-end pe-0">Revenue</th>
                            <th class="text-end pe-0">Orders</th>
                            <th class="text-end pe-0">Share</th>
                        </tr>
                    </thead>
                    @php $catTotal = $byCategory->sum('total_revenue'); @endphp
                    <tbody>
                    @foreach($byCategory as $cat)
                        <tr class="border-bottom border-light">
                            <td class="ps-0 fw-medium text-dark">
                                {{ $cat->category->name ?? 'Uncategorized' }}
                            </td>
                            <td class="text-end pe-0 text-dark">@currency($cat->total_revenue)</td>
                            <td class="text-end pe-0 text-dark">{{ number_format($cat->total_orders) }}</td>
                            <td class="text-end pe-0 text-dark">
                                {{ $catTotal > 0 ? round($cat->total_revenue / $catTotal * 100) : 0 }}%
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Chart.js Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const monthlyLabels  = @json($monthlySales->keys());
const monthlyRevenue = @json($monthlySales->pluck('revenue'));
const monthlyAvg     = @json($monthlySales->pluck('avg'));
const currency = @json(config('admin.currency'))

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthlyLabels,
        datasets: [
            {
                label: 'Revenue',
                data: monthlyRevenue,
                backgroundColor: 'rgba(59,130,246,0.75)',
                borderRadius: 4,
                yAxisID: 'y'
            },
            {
                label: 'Avg order value',
                data: monthlyAvg,
                type: 'line',
                borderColor: '#10b981',
                backgroundColor: 'transparent',
                borderWidth: 2,
                pointRadius: 3,
                borderDash: [4, 3],
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 }, autoSkip: false } },
            y: { ticks: { callback: v => currency + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) }, grid: { color: 'rgba(0,0,0,0.05)' } },
            y1: { position: 'right', ticks: { callback: v => currency +v, color: '#10b981', font: { size: 11 } }, grid: { display: false } }
        }
    }
});

const catLabels = @json($byCategory->map(fn($c) => $c->category->name ?? 'Other'));
const catData   = @json($byCategory->pluck('total_revenue'));
const catColors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#6b7280'];

new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: catLabels,
        datasets: [{ data: catData, backgroundColor: catColors, borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 10 } } }
    }
});
</script>
@endsection