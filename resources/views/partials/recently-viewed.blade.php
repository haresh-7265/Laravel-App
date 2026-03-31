{{-- resources/views/partials/recently-viewed.blade.php --}}
@if($recentlyViewed->isNotEmpty())
<div class="card border-0 shadow-sm mt-4">

    {{-- Header --}}
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2 px-3">
        <h6 class="mb-0 fw-semibold text-dark">
            <i class="bi bi-clock-history me-2 text-primary"></i>Recently Viewed
        </h6>
        <span class="badge bg-primary rounded-pill">{{ $recentlyViewed->count() }}</span>
    </div>

    {{-- Horizontal Scroll --}}
    <div class="card-body p-2">
        <div class="d-flex gap-2 overflow-auto pb-1" style="scroll-behavior: smooth;">
            @foreach($recentlyViewed as $product)
            <a href="{{ route('products.show', $product) }}"
               class="text-decoration-none text-dark flex-shrink-0"
               style="width: 100px;">

                <div class="card border-0 shadow-sm h-100 recently-viewed-card">

                    {{-- Image --}}
                    <div class="overflow-hidden rounded-top" style="height: 80px;">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}"
                                 alt="{{ $product->name }}"
                                 class="w-100 h-100 object-fit-cover">
                        @else
                            <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="p-1 text-center">
                        <p class="mb-0 text-truncate fw-semibold" style="font-size: 0.7rem;">
                            {{ $product->name }}
                        </p>
                        <p class="mb-0 text-primary fw-bold" style="font-size: 0.72rem;">
                            @currency($product->price)
                        </p>
                    </div>

                </div>
            </a>
            @endforeach
        </div>
    </div>

</div>

{{-- Style --}}
<style>
    /* hide scrollbar — still scrollable */
    .card-body .overflow-auto::-webkit-scrollbar { display: none; }
    .card-body .overflow-auto { -ms-overflow-style: none; scrollbar-width: none; }

    .recently-viewed-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .recently-viewed-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
    }
</style>
@endif