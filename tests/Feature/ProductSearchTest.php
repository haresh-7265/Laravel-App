<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function products_search_route_returns_200(): void
    {
        $response = $this->get('/products/search');

        $response->assertStatus(200);
    }

    /** @test */
    public function products_search_with_query_returns_200(): void
    {
        $response = $this->get('/products?q=test');

        $response->assertStatus(200);
    }
}
