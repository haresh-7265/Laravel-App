{{--
    Export Filter Popup Component
    Usage: @include('components.export-filter-popup', ['categories' => $categories])
    Or as component: <x-export-filter-popup :categories="$categories" />
--}}

{{-- ── Trigger Button ── --}}
<button id="exportTriggerBtn" type="button">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2.2"
         stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
        <polyline points="7 10 12 15 17 10"/>
        <line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    Export CSV
</button>

{{-- ── Backdrop ── --}}
<div id="exportBackdrop"></div>

{{-- ── Modal ── --}}
<div id="exportModal" role="dialog" aria-modal="true" aria-labelledby="exportModalTitle">

    {{-- Header --}}
    <div class="ep-header">
        <div class="ep-header-left">
            <div class="ep-icon">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div>
                <h2 id="exportModalTitle">Export Products</h2>
                <p class="ep-subtitle">Filter &amp; download as CSV</p>
            </div>
        </div>
        <button class="ep-close-btn" id="exportCloseBtn" aria-label="Close dialog">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    {{-- Form --}}
    <form id="exportForm" action="{{ route('products.export') }}" method="GET">

        <div class="ep-body">

            {{-- Category --}}
            <div class="ep-field ep-full">
                <label for="ep_category">Category</label>
                <div class="ep-select-wrap">
                    <select id="ep_category" name="category_id">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <svg class="ep-select-arrow" width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </div>
            </div>

            {{-- Divider: Price --}}
            <div class="ep-divider ep-full">
                <span>Price range</span>
            </div>

            <div class="ep-field">
                <label for="ep_min_price">Min price (₹)</label>
                <div class="ep-input-wrap">
                    <span class="ep-prefix">₹</span>
                    <input type="number"
                           id="ep_min_price"
                           name="min_price"
                           placeholder="0.00"
                           step="0.01"
                           min="0"
                           value="{{ request('min_price') }}">
                </div>
            </div>

            <div class="ep-field">
                <label for="ep_max_price">Max price (₹)</label>
                <div class="ep-input-wrap">
                    <span class="ep-prefix">₹</span>
                    <input type="number"
                           id="ep_max_price"
                           name="max_price"
                           placeholder="9999.00"
                           step="0.01"
                           min="0"
                           value="{{ request('max_price') }}">
                </div>
            </div>

            {{-- Divider: Stock --}}
            <div class="ep-divider ep-full">
                <span>Stock range</span>
            </div>

            <div class="ep-field">
                <label for="ep_min_stock">Min stock</label>
                <input type="number"
                       id="ep_min_stock"
                       name="min_stock"
                       placeholder="0"
                       min="0"
                       value="{{ request('min_stock') }}">
            </div>

            <div class="ep-field">
                <label for="ep_max_stock">Max stock</label>
                <input type="number"
                       id="ep_max_stock"
                       name="max_stock"
                       placeholder="999"
                       min="0"
                       value="{{ request('max_stock') }}">
            </div>

        </div>{{-- /.ep-body --}}

        {{-- Footer --}}
        <div class="ep-footer">
            <button type="button" class="ep-btn-reset" id="exportResetBtn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <polyline points="1 4 1 10 7 10"/>
                    <path d="M3.51 15a9 9 0 1 0 .49-3.45"/>
                </svg>
                Reset filters
            </button>

            <div class="ep-btn-group">
                <button type="button" class="ep-btn-cancel" id="exportCancelBtn">
                    Cancel
                </button>
                <button type="submit" class="ep-btn-export">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>

    </form>
</div>

{{-- Load component assets --}}
@push('styles')
    @vite('resources/css/csvExport.css')
@endpush
    
@push('scripts')
    @vite('resources/js/csvExport.js')
@endpush