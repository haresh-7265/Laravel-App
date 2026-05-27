{{-- Shared reviews list partial — used by guest, customer, and admin views --}}
@forelse($reviews as $review)
    <div class="card mb-3 border-0 shadow-sm {{ current_user() && $review->user_id === current_user()->id ? 'border-start border-primary border-3' : '' }}" id="review-{{ $review->id }}">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-3">
                    {{-- Avatar --}}
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                         style="width: 42px; height: 42px; font-size: 0.9rem;">
                        {{ Str::initials($review->user->name) }}
                    </div>
                    <div>
                        <div class="fw-semibold">
                            {{ $review->user->name }}
                            @if(current_user() && $review->user_id === current_user()->id)
                                <span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size: 0.7rem;">You</span>
                            @endif
                        </div>
                        <div>
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= $review->rating ? 'bi-star-fill text-warning' : 'bi-star text-muted' }}" style="font-size: 0.85rem;"></i>
                            @endfor
                            <span class="text-muted small ms-1">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-1">
                    @can('update', $review)
                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                onclick="editReview({{ $review->rating }}, '{{ addslashes($review->comment ?? '') }}')"
                                title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                    @endcan
                    @can('delete', $review)
                        <form action="{{ route('products.reviews.destroy', [$product, $review]) }}"
                              method="POST"
                              onsubmit="return confirm('Delete your review?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            @if($review->comment)
                <p class="mt-2 mb-0 text-muted ps-5 ms-3">{{ $review->comment }}</p>
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-chat-left-dots fs-1 d-block mb-2"></i>
        <p class="mb-0">No reviews yet. {{ (is_guest() || is_customer()) ? "Be the first to share your thoughts!" : '' }}</p>
    </div>
@endforelse
