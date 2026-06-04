<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Channels\WebhookChannel;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderShipped;
use App\Notifications\ProductLowStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Order $order;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);

        $category = Category::create([
            'name' => 'Test Category',
        ]);

        $this->product = Product::create([
            'name'        => 'Test Widget',
            'slug'        => 'test-widget',
            'price'       => 49.99,
            'stock'       => 50,
            'category_id' => $category->id,
        ]);

        $this->order = Order::create([
            'user_id'          => $this->customer->id,
            'order_number'     => 'ORD-TEST001',
            'status'           => 'processing',
            'subtotal'         => 49.99,
            'total'            => 49.99,
            'payment_method'   => 'cod',
            'payment_status'   => 'unpaid',
            'shipping_name'    => $this->customer->name,
            'shipping_email'   => $this->customer->email,
            'shipping_phone'   => '1234567890',
            'shipping_address' => '123 Test St',
            'shipping_city'    => 'TestCity',
            'shipping_state'   => 'TestState',
            'shipping_pincode' => '123456',
        ]);

        OrderItem::create([
            'order_id'     => $this->order->id,
            'product_id'   => $this->product->id,
            'product_name' => $this->product->name,
            'price'        => 49.99,
            'quantity'     => 1,
            'subtotal'     => 49.99,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 1: Admin marks order as shipped → OrderShipped sent to customer
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function admin_shipping_order_sends_notification_to_customer(): void
    {
        Notification::fake();

        // Act — admin updates status to "shipped"
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $this->order), [
                'status' => 'shipped',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert — the customer received an OrderShipped notification
        Notification::assertSentTo(
            $this->customer,
            OrderShipped::class,
            function (OrderShipped $notification) {
                return $notification->order->id === $this->order->id;
            }
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 2: Invalid status transition → OrderShipped NOT sent
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function invalid_status_transition_does_not_send_notification(): void
    {
        Notification::fake();

        // Order is in "processing" — cannot jump directly to "delivered"
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $this->order), [
                'status' => 'delivered',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        // Assert — no OrderShipped was sent
        Notification::assertNotSentTo($this->customer, OrderShipped::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 3: Guest checkout → assertSentOnDemand (AnonymousNotifiable)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function guest_checkout_sends_notification_on_demand(): void
    {
        Notification::fake();

        // Simulate an on-demand notification to a guest email
        Notification::route('mail', 'guest@example.com')
            ->notify(new OrderShipped($this->order));

        // Assert — notification was sent on-demand
        Notification::assertSentOnDemand(
            OrderShipped::class,
            function (OrderShipped $notification, array $channels, AnonymousNotifiable $notifiable) {
                return $notifiable->routes['mail'] === 'guest@example.com'
                    && $notification->order->id === $this->order->id;
            }
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 4: via() returns expected channels per notification type
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function order_shipped_via_returns_mail_and_database(): void
    {
        $notification = new OrderShipped($this->order);

        $channels = $notification->via($this->customer);

        $this->assertEquals(['mail', 'database'], $channels);
    }

    /** @test */
    public function new_order_received_via_returns_all_five_channels(): void
    {
        $notification = new NewOrderReceived($this->order);

        $channels = $notification->via($this->admin);

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
        $this->assertContains(WebhookChannel::class, $channels);
        $this->assertContains('slack', $channels);
        $this->assertCount(5, $channels);
    }

    /** @test */
    public function product_low_stock_via_returns_mail_database_and_slack(): void
    {
        $notification = new ProductLowStock($this->product);

        $channels = $notification->via($this->admin);

        $this->assertEquals(['mail', 'database', 'slack'], $channels);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 5: toArray() payload verification
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function order_shipped_to_array_contains_expected_payload(): void
    {
        $notification = new OrderShipped($this->order);

        $payload = $notification->toArray($this->customer);

        $this->assertEquals($this->order->id, $payload['order_id']);
        $this->assertEquals('N/A', $payload['tracking_number']);
        $this->assertStringContains('ORD-TEST001', $payload['message']);
        $this->assertEquals('truck', $payload['icon']);
    }

    /** @test */
    public function new_order_received_to_database_contains_expected_payload(): void
    {
        $notification = new NewOrderReceived($this->order);

        $payload = $notification->toDatabase($this->admin);

        $this->assertEquals($this->order->id, $payload['order_id']);
        $this->assertEquals('ORD-TEST001', $payload['order_number']);
        $this->assertEquals($this->customer->name, $payload['customer_name']);
        $this->assertEquals('order', $payload['icon']);
        $this->assertStringContains('ORD-TEST001', $payload['message']);
        $this->assertStringContains($this->customer->name, $payload['message']);
    }

    /** @test */
    public function product_low_stock_to_database_contains_expected_payload(): void
    {
        $notification = new ProductLowStock($this->product);

        $payload = $notification->toDatabase($this->admin);

        $this->assertEquals($this->product->id, $payload['product_id']);
        $this->assertEquals('Test Widget', $payload['product_name']);
        $this->assertEquals(50, $payload['stock']);
        $this->assertEquals('alert', $payload['icon']);
        $this->assertStringContains('Test Widget', $payload['message']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 6: Notification count — exact number sent
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function order_shipped_is_sent_exactly_once(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $this->order), [
                'status' => 'shipped',
            ]);

        Notification::assertSentToTimes($this->customer, OrderShipped::class, 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Custom assertion to check that a string contains a substring.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
