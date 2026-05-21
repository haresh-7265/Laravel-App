<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_factories_can_create_models(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(User::class, $user);

        $admin = User::factory()->admin()->create();
        $this->assertEquals('admin', $admin->role);

        $customer = User::factory()->customer()->create();
        $this->assertEquals('customer', $customer->role);

        $category = Category::factory()->create();
        $this->assertInstanceOf(Category::class, $category);

        $product = Product::factory()->create();
        $this->assertInstanceOf(Product::class, $product);

        $featuredProduct = Product::factory()->featured()->create();
        $this->assertContains('featured', $featuredProduct->tags);

        $outOfStockProduct = Product::factory()->outOfStock()->create();
        $this->assertEquals(0, $outOfStockProduct->stock);

        $onSaleProduct = Product::factory()->onSale()->create();
        $this->assertNotNull($onSaleProduct->discount_price);

        $publishedProduct = Product::factory()->published()->create();
        $this->assertTrue($publishedProduct->is_active);

        $order = Order::factory()->create();
        $this->assertInstanceOf(Order::class, $order);

        $pendingOrder = Order::factory()->pending()->create();
        $this->assertEquals('pending', $pendingOrder->status);

        $orderItem = OrderItem::factory()->create();
        $this->assertInstanceOf(OrderItem::class, $orderItem);

        $review = ProductReview::factory()->create();
        $this->assertInstanceOf(ProductReview::class, $review);
    }

    public function test_composed_relationships(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()
            ->for($user)
            ->has(OrderItem::factory()->count(3), 'items')
            ->create();

        $this->assertEquals($user->id, $order->user_id);
        $this->assertCount(3, $order->items);
    }
}
