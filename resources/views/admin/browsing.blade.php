@extends('layouts.app')

@section('title', 'Store Browsing - Live')

@push('styles')
    <style>
        .browsing-list .customer-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-bottom: 1px solid #f1f1f1;
}

.browsing-list .customer-row:last-child {
    border-bottom: none;
}

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #0d6efd;
    color: #fff;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.status-dot {
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
}

.info {
    flex: 1;
    min-width: 0;
}

.info .name {
    font-weight: 600;
    display: block;
}

.info .page {
    font-size: 12px;
    color: #6c757d;
    word-break: break-all;
}

.duration {
    font-size: 12px;
    color: #6c757d;
    white-space: nowrap;
}
    </style>
@endpush


@section('content')
<div class="container py-4">

    {{-- Metric cards --}}
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Browsing now</p>
                        <h4 class="fw-bold mb-0" id="online-count">0</h4>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                        <i class="bi bi-people fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Joined today</p>
                        <h4 class="fw-bold mb-0" id="joined-today">0</h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                        <i class="bi bi-graph-up fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Live customer list --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Currently browsing</h5>
                <span class="badge bg-success" id="online-count-badge">Live</span>
            </div>

            <div id="browsing-list" class="browsing-list">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border text-primary mb-2"></div>
                    <p class="mb-0 small">Connecting...</p>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite('resources/js/admin/browsing.js')
@endpush