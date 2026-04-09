@extends('layouts.app')

@section('title','Products')

@section('content')

@include('partials.recently-viewed', ['recentlyViewed' => $recentlyViewed])

<div class="d-flex justify-content-between align-items-center mb-3">
    @admin
        <x-export-filter-popup/>
    @endadmin
</div>

{{-- Product Cards Grid --}}
    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-5">
        @forelse($products as $product)
            <div class="col">
                <x-product-card :product="$product" />
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    No products found.
                </div>
            </div>
        @endforelse
    </div>

{{-- Render pagination links --}}
{{ $products->links() }}

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
