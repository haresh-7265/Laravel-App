<?php

namespace App\Events\Product;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductReviewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly User $user,
        public readonly ProductReview $review
    ) {
    }
}