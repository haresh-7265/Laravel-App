<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FakeStoreService
{
    protected string $baseUrl = 'https://fakestoreapi.com';

    public function fetchProducts(): array
    {
        $response = Http::get("{$this->baseUrl}/products");

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('FakeStore fetch failed', ['status' => $response->status()]);
        return [];
    }

    public function postProduct(): void
    {
        $response = Http::post("{$this->baseUrl}/products", [
            'title'       => 'Test Product',
            'price'       => 29.99,
            'description' => 'A sample product',
            'category'    => 'electronics',
            'image'       => 'https://fakestoreapi.com/img/test.jpg',
        ]);

        Log::info('FakeStore POST response', [
            'status' => $response->status(),
            'ok'     => $response->ok(),
            'body'   => $response->json(),
        ]);
    }
}