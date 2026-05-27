<?php

namespace App\Policies;

use App\Models\ProductReview;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user instanceof User;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, ProductReview $review): bool
    {
        return $user instanceof User 
            && $review->user_id === $user->id 
            && $review->created_at->gt(now()->subHours(24));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, ProductReview $review): bool
    {
        return $user instanceof User 
            && $review->user_id === $user->id 
            && $review->created_at->gt(now()->subHours(24));
    }
}
