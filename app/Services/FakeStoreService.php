<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FakeStoreService
{
    protected function client()
    {
        // Uses the jsonApi() macro registered in AppServiceProvider
        return Http::jsonApi(
            baseUrl: config('services.fakestore.base_url'),
            apiKey: config('services.fakestore.key'),
            timeout: config('services.fakestore.timeout'),
        );
    }

    public function fetchProducts(): array
    {
        $response = $this->client()->get("/products");

        if (!$response->successful()) {
            Log::error('FakeStore fetch failed', ['status' => $response->status()]);
            throw new \App\Exceptions\ExternalApiException(
                message: 'Product service is currently unavailable.',
                context: 'GET /products',
                apiStatusCode: $response->status(),
            );
        }

        Log::error('FakeStore fetch failed', ['status' => $response->status()]);
        return $response->json();
    }

    public function postProduct(): void
    {
        $response = $this->client()->post("/products", [
            'title' => 'Test Product',
            'price' => 29.99,
            'description' => 'A sample product',
            'category' => 'electronics',
            'image' => 'https://fakestoreapi.com/img/test.jpg',
        ]);

        Log::info('FakeStore POST response', [
            'status' => $response->status(),
            'ok' => $response->ok(),
            'body' => $response->json(),
        ]);
    }
}