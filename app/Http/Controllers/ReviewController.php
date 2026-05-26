<?php

namespace App\Http\Controllers;

use App\Events\Product\ProductReviewed;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ReviewController extends Controller
{
    /**
     * Store or update a review for a product.
     * Each user can only leave one review per product (upsert).
     * Rate limited: 5 review submissions per hour per user.
     */
    public function store(Request $request, Product $product)
    {
        // Only customer can review product
        if (! auth()->user()->isCustomer()) {
            abort(403);
        }

        $validated = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // ── Programmatic rate limiting: 5 reviews per hour ──────────────
        $key = 'reviews:' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return back()
                ->withInput()
                ->withErrors([
                    'rating' => "You've submitted too many reviews. Please try again in {$minutes} " . \Str::plural('minute', $minutes) . " ({$seconds}s).",
                ]);
        }

        RateLimiter::hit($key, 3600); // decay in 1 hour

        ProductReview::updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id'    => auth()->id(),
            ],
            [
                'rating'  => $validated['rating'],
                'comment' => $validated['comment'],
            ]
        );

        ProductReviewed::dispatch($product);

        return back()->with('success', 'Your review has been saved!');
    }

    /**
     * Delete the authenticated user's review.
     */
    public function destroy(Product $product, ProductReview $review)
    {
        // Only the review author can delete
        if ($review->user_id !== auth()->id()) {
            abort(403);
        }

        $review->delete();

        // Recalculate average rating
        $avg = $product->averageRating();
        $product->update(['avg_rating' => $avg ?? 0]);

        return back()->with('success', 'Your review has been deleted.');
    }
}

