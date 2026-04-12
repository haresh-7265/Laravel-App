@extends('layouts.app')

@section('title','Products')

@section('style')
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

    /* Results summary */
    .results-summary {
        font-size: .85rem;
        color: #6c757d;
    }
    .results-summary strong { color: #212529; }
</style>
@endsection

@section('content')

@include('partials.recently-viewed', ['recentlyViewed' => $recentlyViewed])

<div class="row g-4">

    {{-- ═══════ LEFT: FILTER SIDEBAR ═══════ --}}
    <div class="col-lg-3">
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

                {{-- ─── Sort By ─── --}}
                <div class="filter-section">
                    <h6><i class="bi bi-sort-down me-1"></i>Sort By</h6>
                    <select name="sort" class="form-select form-select-sm sort-select" onchange="this.form.submit()">
                        <option value="">Default (Newest)</option>
                        <option value="price_low"  {{ request('sort') == 'price_low'  ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="popularity" {{ request('sort') == 'popularity' ? 'selected' : '' }}>Popularity (Most Sales)</option>
                        <option value="newest"     {{ request('sort') == 'newest'     ? 'selected' : '' }}>Newest First</option>
                    </select>
                </div>

                {{-- ─── Price Range ─── --}}
                <div class="filter-section">
                    <h6><i class="bi bi-currency-rupee me-1"></i>Price Range</h6>
                    <div class="price-range-group">
                        <input type="number" name="min_price" class="form-control form-control-sm"
                               placeholder="Min" min="0" step="1"
                               value="{{ request('min_price') }}">
                        <span class="dash">–</span>
                        <input type="number" name="max_price" class="form-control form-control-sm"
                               placeholder="Max" min="0" step="1"
                               value="{{ request('max_price') }}">
                    </div>
                </div>

                {{-- ─── Categories ─── --}}
                <div class="filter-section">
                    <h6><i class="bi bi-tag me-1"></i>Categories</h6>
                    @foreach($categories as $category)
                        <div class="filter-check">
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                   id="cat-{{ $category->id }}"
                                   {{ in_array($category->id, (array) request('categories', [])) ? 'checked' : '' }}>
                            <label for="cat-{{ $category->id }}">{{ $category->name }}</label>
                        </div>
                    @endforeach
                </div>

                {{-- ─── Toggles: In Stock / On Sale ─── --}}
                <div class="filter-section">
                    <h6><i class="bi bi-toggles me-1"></i>Availability</h6>
                    <div class="filter-switch">
                        <label for="in_stock">In Stock Only</label>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="in_stock" value="1"
                                   id="in_stock" {{ request('in_stock') ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="filter-switch">
                        <label for="on_sale">On Sale</label>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="on_sale" value="1"
                                   id="on_sale" {{ request('on_sale') ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                {{-- ─── Apply Button ─── --}}
                <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                    <i class="bi bi-search me-1"></i>Apply Filters
                </button>

            </div>
        </form>
    </div>

    {{-- ═══════ RIGHT: PRODUCT GRID ═══════ --}}
    <div class="col-lg-9">

        {{-- Results header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <span class="results-summary">
                    Showing <strong>{{ $products->count() }}</strong> of
                    <strong>{{ $total_products }}</strong> products
                </span>

                {{-- Active filter badges --}}
                @if($hasFilters ?? false)
                    <div class="active-filters mt-1">
                        @if(request('min_price') || request('max_price'))
                            <span class="filter-badge">
                                <i class="bi bi-currency-rupee"></i>
                                {{ request('min_price', '0') }} – {{ request('max_price', '∞') }}
                            </span>
                        @endif
                        @if(request('categories'))
                            @foreach((array) request('categories') as $catId)
                                @php $cat = $categories->firstWhere('id', $catId); @endphp
                                @if($cat)
                                    <span class="filter-badge">
                                        <i class="bi bi-tag-fill"></i> {{ $cat->name }}
                                    </span>
                                @endif
                            @endforeach
                        @endif
                        @if(request('in_stock'))
                            <span class="filter-badge"><i class="bi bi-box-seam"></i> In Stock</span>
                        @endif
                        @if(request('on_sale'))
                            <span class="filter-badge"><i class="bi bi-percent"></i> On Sale</span>
                        @endif
                        @if(request('sort'))
                            <span class="filter-badge">
                                <i class="bi bi-sort-down"></i>
                                {{ ['price_low'=>'Price ↑','price_high'=>'Price ↓','popularity'=>'Popular','newest'=>'Newest'][request('sort')] ?? request('sort') }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            @admin
                <x-export-filter-popup/>
            @endadmin
        </div>

        {{-- Product Cards Grid --}}
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-4">
            @forelse($products as $product)
                <div class="col">
                    <x-product-card :product="$product" />
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-search me-1"></i>
                        No products match your filters.
                        <a href="{{ route('products.index') }}" class="alert-link ms-1">Clear all filters</a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        {{ $products->links() }}
    </div>

</div>

@section('footer')
<footer class="bg-dark text-white mt-auto py-3">
    <div class="container text-center">
        <span class="fw-bold me-2">{{ config('app.name') }}</span>
        <span class="text-white-50 small">
            &copy; {{ date('Y') }} All rights reserved.
        </span>
    </div>
</footer>
@endsection
@endsection
