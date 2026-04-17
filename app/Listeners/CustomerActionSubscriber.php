<?php

namespace App\Listeners;

use App\Events\CartAbandoned;
use App\Events\Product\{ProductAddedToCart, ProductReviewed, ProductViewed};
use App\Jobs\SendAbandonedCartEmail;
use App\Mail\CartAbandonedMail;
use App\Services\RecentlyViewedService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CustomerActionSubscriber implements ShouldQueue
{
    public function __construct(private RecentlyViewedService $recentlyViewedService)
    {
    }
    // -------------------------------------------------------
    // ProductViewed — analytics + recently viewed
    // -------------------------------------------------------
    public function onProductViewed(ProductViewed $event): void
    {
        analytics()->track('product_viewed', [
            'product_id' => $event->product->id,
            'product_name' => $event->product->name,
            'category' => $event->product->category,
            'price' => $event->product->price,
            'user_id' => $event->user?->id,
            'session_id' => $event->sessionId,
            'timestamp' => now()->toISOString(),
        ]);

        $this->recentlyViewedService->track(
            $event->product->id,
            $event->user?->id,
            $event->sessionId
        );
    }

    // -------------------------------------------------------
    // ProductAddedToCart — supports guest users
    // -------------------------------------------------------
    public function onProductAddedToCart(ProductAddedToCart $event): void
    {
        analytics()->track('product_added_to_cart', [
            'product_id' => $event->product->id,
            'product_name' => $event->product->name,
            'quantity' => $event->quantity,
            'price' => $event->price,
            'revenue' => $event->price * $event->quantity,
            'user_id' => $event->user?->id,       // null for guests
            'is_guest' => is_null($event->user),
            'timestamp' => now()->toISOString(),
        ]);
    }

    // -------------------------------------------------------
    // CartAbandoned — schedule reminder, guest or auth user
    // -------------------------------------------------------
    public function onCartAbandoned(CartAbandoned $event): void
    {
        // Need at least an email to send reminder
        $email = $event->user?->email;

        if (empty($email)) {
            return; // guest with no email — skip
        }

        Mail::to($email)->send(new CartAbandonedMail(
            user: $event->user,
            cartItems: $event->cartItems,
            cartTotal: $event->cartTotal,
        ));
    }

    // -------------------------------------------------------
    // ProductReviewed — recalculate rating (no Review model)
    // -------------------------------------------------------
    public function onProductReviewed(ProductReviewed $event): void
    {
        $event->product->update([
            'avg_rating' => $event->product->averageRating(),
        ]);

    }

    // -------------------------------------------------------
    // Wire events → methods
    // -------------------------------------------------------
    public function subscribe(Dispatcher $events): array
    {
        return [
            ProductViewed::class => 'onProductViewed',
            ProductAddedToCart::class => 'onProductAddedToCart',
            CartAbandoned::class => 'onCartAbandoned',
            ProductReviewed::class => 'onProductReviewed',
        ];
    }

    public function withDelay(object $event): array
    {
        return [
            'onCartAbandoned' => now()->addHours(24),
        ];
    }

    public function failed(object $event, Throwable $exception): void
    {
        \Log::error('CustomerActionSubscriber job failed', [
            'event' => get_class($event),
            'exception' => $exception->getMessage(),
        ]);
    }
}