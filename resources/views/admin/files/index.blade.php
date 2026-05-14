{{-- resources/views/admin/files/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0">File Manager</h1>
        <form method="POST" action="{{ route('admin.files.cleanup') }}"
            onsubmit="return confirm('Delete all files older than 30 days?')">
            @csrf
            <button class="btn btn-danger btn-sm">
                <i class="bi bi-trash me-1"></i> Bulk cleanup (30+ days)
            </button>
        </form>
    </div>

    {{-- stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="bg-light rounded p-3">
                <p class="text-muted small mb-1">Total files</p>
                <p class="fw-500 fs-5 mb-0">{{ count($files) }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-light rounded p-3">
                <p class="text-muted small mb-1">Total size</p>
                <p class="fw-500 fs-5 mb-0">
                    {{ number_format($files->sum('size') / 1024, 1) }} KB
                </p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-light rounded p-3">
                <p class="text-muted small mb-1">Older than 30 days</p>
                <p class="fw-500 fs-5 mb-0 text-danger">
                    {{ $files->where('age_days', '>=', 30)->count() }}
                </p>
            </div>
        </div>
    </div>

    {{-- table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Last modified</th>
                        <th>Age</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($files as $file)
                    <tr>
                        <td>
                            <code class="text-dark">
                                <a href="{{ $file['url'] }}" class="hover:text-blue-500 hover:underline">
                                    {{ $file['name'] }}
                                </a>
                            </code>
                        </td>
                        <td class="text-muted small">
                            {{ number_format($file['size'] / 1024, 1) }} KB
                        </td>
                        <td class="text-muted small">
                            {{ \Carbon\Carbon::createFromTimestamp($file['lastModified'])->isoFormat('D MMM YYYY, HH:mm') }}
                        </td>
                        <td>
                            <span class="badge {{ $file['age_days'] >=30 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                                {{ number_format($file['age_days']) }}d old
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.files.archive') }}"
                                onsubmit="return confirm('Archive {{ $file['name'] }}?')">
                                @csrf
                                <input type="hidden" name="path" value="{{ $file['path'] }}">
                                <button class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-archive me-1"></i> Archive
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            No files found in reports disk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection