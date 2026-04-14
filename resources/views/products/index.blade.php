@extends('layouts.app')

@section('title','Products')

@section('content')

@include('partials.recently-viewed', ['recentlyViewed' => $recentlyViewed])

<div class="row g-4">

    {{-- ═══════ LEFT: FILTER SIDEBAR ═══════ --}}
    <x-product-filter/>

    {{-- ═══════ RIGHT: PRODUCT GRID ═══════ --}}
    <div class="col-lg-9">

        {{-- ═══════ HOMEPAGE SECTIONS (Concurrent Data) ═══════ --}}
            @if($featured->isNotEmpty())
            <div class="mb-5">
                <h3 class="mb-3">Featured Products</h3>

                <div class="d-flex overflow-auto gap-3 pb-2 horizontal-scroll" style="scroll-snap-type: x mandatory;">
                    @foreach($featured as $p)
                        <div style="min-width: 280px; flex: 0 0 auto; scroll-snap-align: start;">
                            <x-product-card :product="$p" />
                        </div>
                    @endforeach
                </div>
            </div>
            @endif


            @if($newArrivals->isNotEmpty())
            <div class="mb-5">
                <h3 class="mb-3">New Arrivals</h3>

                <div class="d-flex overflow-auto gap-3 pb-2 horizontal-scroll" style="scroll-snap-type: x mandatory;">
                    @foreach($newArrivals as $p)
                        <div style="min-width: 280px; flex: 0 0 auto; scroll-snap-align: start;">
                            <x-product-card :product="$p" />
                        </div>
                    @endforeach
                </div>
            </div>
            @endif


            @if($onSale->isNotEmpty())
            <div class="mb-5">
                <h3 class="mb-3">On Sale</h3>

                <div class="d-flex overflow-auto gap-3 pb-2 horizontal-scroll" style="scroll-snap-type: x mandatory;">
                    @foreach($onSale as $p)
                        <div style="min-width: 280px; flex: 0 0 auto; scroll-snap-align: start;">
                            <x-product-card :product="$p" />
                        </div>
                    @endforeach
                </div>
            </div>
            @endif


            <hr class="mb-5">

            <h3 class="mb-3">All Products</h3>

        {{-- Results header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>

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
