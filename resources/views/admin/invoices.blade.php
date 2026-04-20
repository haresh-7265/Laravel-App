@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div style="padding: 2rem">

    {{-- header --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem">
        <h1 style="font-size:20px; font-weight:500; margin:0">Invoices</h1>
    </div>

    {{-- stats --}}
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:1.5rem">
        <div style="background:#f9fafb; border-radius:8px; padding:14px 16px">
            <p style="font-size:12px; color:#6b7280; margin:0 0 4px">Total files</p>
            <p style="font-size:22px; font-weight:500; margin:0">{{ count($invoices) }}</p>
        </div>
        <div style="background:#f9fafb; border-radius:8px; padding:14px 16px">
            <p style="font-size:12px; color:#6b7280; margin:0 0 4px">Total size</p>
            <p style="font-size:22px; font-weight:500; margin:0">
                {{ number_format($invoices->sum('size') / 1024, 1) }} KB
            </p>
        </div>
        <div style="background:#f9fafb; border-radius:8px; padding:14px 16px">
            <p style="font-size:12px; color:#6b7280; margin:0 0 4px">Latest upload</p>
            <p style="font-size:15px; font-weight:500; margin:0">
                {{ $invoices->sortByDesc('lastModified')->first()
                    ? \Carbon\Carbon::createFromTimestamp($invoices->sortByDesc('lastModified')->first()['lastModified'])->format('d M Y')
                    : '—' }}
            </p>
        </div>
    </div>

    {{-- sort --}}
    <div style="display:flex; gap:8px; margin-bottom:1rem">
        <select id="sort-select"
            style="font-size:13px; padding:0 10px; height:36px; width: max-content; padding-right: 20px; border:1px solid #e5e7eb; border-radius:6px">
            <option value="date-desc">Newest first</option>
            <option value="date-asc">Oldest first</option>
            <option value="size-desc">Largest first</option>
            <option value="size-asc">Smallest first</option>
            <option value="name-asc">Name A–Z</option>
        </select>
    </div>

    {{-- table --}}
    <div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden">
        <table id="invoice-table" style="width:100%; border-collapse:collapse; table-layout:fixed">
            <thead style="background:#f9fafb">
                <tr>
                    <th style="width:32%; font-size:12px; font-weight:500; color:#6b7280; padding:10px 16px; text-align:left; border-bottom:1px solid #e5e7eb">Filename</th>
                    <th style="width:12%; font-size:12px; font-weight:500; color:#6b7280; padding:10px 16px; text-align:left; border-bottom:1px solid #e5e7eb">Size</th>
                    <th style="width:24%; font-size:12px; font-weight:500; color:#6b7280; padding:10px 16px; text-align:left; border-bottom:1px solid #e5e7eb">Last modified</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                <tr class="inv-row"
                    data-name="{{ strtolower($invoice['filename']) }}"
                    data-size="{{ $invoice['size'] }}"
                    data-ts="{{ $invoice['lastModified'] }}"
                    style="border-bottom:1px solid #e5e7eb">
                    <td style="padding:11px 16px; font-family:monospace; font-size:12px">
                        {{ $invoice['filename'] }}
                    </td>
                    <td style="padding:11px 16px; font-size:13px">
                        {{ number_format($invoice['size'] / 1024, 1) }} KB
                    </td>
                    <td style="padding:11px 16px; font-size:13px; color:#6b7280">
                        {{ \Carbon\Carbon::createFromTimestamp($invoice['lastModified'])->format('d M Y, H:i') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:3rem; color:#9ca3af; font-size:13px">
                        No invoices found on disk.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // search + sort
    const rows    = document.querySelectorAll('.inv-row');
    const sort    = document.getElementById('sort-select');

    function sortRows() {
        const tbody = document.querySelector('#invoice-table tbody');
        const arr   = [...rows].filter(r => r.style.display !== 'none');
        const val   = sort.value;

        arr.sort((a, b) => {
            if (val === 'date-desc') return b.dataset.ts - a.dataset.ts;
            if (val === 'date-asc')  return a.dataset.ts - b.dataset.ts;
            if (val === 'size-desc') return b.dataset.size - a.dataset.size;
            if (val === 'size-asc')  return a.dataset.size - b.dataset.size;
            if (val === 'name-asc')  return a.dataset.name.localeCompare(b.dataset.name);
        });

        arr.forEach(r => tbody.appendChild(r));
    }

    search.addEventListener('keyup', () => { filter(); sortRows(); });
    sort.addEventListener('change', sortRows);

</script>
@endpush