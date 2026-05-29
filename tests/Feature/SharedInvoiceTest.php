<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SharedInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed the customer role required by UserFactory afterCreating hook
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
    }

    public function test_guest_can_download_invoice_with_valid_encrypted_payload()
    {
        Storage::fake('public');
        Storage::disk('public')->put('invoices/dummy.pdf', 'dummy content');

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'invoice_path' => 'invoices/dummy.pdf',
            'status' => 'delivered',
        ]);

        $jsonData = json_encode([
            'order_id' => $order->id,
            'expires_at' => now()->addHours(2)->timestamp,
        ]);
        $payload = Crypt::encrypt($jsonData, false);

        $response = $this->get(route('shared-invoice.download', ['payload' => $payload]));

        $response->assertStatus(200);
        $this->assertEquals('dummy content', $response->streamedContent());
    }

    public function test_guest_cannot_download_invoice_with_invalid_payload()
    {
        $response = $this->get(route('shared-invoice.download', ['payload' => 'invalid-payload']));
        $response->assertStatus(403);
    }

    public function test_guest_cannot_download_invoice_with_expired_payload()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'invoice_path' => 'invoices/dummy.pdf',
            'status' => 'delivered',
        ]);

        $jsonData = json_encode([
            'order_id' => $order->id,
            'expires_at' => now()->subMinute()->timestamp,
        ]);
        $payload = Crypt::encrypt($jsonData, false);

        $response = $this->get(route('shared-invoice.download', ['payload' => $payload]));
        $response->assertStatus(403);
    }
}
