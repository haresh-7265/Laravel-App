@props(['product'])
<div class="card h-100 shadow-sm border-0 product-card">

    {{-- Product Image --}}
    <div style="position: relative; overflow: hidden;">
        <img src="{{ $product->image ? asset('storage/'. $product->image) : asset('storage/products/default.jpg') }}"
             alt="{{ $product->name }}"
             class="card-img-top"
             style="height: 220px; object-fit: cover; transition: transform 0.3s ease;">


        {{-- Category Badge --}}
        <span class="badge bg-primary"
              style="position: absolute; top: 10px; left: 10px;">
            {{ $product->category->name }}
        </span>
    </div>

    <div class="card-body d-flex flex-column">

        {{-- Name --}}
        <h5 class="card-title fw-bold mb-1">{{ $product->name }}</h5>

        {{-- Description --}}
        <p class="card-text text-muted small mb-3"
           style="display: -webkit-box; -webkit-line-clamp: 2;
                  -webkit-box-orient: vertical; overflow: hidden;">
            {{ Str::limit($product->description ?? 'No description available.', 30, '...') }}
        </p>

        {{-- Price & Stock --}}
        <div class="d-flex justify-content-between align-items-center mb-3 mt-auto">
            <div>
                @if(!empty($product->discount_price) && $product->discount_price < $product->price)
                    {{-- Has discount --}}
                    <span class="text-decoration-line-through text-muted me-1">
                        @currency($product->price)
                    </span>
                    <strong class="text-success fs-5">
                        @currency($product->discount_price)
                    </strong>
                    <span class="badge bg-success ms-1" style="font-size: 10px;">
                        {{ round(($product->price - $product->discount_price) / $product->price * 100) }}% OFF
                    </span>
                @else
                    <strong class="text-success fs-5">
                        @currency($product->price)
                    </strong>
                @endif
            </div>
        </div>
        <small class="{{ $product->stock > 0 ? 'text-primary' : 'text-danger' }} mb-1">
            {{ $product->stock > 0 ? $product->stock . ' in stock' : 'Out of Stock' }}
        </small>

        {{-- Actions --}}
        <div class="d-flex gap-2">
            <a href="{{ route('products.show', $product->slug) }}"
               class="btn btn-outline-primary btn-sm flex-fill">View</a>

            @can('edit-product')
                <a href="{{ route('products.edit', $product->slug) }}"
                   class="btn btn-warning btn-sm flex-fill">Edit</a>
            @endcan

            @can('delete-product')
                <form action="{{ route('products.destroy', $product->id) }}"
                      method="POST"
                      onsubmit="return confirm('Delete this product?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            @endcan
        </div>
    </div>
</div>