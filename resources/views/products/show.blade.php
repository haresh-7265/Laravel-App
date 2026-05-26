@extends('layouts.app')

@section('title', $product->name)
@section('meta_description', meta_description($product->description ?? '', $product->name))

@section('content')

<input type="hidden" id="productId" value="{{ $product->id }}">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>{{ __('products.product_details') }}</h2>
        <div>
            @admin
            <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">{{ __('products.edit') }}</a>
            @endadmin
            <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('products.back') }}</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="row g-0">

            {{-- Product Image --}}
            <div class="col-md-4">
                <img src="{{ $product->image ? Storage::url($product->image) : Storage::url('products/default.png') }}"
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
                        <strong>{{ __('products.category') }}:</strong>
                        <span class="badge bg-primary">{{ $product->category->name }}</span>
                    </p>

                    {{-- Description --}}
                    <p class="card-text mb-3">
                        <strong>{{ __('products.description') }}:</strong><br>
                        {{ $product->description ?? __('products.no_description') }}
                    </p>

                    <hr>

                    {{-- Price & Stock --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block">{{ __('products.price') }}</small>
                                @if(!empty($product->discount_price) && $product->discount_price < $product->price)
                                    {{-- Has discount --}}
                                    <span class="text-decoration-line-through text-muted me-1">
                                        @currency($product->price)
                                    </span>
                                    <strong class="text-success fs-4">
                                        @currency($product->discount_price)
                                    </strong>
                                    <span class="badge bg-success ms-1" style="font-size: 10px;">
                                        {{ round(($product->price - $product->discount_price) / $product->price * 100) }}% OFF
                                    </span>
                                @else
                                    <strong class="text-success fs-4">
                                        @currency($product->price)
                                    </strong>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded text-center">
                                <small class="text-muted d-block">{{ __('products.stock') }}</small>
                                <strong class="fs-4 {{ $product->stock > 0 ? 'text-primary' : 'text-danger' }}" id="stockCount">
                                    {{ $product->stock > 0 ? __('products.units', ['count' => $product->stock]) : __('products.out_of_stock') }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    @if (!auth()->check() || auth()->user()->role == 'customer')
                        <hr>

                    {{-- Add to Cart --}}

                    @php $outOfStock = $product->stock <= 0; @endphp
                    <div id="cartWrapper" style="display: {{ $outOfStock ? 'none' : '' }};">
                        <form action="{{ route('cart.add', $product) }}" method="POST" class="d-flex gap-2 align-items-center" id="cart-form">
                            @csrf
                        
                            {{-- Quantity --}}
                            <input type="number" 
                                   name="quantity" 
                                   value="1" 
                                   min="1" 
                                   max="{{ $product->stock }}"
                                   class="form-control w-25">
                        
                            {{-- Button --}}
                            <button type="submit" class="btn btn-success" id="addToCartBtn">
                                {{ __('products.add_to_cart') }}
                            </button>
                        </form>
                    </div>
                        <button class="btn btn-secondary" id="outOfStockBtn" disabled style="display: {{ $outOfStock ? '' : 'none' }};">
                            {{ __('products.out_of_stock') }}
                        </button>
                    
                    @endif

                    @admin
            
                    <hr>

                    {{-- Timestamps --}}
                    <div class="row text-muted small">
                        <div class="col-md-6">
                            <strong>{{ __('products.created') }}</strong>
                            {{ $product->created_at->isoFormat('LL') }}
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('products.last_updated') }}</strong>
                            {{ $product->updated_at->isoFormat('LL') }}
                        </div>
                    </div>

                    <hr>

                    {{-- Actions --}}
                    <div class="d-flex gap-2">
                        <a href="{{ route('products.edit', $product) }}"
                           class="btn btn-warning">
                            {{ __('products.edit_product') }}
                        </a>

                        <form action="{{ route('products.destroy', $product) }}"
                              method="POST"
                              onsubmit="return confirm('{{ __('products.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">{{ __('products.delete_product') }}</button>
                        </form>
                    </div>

                    @endadmin

                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Reviews Section --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="mt-5">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">
                <i class="bi bi-chat-left-text me-2"></i>Customer Reviews
                <span class="badge bg-secondary ms-2">{{ $product->reviews->count() }}</span>
            </h3>
            @if($product->reviews->count() > 0)
                <div class="text-end">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4 fw-bold text-warning">{{ number_format($product->avg_rating, 1) }}</span>
                        <div>
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= round($product->avg_rating))
                                    <i class="bi bi-star-fill text-warning"></i>
                                @else
                                    <i class="bi bi-star text-warning"></i>
                                @endif
                            @endfor
                            <div class="text-muted small">{{ trans_choice('reviews_count', $product->reviews->count(), ['count' => $product->reviews->count()]) }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @auth
        @if(auth()->user()->isCustomer())
        @php $userReview = $product->reviews->where('user_id', auth()->id())->first(); @endphp
        <div class="row">
            {{-- ─── Left Column: Review Form (customers only) ────────────── --}}
            <div class="col-md-4 mb-4">
                <div class="position-sticky" style="top: 80px;">
                    <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-3" id="formTitle">
                                <i class="bi bi-pencil-square me-2"></i>
                                {{ $userReview ? 'Update Your Review' : 'Write a Review' }}
                            </h5>

                            <form action="{{ route('products.reviews.store', $product) }}" method="POST" id="reviewForm">
                                @csrf

                                {{-- Star Rating --}}
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
                                    <div class="star-rating-input d-flex gap-1" id="starRatingInput">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <label class="star-label" data-value="{{ $i }}" style="cursor: pointer; font-size: 1.8rem;">
                                                <input type="radio"
                                                       name="rating"
                                                       value="{{ $i }}"
                                                       class="d-none"
                                                       {{ old('rating', $userReview?->rating) == $i ? 'checked' : '' }}
                                                       required>
                                                <i class="bi {{ old('rating', $userReview?->rating) >= $i ? 'bi-star-fill text-warning' : 'bi-star text-muted' }}"></i>
                                            </label>
                                        @endfor
                                        <span class="ms-2 align-self-center text-muted small" id="ratingText">
                                            @if($userReview?->rating)
                                                {{ ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'][$userReview->rating] }}
                                            @else
                                                Select a rating
                                            @endif
                                        </span>
                                    </div>
                                    @error('rating')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Comment --}}
                                <div class="mb-3">
                                    <label for="reviewComment" class="form-label fw-semibold">Comment</label>
                                    <textarea name="comment"
                                              id="reviewComment"
                                              class="form-control @error('comment') is-invalid @enderror"
                                              rows="4"
                                              maxlength="1000"
                                              placeholder="Share your experience with this product...">{{ old('comment', $userReview?->comment) }}</textarea>
                                    <div class="d-flex justify-content-between mt-1">
                                        @error('comment')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @else
                                            <div></div>
                                        @enderror
                                        <small class="text-muted"><span id="charCount">{{ strlen(old('comment', $userReview?->comment ?? '')) }}</span>/1000</small>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-send me-1"></i>
                                        <span id="submitBtnText">{{ $userReview ? 'Update Review' : 'Submit Review' }}</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary d-none" id="cancelEditBtn" onclick="cancelEdit()">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Right Column: Reviews List ───────────────────────────── --}}
            <div class="col-md-8">
                @include('products._reviews-list', ['reviews' => $product->reviews, 'product' => $product])
            </div>
        </div>
        @else
            {{-- Admin: full-width reviews (no form) --}}
            @include('products._reviews-list', ['reviews' => $product->reviews, 'product' => $product])
        @endif
        @else
            {{-- Guest prompt --}}
            <div class="alert alert-light border mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-primary fs-5"></i>
                <span>
                    <a href="{{ route('login') }}" class="fw-semibold">Log in</a> to write a review.
                </span>
            </div>

            {{-- Guest: full-width reviews (read-only) --}}
            @include('products._reviews-list', ['reviews' => $product->reviews, 'product' => $product])
        @endauth
    </div>

@endsection

@push('scripts')
    @vite('resources/js/cart.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const labels = document.querySelectorAll('.star-label');
            const ratingText = document.getElementById('ratingText');
            const texts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

            if (!labels.length) return;

            labels.forEach(label => {
                const value = parseInt(label.dataset.value);

                label.addEventListener('mouseenter', () => {
                    labels.forEach(l => {
                        const icon = l.querySelector('i');
                        icon.className = parseInt(l.dataset.value) <= value
                            ? 'bi bi-star-fill text-warning'
                            : 'bi bi-star text-muted';
                    });
                    ratingText.textContent = texts[value];
                });

                label.addEventListener('click', () => {
                    label.querySelector('input').checked = true;
                    labels.forEach(l => {
                        const icon = l.querySelector('i');
                        icon.className = parseInt(l.dataset.value) <= value
                            ? 'bi bi-star-fill text-warning'
                            : 'bi bi-star text-muted';
                    });
                    ratingText.textContent = texts[value];
                });
            });

            const container = document.getElementById('starRatingInput');
            if (container) {
                container.addEventListener('mouseleave', () => {
                    const checked = container.querySelector('input:checked');
                    const selectedVal = checked ? parseInt(checked.value) : 0;
                    labels.forEach(l => {
                        const icon = l.querySelector('i');
                        icon.className = parseInt(l.dataset.value) <= selectedVal
                            ? 'bi bi-star-fill text-warning'
                            : 'bi bi-star text-muted';
                    });
                    ratingText.textContent = selectedVal ? texts[selectedVal] : 'Select a rating';
                });
            }

            // Character counter
            const textarea = document.getElementById('reviewComment');
            const counter = document.getElementById('charCount');
            if (textarea && counter) {
                textarea.addEventListener('input', () => {
                    counter.textContent = textarea.value.length;
                });
            }
        });

        /**
         * Pre-fill the review form for editing and scroll to it.
         */
        function editReview(rating, comment) {
            // Update form title
            document.getElementById('formTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Your Review';
            document.getElementById('submitBtnText').textContent = 'Update Review';
            document.getElementById('cancelEditBtn').classList.remove('d-none');

            // Set rating
            const labels = document.querySelectorAll('.star-label');
            const ratingText = document.getElementById('ratingText');
            const texts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

            labels.forEach(l => {
                const val = parseInt(l.dataset.value);
                const input = l.querySelector('input');
                const icon = l.querySelector('i');
                input.checked = val === rating;
                icon.className = val <= rating ? 'bi bi-star-fill text-warning' : 'bi bi-star text-muted';
            });
            ratingText.textContent = texts[rating];

            // Set comment
            const textarea = document.getElementById('reviewComment');
            textarea.value = comment;
            document.getElementById('charCount').textContent = comment.length;

            // Scroll to form
            document.getElementById('reviewForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
            textarea.focus();
        }

        /**
         * Reset form to "Write a Review" state.
         */
        function cancelEdit() {
            document.getElementById('formTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Write a Review';
            document.getElementById('submitBtnText').textContent = 'Submit Review';
            document.getElementById('cancelEditBtn').classList.add('d-none');

            // Clear form
            const labels = document.querySelectorAll('.star-label');
            labels.forEach(l => {
                l.querySelector('input').checked = false;
                l.querySelector('i').className = 'bi bi-star text-muted';
            });
            document.getElementById('ratingText').textContent = 'Select a rating';
            document.getElementById('reviewComment').value = '';
            document.getElementById('charCount').textContent = '0';
        }
    </script>
@endpush