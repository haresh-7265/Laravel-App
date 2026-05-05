<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FakeStoreApiTest extends TestCase
{
    use RefreshDatabase;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();  // ← Laravel boots first

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function fakeProducts(): array
    {
        return [
            [
                "id" => 1,
                "title" => 'Fjallraven Backpack',
                "price" => 109.95,
                "description" => "Your perfect pack for everyday use and walks in the forest. Stash your laptop (up to 15 inches) in the padded sleeve, your everyday",
                "category" => "men's clothing",
                "image" => "https://fakestoreapi.com/img/81fPKd-2AYL._AC_SL1500_t.png",
                "rating" => [
                    "rate" => 3.9,
                    "count" => 120
                ]
            ],
            [
                "id" => 2,
                "title" => "Mens Casual Premium Slim Fit T-Shirts",
                "price" => 22.3,
                "description" => "Slim-fitting style, contrast raglan long sleeve, three-button henley placket, light weight & soft fabric for breathable and comfortable wearing. And Solid stitched shirts with round neck made for durability and a great fit for casual fashion wear and diehard baseball fans. The Henley style round neckline includes a three-button placket.",
                "category" => "men's clothing",
                "image" => "https://fakestoreapi.com/img/71-3HjGNDUL._AC_SY879._SX._UX._SY._UY_t.png",
                "rating" => [
                    "rate" => 4.1,
                    "count" => 259
                ]
            ],
        ];
    }

    /** @test */
    public function it_imports_products_and_returns_them(): void
    {
        // Arrange — fake response, no real HTTP call
        Http::fake([
            'fakestoreapi.com/products' => Http::response(['success' => true, 'products' => $this->fakeProducts()], 200),
        ]);

        // Act
        $response = $this->actingAs($this->admin)->getJson('/fakestore/products');

        // Assert response structure
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('products.0.title', 'Fjallraven Backpack')
            ->assertJsonPath('products.1.title', 'Mens Casual Premium Slim Fit T-Shirts')
            ->assertJsonCount(2, 'products');

        // Assert correct URL + headers were sent
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'fakestoreapi.com/products')
                && $request->hasHeader('Accept', 'application/json')
                && $request->hasHeader('Content-Type', 'application/json');
        });
    }

    /** @test */
    public function it_returns_friendly_error_when_product_service_fails(): void
    {
        // Arrange — fake 500
        Http::fake([
            'fakestoreapi.com/products' => Http::response(['error' => true], 500),
        ]);

        // Act
        $response = $this->actingAs($this->admin)->getJson('/fakestore/products');

        // Assert — friendly JSON error, no stack trace
        $response->assertStatus(500)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Product service is currently unavailable.')
            ->assertJsonMissing(['exception']) 
            ->assertJsonMissing(['trace']);

        // Assert request still attempted correct URL
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'fakestoreapi.com/products');
        });
    }

    /** @test */
    public function it_verifies_api_key_header_sent(): void
    {
        Http::fake([
            'fakestoreapi.com/products' => Http::response($this->fakeProducts(), 200),
        ]);

        $this->actingAs($this->admin)->getJson('/fakestore/products');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Api-Key')  // key present (even if empty for FakeStore)
                && $request->hasHeader('Accept', 'application/json');
        });
    }
}