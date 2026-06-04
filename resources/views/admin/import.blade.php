@extends('layouts.app')

@section('title', 'Import Products')

@section('content')

<div class="max-w-5xl mx-auto px-6 py-10">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 flex items-center gap-3">
                <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                </span>
                Import Products
            </h1>
            <p class="text-sm mt-1 text-gray-500">Upload a CSV file to bulk-import products into the catalogue.</p>
        </div>
        <a href="#rules" class="text-sm flex items-center gap-1 px-3 py-1.5 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
            <i class="bi bi-info-circle"></i> Field Rules
        </a>
    </div>

    {{-- Upload card --}}
    <div class="rounded-2xl p-6 mb-6 bg-white border border-gray-200 shadow-sm">
        <h2 class="font-semibold mb-4 flex items-center gap-2 text-gray-800">
            <i class="bi bi-cloud-upload text-blue-500"></i>
            Upload CSV File
        </h2>

        <form action="{{ route('products.import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
            @csrf

            <div id="dropZone"
                 class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center mb-4 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all">
                <i class="bi bi-file-earmark-spreadsheet text-4xl mb-3 block text-blue-500"></i>
                <p class="font-medium text-sm mb-1 text-gray-700">Drag & drop your CSV here or <span class="text-blue-600">browse</span></p>
                <p class="text-xs font-mono text-gray-400">Max 10 MB · .csv only · UTF-8 encoded</p>
                <input type="file" name="csv_file" id="csvInput" accept=".csv" class="hidden">
            </div>

            <div id="filePreview" class="hidden items-center gap-3 px-4 py-3 rounded-xl mb-4 bg-gray-50 border border-gray-200">
                <i class="bi bi-file-earmark-check text-xl text-green-500"></i>
                <div class="flex-1 min-w-0">
                    <p id="fileName" class="text-sm font-medium text-gray-800 truncate"></p>
                    <p id="fileSize" class="text-xs font-mono text-gray-400"></p>
                </div>
                <button type="button" id="clearFile" class="text-xs px-2 py-1 rounded text-red-500 bg-red-50 hover:bg-red-100 transition-colors">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            @if($errors->any())
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-600 flex items-start gap-2">
                    <i class="bi bi-exclamation-circle-fill mt-0.5 flex-shrink-0"></i>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p id="jsError" class="hidden mb-3 text-xs text-center text-red-500"></p>

            <button type="submit" id="submitBtn" disabled
                    class="w-full py-3 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 transition-all bg-gray-100 text-gray-400 cursor-not-allowed">
                <i class="bi bi-file-earmark-arrow-up"></i>
                Import Products
            </button>
        </form>
    </div>

    {{-- Active imports container — cards injected by JS --}}
    <div id="batchSection" class="{{ empty($batchIds) ? 'hidden' : '' }}">
        <h2 class="font-semibold mb-3 flex items-center gap-2 text-sm text-gray-500 uppercase tracking-wide">
            <i class="bi bi-layers"></i> Active Imports
        </h2>
        <div class="space-y-4" id="batchList">
            {{-- JS renders a card per batch ID --}}
            @foreach($batchIds ?? [] as $batchId)
                <div id="batch-{{ $batchId }}"
                     data-batch-id="{{ $batchId }}"
                     class="rounded-2xl p-5 bg-white border border-gray-200 shadow-sm">

                    {{-- Loading skeleton --}}
                    <div class="batch-skeleton flex items-center gap-3 animate-pulse">
                        <div class="w-8 h-8 rounded-lg bg-gray-100"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 bg-gray-100 rounded w-1/3"></div>
                            <div class="h-2 bg-gray-100 rounded w-1/2"></div>
                        </div>
                    </div>

                    {{-- Real content — hidden until first poll --}}
                    <div class="batch-content hidden">

                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm batch-status-bg">
                                    <i class="bi batch-icon"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 batch-name">Product Import</p>
                                    <p class="text-xs font-mono mt-0.5 text-gray-400">{{ $batchId }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold font-mono batch-badge"></span>
                                <button type="button"
                                        class="batch-cancel-btn text-xs px-2.5 py-1 rounded-lg flex items-center gap-1 text-red-500 bg-red-50 border border-red-200 hover:bg-red-100 transition-all"
                                        data-batch-id="{{ $batchId }}">
                                    <i class="bi bi-stop-circle"></i> Cancel
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between text-xs mb-1.5 text-gray-400">
                                <span>Progress</span>
                                <span class="font-mono font-medium text-gray-700 batch-progress-text">0%</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 batch-progress-bar bg-blue-500" style="width:0%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-3">
                            <div class="rounded-xl p-3 text-center bg-gray-50 border border-gray-100">
                                <p class="text-lg font-bold font-mono text-gray-800 batch-total">—</p>
                                <p class="text-xs mt-0.5 flex items-center justify-center gap-1 text-gray-400"><i class="bi bi-stack"></i> Total</p>
                            </div>
                            <div class="rounded-xl p-3 text-center bg-green-50 border border-green-100">
                                <p class="text-lg font-bold font-mono text-green-600 batch-processed">—</p>
                                <p class="text-xs mt-0.5 flex items-center justify-center gap-1 text-gray-400"><i class="bi bi-check-circle"></i> Processed</p>
                            </div>
                            <div class="rounded-xl p-3 text-center bg-amber-50 border border-amber-100">
                                <p class="text-lg font-bold font-mono text-amber-500 batch-pending">—</p>
                                <p class="text-xs mt-0.5 flex items-center justify-center gap-1 text-gray-400"><i class="bi bi-hourglass-split"></i> Pending</p>
                            </div>
                            <div class="rounded-xl p-3 text-center bg-red-50 border border-red-100">
                                <p class="text-lg font-bold font-mono text-red-500 batch-failed">—</p>
                                <p class="text-xs mt-0.5 flex items-center justify-center gap-1 text-gray-400"><i class="bi bi-x-circle"></i> Failed</p>
                            </div>
                        </div>

                        <div class="batch-done hidden mt-3 text-xs items-center gap-2 px-3 py-2 rounded-lg bg-green-50 text-green-700 border border-green-200">
                            <i class="bi bi-check-circle-fill"></i> Import completed successfully.
                        </div>
                        <div class="batch-cancelled-msg hidden mt-3 text-xs items-center gap-2 px-3 py-2 rounded-lg bg-red-50 text-red-600 border border-red-200">
                            <i class="bi bi-slash-circle"></i> Import was cancelled. Pending jobs skipped.
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Field rules --}}
    <div id="rules" class="mt-8 rounded-2xl p-6 bg-white border border-gray-200 shadow-sm">
        <h2 class="font-semibold mb-5 flex items-center gap-2 text-gray-800">
            <i class="bi bi-clipboard-data text-blue-500"></i>
            CSV Field Rules & Validation
        </h2>

        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="rounded-xl p-4 bg-gray-50 border border-gray-100">
                <p class="text-xs mb-1 text-gray-400"><i class="bi bi-file-earmark me-1"></i>File Type</p>
                <p class="font-semibold text-sm font-mono text-gray-800">.csv only</p>
            </div>
            <div class="rounded-xl p-4 bg-gray-50 border border-gray-100">
                <p class="text-xs mb-1 text-gray-400"><i class="bi bi-hdd me-1"></i>Max Size</p>
                <p class="font-semibold text-sm font-mono text-gray-800">10 MB</p>
            </div>
            <div class="rounded-xl p-4 bg-gray-50 border border-gray-100">
                <p class="text-xs mb-1 text-gray-400"><i class="bi bi-type me-1"></i>Encoding</p>
                <p class="font-semibold text-sm font-mono text-gray-800">UTF-8</p>
            </div>
        </div>

        <div class="rounded-xl overflow-hidden border border-gray-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400"><i class="bi bi-columns me-1"></i>COLUMN</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">TYPE</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">REQUIRED</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">RULES & NOTES</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">EXAMPLE</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $fields = [
                        ['name',           'string',  true,  'Max 255 chars. Auto-generates slug.',                            'Blue Denim Jacket'],
                        ['price',          'decimal', true,  'Format: 99.99. Max 8 digits, 2 decimal. Must be ≥ 0.',            '1299.99'],
                        ['discount_price', 'decimal', false, 'Must be < price. Max 10 digits, 2 decimal. Leave blank if none.', '999.99'],
                        ['stock',          'integer', true,  'Whole number only. Min 0.',                                       '50'],
                        ['category',       'string',  true,  'Category name (not ID). Created automatically if not found.',     'Clothing'],
                        ['description',    'text',    false, 'Plain text or HTML. Leave blank if none.',                        'Premium quality...'],
                        ['is_active',      'boolean', false, '1 or 0 (default: 1). Controls visibility.',                      '1'],
                        ['tags',           'json',    false, 'Comma-separated list. Stored as JSON array.',                     'summer,sale,new'],
                    ];
                    @endphp
                    @foreach($fields as $i => [$col, $type, $req, $rule, $example])
                    <tr class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} border-t border-gray-100">
                        <td class="px-4 py-3"><span class="font-mono font-medium text-sm text-blue-600">{{ $col }}</span></td>
                        <td class="px-4 py-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold font-mono bg-blue-50 text-blue-600">{{ $type }}</span></td>
                        <td class="px-4 py-3">
                            @if($req)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-500">
                                    <i class="bi bi-asterisk" style="font-size:8px"></i> required
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-400">optional</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $rule }}</td>
                        <td class="px-4 py-3"><code class="font-mono text-xs px-2 py-0.5 rounded bg-gray-100 text-green-600">{{ $example }}</code></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            <p class="text-xs font-semibold mb-2 flex items-center gap-2 text-gray-400">
                <i class="bi bi-file-code"></i> SAMPLE CSV HEADER
            </p>
            <div class="rounded-xl p-4 overflow-x-auto bg-gray-50 border border-gray-200">
                <code class="font-mono text-xs text-green-600">name,price,discount_price,stock,category,description,is_active,tags</code>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl p-4 flex gap-3 bg-amber-50 border border-amber-200">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500 flex-shrink-0"></i>
                <div>
                    <p class="text-xs font-semibold mb-1 text-amber-700">Category Auto-Create</p>
                    <p class="text-xs text-amber-600">If category name doesn't exist in the DB, it will be created automatically. Double-check spelling.</p>
                </div>
            </div>
            <div class="rounded-xl p-4 flex gap-3 bg-blue-50 border border-blue-200">
                <i class="bi bi-arrow-repeat mt-0.5 text-blue-500 flex-shrink-0"></i>
                <div>
                    <p class="text-xs font-semibold mb-1 text-blue-700">Duplicate Handling</p>
                    <p class="text-xs text-blue-600">Existing products matched by <code class="font-mono">name</code> will be updated, not duplicated.</p>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
const statusBase = "{{ url('products/import/status') }}";
const cancelBase = "{{ url('products/import/cancel') }}";
const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content;

// ── Upload form ────────────────────────────────────────────────────────
const csvInput    = document.getElementById('csvInput');
const dropZone    = document.getElementById('dropZone');
const filePreview = document.getElementById('filePreview');
const fileNameEl  = document.getElementById('fileName');
const fileSizeEl  = document.getElementById('fileSize');
const clearFile   = document.getElementById('clearFile');
const submitBtn   = document.getElementById('submitBtn');
const jsError     = document.getElementById('jsError');

function setFile(f) {
    if (!f) return;
    if (!f.name.endsWith('.csv'))       { showError('Only .csv files are accepted.'); return; }
    if (f.size > 10 * 1024 * 1024)     { showError('File exceeds 10 MB limit.'); return; }
    const mb = f.size / (1024 * 1024);
    fileNameEl.textContent = f.name;
    fileSizeEl.textContent = mb < 1 ? (f.size/1024).toFixed(1)+' KB' : mb.toFixed(2)+' MB';
    filePreview.classList.remove('hidden'); filePreview.classList.add('flex');
    submitBtn.disabled = false;
    submitBtn.className = 'w-full py-3 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 transition-all bg-blue-600 hover:bg-blue-700 text-white cursor-pointer';
    jsError.classList.add('hidden');
}

function showError(msg) { jsError.textContent = msg; jsError.classList.remove('hidden'); }

dropZone.addEventListener('click',     () => csvInput.click());
dropZone.addEventListener('dragover',  e  => { e.preventDefault(); dropZone.classList.add('border-blue-400','bg-blue-50'); });
dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('border-blue-400','bg-blue-50'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-400','bg-blue-50');
    const f = e.dataTransfer.files[0];
    const dt = new DataTransfer(); dt.items.add(f); csvInput.files = dt.files;
    setFile(f);
});
csvInput.addEventListener('change', e => setFile(e.target.files[0]));
clearFile.addEventListener('click', () => {
    csvInput.value = '';
    filePreview.classList.add('hidden'); filePreview.classList.remove('flex');
    submitBtn.disabled = true;
    submitBtn.className = 'w-full py-3 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 transition-all bg-gray-100 text-gray-400 cursor-not-allowed';
});

// ── Batch polling ──────────────────────────────────────────────────────
function updateCard(card, data) {
    card.querySelector('.batch-skeleton').classList.add('hidden');
    card.querySelector('.batch-content').classList.remove('hidden');

    card.querySelector('.batch-name').textContent          = data.name || 'Product Import';
    card.querySelector('.batch-progress-bar').style.width  = data.progress + '%';
    card.querySelector('.batch-progress-text').textContent = data.progress + '%';
    card.querySelector('.batch-total').textContent         = data.total;
    card.querySelector('.batch-processed').textContent     = data.processed;
    card.querySelector('.batch-pending').textContent       = data.pending;
    card.querySelector('.batch-failed').textContent        = data.failed;

    // Progress bar color
    const bar = card.querySelector('.batch-progress-bar');
    bar.className = 'h-full rounded-full transition-all duration-500 batch-progress-bar';
    if      (data.cancelled)  bar.classList.add('bg-red-400');
    else if (data.finished)   bar.classList.add('bg-green-400');
    else if (data.failed > 0) bar.classList.add('bg-amber-400');
    else                      bar.classList.add('bg-blue-500');

    // Status badge
    const badge = card.querySelector('.batch-badge');
    badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold font-mono batch-badge';
    if      (data.cancelled)    { badge.classList.add('bg-red-50','text-red-500');     badge.textContent = 'Cancelled'; }
    else if (data.finished)     { badge.classList.add('bg-green-50','text-green-600'); badge.textContent = 'Complete';  }
    else if (data.progress > 0) { badge.classList.add('bg-blue-50','text-blue-600');   badge.textContent = 'Running';   }
    else                        { badge.classList.add('bg-gray-100','text-gray-500');  badge.textContent = 'Queued';    }

    // Status icon
    const icon   = card.querySelector('.batch-icon');
    const iconBg = card.querySelector('.batch-status-bg');
    icon.className = 'bi batch-icon';
    if      (data.cancelled) { icon.classList.add('bi-slash-circle','text-red-500');        iconBg.className = 'w-8 h-8 rounded-lg flex items-center justify-center text-sm batch-status-bg bg-red-50';   }
    else if (data.finished)  { icon.classList.add('bi-check-circle-fill','text-green-500'); iconBg.className = 'w-8 h-8 rounded-lg flex items-center justify-center text-sm batch-status-bg bg-green-50'; }
    else                     { icon.classList.add('bi-arrow-repeat','text-blue-500');       iconBg.className = 'w-8 h-8 rounded-lg flex items-center justify-center text-sm batch-status-bg bg-blue-50';  }

    // Done / cancelled messages
    const doneMsg = card.querySelector('.batch-done');
    const canMsg  = card.querySelector('.batch-cancelled-msg');
    if (data.finished && !data.cancelled) { doneMsg.classList.remove('hidden'); doneMsg.classList.add('flex'); }
    if (data.cancelled)                   { canMsg.classList.remove('hidden');  canMsg.classList.add('flex'); }

    // Hide cancel button when done
    const cancelBtn = card.querySelector('.batch-cancel-btn');
    if (cancelBtn && (data.finished || data.cancelled)) cancelBtn.remove();
}

document.querySelectorAll('[data-batch-id]').forEach(card => {
    const batchId = card.dataset.batchId;
    let timer     = null;  
    let isRunning = false; 

    async function poll() {
        if (isRunning) return; 
        isRunning = true;

        try {
            const res  = await fetch(`${statusBase}/${batchId}`);
            const data = await res.json();
            updateCard(card, data);
            console.log(data);
            if (data.finished || data.cancelled) {
                console.log('Stopping polling', batchId);
                clearInterval(timer); 
                timer = null;
                isRunning = false;
                return; 
            }
        } catch {}

        isRunning = false;
    }

    poll();                          
    timer = setInterval(poll, 5000); 

    // Cancel button
    const cancelBtn = card.querySelector('.batch-cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', async () => {
            if (!confirm('Cancel this import? Pending rows will be skipped.')) return;

            clearInterval(timer); 
            timer = null;

            await fetch(`${cancelBase}/${batchId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            });

            updateCard(card, {
                name:      card.querySelector('.batch-name')?.textContent,
                progress:  parseInt(card.querySelector('.batch-progress-text')?.textContent) || 0,
                total:     card.querySelector('.batch-total')?.textContent,
                processed: card.querySelector('.batch-processed')?.textContent,
                pending:   0,
                failed:    card.querySelector('.batch-failed')?.textContent,
                finished:  false,
                cancelled: true,
            });
        });
    }
});
</script>
@endpush