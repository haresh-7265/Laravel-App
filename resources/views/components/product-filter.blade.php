<div class="col-lg-3">
    <div class="position-sticky" style="top: 60px;">
        <form method="GET" action="{{ route('products.index') }}" id="filterForm">
            <div class="filter-panel">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-funnel me-1"></i>Filters
                    </h5>

                    @if($hasFilters ?? false)
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Clear
                        </a>
                    @endif
                </div>

                {{-- Sort --}}
                <div class="filter-section">
                    <h6>Sort By</h6>
                    <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Default</option>
                        <option value="price_low"  @selected(request('sort') == 'price_low')>Low to High</option>
                        <option value="price_high" @selected(request('sort') == 'price_high')>High to Low</option>
                        <option value="popularity" @selected(request('sort') == 'popularity')>Popularity</option>
                        <option value="newest"     @selected(request('sort') == 'newest')>Newest</option>
                    </select>
                </div>

                {{-- Price --}}
                <div class="filter-section">
                    <h6>Price</h6>
                    <div class="d-flex gap-2">
                        <input type="number" name="min_price" class="form-control form-control-sm"
                               placeholder="Min" value="{{ request('min_price') }}">
                        <input type="number" name="max_price" class="form-control form-control-sm"
                               placeholder="Max" value="{{ request('max_price') }}">
                    </div>
                </div>

                {{-- Categories --}}
                <div class="filter-section">
                    <h6>Categories</h6>
                    <div class="category-section">
                    @foreach($categories as $category)
                        <div>
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                   @checked(in_array($category->id, (array) request('categories', [])))>
                            {{ $category->name }}
                        </div>
                    @endforeach
                    </div>
                </div>

                {{-- Toggles --}}
                <div class="filter-section">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="in_stock" value="1"
                               @checked(request('in_stock'))>
                        <label class="form-check-label">In Stock</label>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="on_sale" value="1"
                               @checked(request('on_sale'))>
                        <label class="form-check-label">On Sale</label>
                    </div>
                </div>

                <button class="btn btn-primary btn-sm w-100 mt-2">Apply</button>

            </div>
        </form>
    </div>
</div>

@push('styles')
    <style>
         /* ═══════ Filter Panel ═══════ */
    .filter-panel {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,.08);
        padding: 1.25rem;
        position: sticky;
        top: 80px;
    }
    .filter-panel h6 {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #6c757d;
        margin-bottom: .6rem;
        padding-bottom: .4rem;
        border-bottom: 2px solid #f0f0f0;
    }
    .filter-section { margin-bottom: 1.1rem; }
    .filter-section:last-child { margin-bottom: 0; }
    .category-section{
        height: 15rem;
        overflow-y: auto;
    }

    /* Price range inputs */
    .price-range-group {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .price-range-group input {
        width: 100%;
        font-size: .85rem;
    }
    .price-range-group .dash {
        color: #adb5bd;
        font-weight: 600;
    }

    /* Category checkboxes */
    .filter-check {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 3px 0;
    }
    .filter-check input[type=checkbox] {
        width: 16px;
        height: 16px;
        accent-color: #0d6efd;
        cursor: pointer;
    }
    .filter-check label {
        font-size: .85rem;
        cursor: pointer;
        margin: 0;
    }

    /* Sort select */
    .sort-select {
        font-size: .85rem;
        border-radius: 8px;
    }

    /* Toggle switches */
    .filter-switch {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
    }
    .filter-switch label {
        font-size: .85rem;
        margin: 0;
        cursor: pointer;
    }

    /* Active filter badges */
    .active-filters { display: flex; flex-wrap: wrap; gap: 6px; }
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #e7f1ff;
        color: #0d6efd;
        font-size: .75rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        border: 1px solid #b6d4fe;
    }
    </style>
@endpush