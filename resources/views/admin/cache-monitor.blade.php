@extends('layouts.app')

@section('title', 'Cache Performance Monitor')

@section('style')
<style>
    /* ═══════ General Page ═══════ */
    .cache-monitor { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }

    /* ═══════ Stat Cards ═══════ */
    .cm-card {
        border: none;
        border-radius: 14px;
        transition: transform .2s ease, box-shadow .2s ease;
        overflow: hidden;
    }
    .cm-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(0,0,0,.15);
    }
    .cm-card .card-body { padding: 1.5rem; }

    .cm-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .cm-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .cm-label {
        font-size: .8rem;
        text-transform: uppercase;
        letter-spacing: .6px;
        opacity: .75;
    }

    /* ═══════ Hit-Rate Gauge ═══════ */
    .gauge-ring {
        width: 160px;
        height: 160px;
        position: relative;
        margin: 0 auto;
    }
    .gauge-ring svg { transform: rotate(-90deg); }
    .gauge-ring .track {
        fill: none;
        stroke: rgba(255,255,255,.15);
        stroke-width: 10;
    }
    .gauge-ring .value {
        fill: none;
        stroke: #fff;
        stroke-width: 10;
        stroke-linecap: round;
        transition: stroke-dashoffset .8s ease;
    }
    .gauge-pct {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }
    .gauge-pct small {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: .7;
        margin-top: 4px;
    }

    /* ═══════ Table Styles ═══════ */
    .cm-table th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #6c757d;
        border-top: none;
    }
    .cm-table td { vertical-align: middle; }
    .cm-table .badge-hit  { background: #198754; }
    .cm-table .badge-miss { background: #dc3545; }

    /* ═══════ Progress-bar inside table ═══════ */
    .cache-bar {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
        min-width: 100px;
    }
    .cache-bar-fill {
        height: 100%;
        border-radius: 4px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        transition: width .5s ease;
    }

    /* ═══════ Clear-cache Button ═══════ */
    .btn-clear-cache {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: none;
        color: #fff;
        padding: .6rem 1.4rem;
        border-radius: 10px;
        font-weight: 600;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .btn-clear-cache:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(239,68,68,.4);
        color: #fff;
    }
    .btn-clear-cache:active { transform: scale(.97); }

    /* ═══════ Scrollable event log ═══════ */
    .event-log-scroll {
        max-height: 400px;
        overflow-y: auto;
    }

    /* ═══════ Subtle pulse on live badge ═══════ */
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
    .live-dot {
        display: inline-block;
        width: 8px; height: 8px;
        background: #22c55e;
        border-radius: 50%;
        animation: pulse 1.5s infinite;
        margin-right: 6px;
    }
</style>
@endsection

@section('content')
<div class="cache-monitor">

    {{-- ═══════ HEADER ═══════ --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-bold mb-0">
                <i class="bi bi-speedometer me-2"></i>Cache Performance Monitor
            </h2>
            <small class="text-muted">
                <span class="live-dot"></span>Data calculated from today's cache logs &middot;
                {{ now()->format('D, d M Y H:i') }}
            </small>
        </div>

        <form action="{{ route('admin.cache-clear') }}" method="POST"
              onsubmit="return confirm('Are you sure you want to flush all cache? This cannot be undone.')">
            @csrf
            <button type="submit" class="btn btn-clear-cache">
                <i class="bi bi-trash3 me-1"></i>Clear All Cache
            </button>
        </form>
    </div>

    {{-- ═══════ STAT CARDS ═══════ --}}
    <div class="row g-3 mb-4">

        {{-- Hit Rate Gauge --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card cm-card text-white h-100"
                 style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                <div class="card-body text-center">
                    <div class="gauge-ring">
                        @php
                            $circ   = 2 * M_PI * 65;           // circumference
                            $offset = $circ - ($circ * $hitRate / 100);
                        @endphp
                        <svg width="160" height="160" viewBox="0 0 160 160">
                            <circle class="track" cx="80" cy="80" r="65" />
                            <circle class="value"  cx="80" cy="80" r="65"
                                    stroke-dasharray="{{ $circ }}"
                                    stroke-dashoffset="{{ $offset }}" />
                        </svg>
                        <div class="gauge-pct">
                            {{ $hitRate }}%
                            <small>Hit Rate</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Hits --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card cm-card bg-success bg-gradient text-white h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="cm-icon bg-white bg-opacity-25">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="cm-value">{{ number_format($totalHits) }}</div>
                        <div class="cm-label">Cache Hits</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Misses --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card cm-card bg-danger bg-gradient text-white h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="cm-icon bg-white bg-opacity-25">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <div class="cm-value">{{ number_format($totalMisses) }}</div>
                        <div class="cm-label">Cache Misses</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cache Size --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card cm-card text-white h-100"
                 style="background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="cm-icon bg-white bg-opacity-25">
                        <i class="bi bi-hdd-stack"></i>
                    </div>
                    <div>
                        <div class="cm-value">{{ $cacheSize['display'] }}</div>
                        <div class="cm-label">
                            {{ ucfirst($cacheStore) }} &middot; {{ number_format($cacheSize['keys']) }} keys
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══════ DETAILS ROW ═══════ --}}
    <div class="row g-4">

        {{-- ─── Most Cached Items ─── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pt-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-semibold mb-0">
                        <i class="bi bi-bar-chart-line me-1 text-primary"></i>Most Cached Items
                    </h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary">Top 10</span>
                </div>
                <div class="card-body p-0">
                    @if(count($mostCached) === 0)
                        <p class="text-muted text-center py-5 mb-0">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                            No cache hits logged yet today.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover cm-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Cache Key</th>
                                        <th>Hits</th>
                                        <th style="min-width:120px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $maxHits = max($mostCached) ?: 1; @endphp
                                    @foreach($mostCached as $key => $count)
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <code class="text-break" style="font-size:.82rem;">{{ $key }}</code>
                                            </td>
                                            <td class="fw-semibold">{{ number_format($count) }}</td>
                                            <td>
                                                <div class="cache-bar">
                                                    <div class="cache-bar-fill"
                                                         style="width: {{ ($count / $maxHits) * 100 }}%;"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ─── Recent Cache Events ─── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pt-3 d-flex align-items-center justify-content-between">
                    <h5 class="fw-semibold mb-0">
                        <i class="bi bi-clock-history me-1 text-info"></i>Recent Cache Events
                    </h5>
                    <span class="badge bg-info bg-opacity-10 text-info">Last 20</span>
                </div>
                <div class="card-body p-0">
                    @if(count($recentEvents) === 0)
                        <p class="text-muted text-center py-5 mb-0">
                            <i class="bi bi-journal-text fs-1 d-block mb-2 opacity-50"></i>
                            No events yet today. Interact with the app to generate cache events.
                        </p>
                    @else
                        <div class="event-log-scroll">
                            <table class="table table-hover cm-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Type</th>
                                        <th>Key</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentEvents as $event)
                                        <tr>
                                            <td class="ps-3">
                                                @if($event['type'] === 'HIT')
                                                    <span class="badge badge-hit">
                                                        <i class="bi bi-check-lg"></i> HIT
                                                    </span>
                                                @else
                                                    <span class="badge badge-miss">
                                                        <i class="bi bi-x-lg"></i> MISS
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <code class="text-break" style="font-size:.82rem;">{{ $event['key'] }}</code>
                                            </td>
                                            <td class="text-muted" style="font-size:.82rem;">
                                                {{ $event['timestamp'] }}
                                            </td>
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

    {{-- ═══════ REDIS EXTRA INFO (if applicable) ═══════ --}}
    @if($cacheStore === 'redis' && $cacheSize['memoryUsed'] !== 'N/A')
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h5 class="fw-semibold mb-0">
                        <i class="bi bi-database me-1 text-danger"></i>Redis Server Info
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3">
                                <div class="text-muted small text-uppercase mb-1">Memory Used</div>
                                <div class="fs-4 fw-bold text-primary">{{ $cacheSize['memoryUsed'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <div class="text-muted small text-uppercase mb-1">Peak Memory</div>
                                <div class="fs-4 fw-bold text-warning">{{ $cacheSize['memoryPeak'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <div class="text-muted small text-uppercase mb-1">Total Keys</div>
                                <div class="fs-4 fw-bold text-success">{{ number_format($cacheSize['keys']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
