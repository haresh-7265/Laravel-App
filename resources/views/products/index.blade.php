@extends('layouts.app')

@section('title', __('products.title'))

@section('content')

@include('partials.recently-viewed', ['recentlyViewed' => $recentlyViewed])

<div class="row g-4">

    {{-- SEARCH BAR --}}
    <x-product-search/>
    {{-- ═══════ LEFT: FILTER SIDEBAR ═══════ --}}
    <x-product-filter :hasFilters=$hasFilters/>

    {{-- ═══════ RIGHT: PRODUCT GRID ═══════ --}}
    <div class="col-lg-9">

        {{-- ═══════ HOMEPAGE SECTIONS (Concurrent Data) ═══════ --}}
            @if($featured->isNotEmpty())
                <x-collapsible-section title="{{ __('products.featured') }}">
                    @foreach($featured as $p)
                        <div class="min-w-[280px] flex-none snap-start">
                            <x-product-card :product="$p" />
                        </div>
                    @endforeach
                </x-collapsible-section>
            @endif

            @if($newArrivals->isNotEmpty())
                <x-collapsible-section title="{{ __('products.new_arrivals') }}">
                    @foreach($newArrivals as $p)
                        <div class="min-w-[280px] flex-none snap-start">
                            <x-product-card :product="$p" />
                        </div>
                    @endforeach
                </x-collapsible-section>
            @endif

            @if($onSale->isNotEmpty())
                <x-collapsible-section title="{{ __('products.on_sale') }}">
                    @foreach($onSale as $p)
                        <div class="min-w-[280px] flex-none snap-start">
                            <x-product-card :product="$p" />
                        </div>
                    @endforeach
                </x-collapsible-section>
            @endif


            <hr class="mb-5">

            <h3 class="mb-3 text-lg font-semibold">{{ __('products.all_products') }}</h3>
            <div class="text-blue-500">
                {{ $products->total() ." ". Str::plural('product', $products->total()) . " found" }}
            </div>
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
                            <span class="filter-badge"><i class="bi bi-box-seam"></i> {{ __('products.stock') }}</span>
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
                        {{ __('products.no_products') }}
                        <a href="{{ route('products.index') }}" class="alert-link ms-1">{{ __('products.clear_filters') }}</a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        {{ $products->links() }}
    </div>

</div>

@section('footer')
<footer class="bg-dark text-white mt-auto py-3 flex-col">
    <div class="container text-center">
        <span class="fw-bold me-2">{{ config('app.name') }}</span>
        <span class="text-white-50 small">
            &copy; {{ date('Y') }} All rights reserved.
        </span>
    </div>
    <div class="container text-center">
        <a href="{{ route('support.tickets.create') }}" class="underline">support</a>
    </div>
    <div class="container text-center">
        <a href="{{ route('contact.create') }}" class="underline">contact Us</a>
    </div>
</footer>
@endsection

@push('scripts')
    @vite('resources/js/cart.js')
@endpush
@endsection
