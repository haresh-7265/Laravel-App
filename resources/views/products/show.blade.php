@extends('layouts.app')

@section('title', $product->name)

@section('content')

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Product Details</h2>
        <div>
            @admin
            <a href="{{ route('products.edit', $product->slug) }}" class="btn btn-warning">Edit</a>
            @endadmin
            <a href="{{ route('products.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="row g-0">

            {{-- Product Image --}}
            <div class="col-md-4">
                <img src="{{ $product->image ? asset('storage/'. $product->image) : asset('storage/products/default.jpg') }}"
                     alt="{{ $product->name }}"
                     class="img-fluid rounded-start"
                     style="width: 100%; height: 350px; object-fit: cover;">
            </div>

            {{-- Product Details --}}
            <div class="col-md-8">
                <div class="card-body p-4">

                    {{-- Name --}}
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h3 class="card-title mb-0">{{ $product->name }}</h3>
                    </div>

                    {{-- Category --}}
                    <p class="text-muted mb-2">
                        <strong>Category:</strong>
                        <span class="badge bg-primary">{{ $product->category->name }}</span>
                    </p>

                    {{-- Description --}}
                    <p class="card-text mb-3">
                        <strong>Description:</strong><br>
                        {{ $product->description ?? 'No description available.' }}
                    </p>

                    <hr>

                    {{-- Price & Stock --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block">Price</small>
                                <strong class="fs-4 text-success">
                                    @currency($product->price)
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block">Stock</small>
                                <strong class="fs-4 {{ $product->stock > 0 ? 'text-primary' : 'text-danger' }}">
                                    {{ $product->stock > 0 ? $product->stock . ' units' : 'Out of Stock' }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    @if (auth()->user()->role == 'customer')
                        <hr>

{{-- Add to Cart --}}
@if($product->stock > 0)
    <form action="{{ route('cart.add', $product->slug) }}" method="POST" class="d-flex gap-2 align-items-center">
        @csrf

        {{-- Quantity --}}
        <input type="number" 
               name="qty" 
               value="1" 
               min="1" 
               max="{{ $product->stock }}"
               class="form-control w-25">

        {{-- Button --}}
        <button type="submit" class="btn btn-success">
            🛒 Add to Cart
        </button>
    </form>
@else
    <button class="btn btn-secondary" disabled>
        Out of Stock
    </button>
@endif
                    @endif

                    @admin
            
                    <hr>

                    {{-- Timestamps --}}
                    <div class="row text-muted small">
                        <div class="col-md-6">
                            <strong>Created:</strong>
                            {{ $product->created_at->format('d M Y, h:i A') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Last Updated:</strong>
                            {{ $product->updated_at->format('d M Y, h:i A') }}
                        </div>
                    </div>

                    <hr>

                    {{-- Actions --}}
                    <div class="d-flex gap-2">
                        <a href="{{ route('products.edit', $product->slug) }}"
                           class="btn btn-warning">
                            Edit Product
                        </a>

                        <form action="{{ route('products.destroy', $product->slug) }}"
                              method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this product?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Product</button>
                        </form>
                    </div>

                    @endadmin

                </div>
            </div>
        </div>
    </div>

@endsection