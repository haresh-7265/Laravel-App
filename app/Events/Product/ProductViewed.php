<?php

namespace App\Events\Product;

use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductViewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly User|Admin|null $user = null,
        public readonly string $sessionId = ''
    ) {}
}