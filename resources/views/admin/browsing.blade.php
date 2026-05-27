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
                        <h4 class="fw-bold mb-0" id="online-count" style="color: greenyellow;">0</h4>
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
    <div class="card shadow-sm border-0 mb-4">
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

    {{-- All Customers list for impersonation and magic links --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">All Registered Customers</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar bg-secondary text-white" style="width: 32px; height: 32px; font-size: 12px;">
                                            {{ Str::initials($customer->name) }}
                                        </div>
                                        <span class="fw-semibold">{{ $customer->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->created_at->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        {{-- Impersonate --}}
                                        <a href="{{ route('admin.impersonate', $customer->id) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-person-fill-exclamation me-1"></i> Impersonate
                                        </a>
                                        {{-- Magic link --}}
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generateMagicLink({{ $customer->id }}, '{{ addslashes($customer->name) }}')">
                                            <i class="bi bi-link-45deg me-1"></i> Magic Link
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No customers registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Magic Link Modal --}}
<div class="modal fade" id="magicLinkModal" tabindex="-1" aria-labelledby="magicLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="magicLinkModalLabel">Signed Magic Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-dark">
                <p>Copy the signed URL below for stateless authentication as <strong id="magicLinkUser"></strong> (valid for 15 minutes):</p>
                <div class="input-group mb-3">
                    <input type="text" id="magicLinkInput" class="form-control text-dark" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyMagicLink()">Copy</button>
                </div>
                <div class="alert alert-info py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i> Opening this link uses stateless <code>Auth::onceUsingId()</code> to log the request, without initiating a web session.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function generateMagicLink(userId, userName) {
        fetch(`/generate-magic-link/${userId}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('magicLinkUser').innerText = userName;
                document.getElementById('magicLinkInput').value = data.magic_link;
                const modal = new bootstrap.Modal(document.getElementById('magicLinkModal'));
                modal.show();
            })
            .catch(err => {
                alert('Error generating magic link');
                console.error(err);
            });
    }

    function copyMagicLink() {
        const input = document.getElementById('magicLinkInput');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(() => {
            alert('Magic link copied to clipboard!');
        });
    }
</script>
@endsection

@push('scripts')
    @vite('resources/js/admin/browsing.js')
@endpush