<?php

namespace App\Http\Controllers;

use App\Services\ExternalApiService;
use Illuminate\Http\JsonResponse;

class GithubController extends Controller
{
    public function __construct(
        protected ExternalApiService $api
    ) {
    }

    // GET /github/profile
    public function profile(): JsonResponse
    {
        $response = $this->api->get('/user');

        return response()->json(
            $response
        );
    }

    // GET /github/repos
    public function repos(): JsonResponse
    {
        $response = $this->api->get('/user/repos', ['sort' => 'updated', 'per_page' => 10]);

        return response()->json(
            $response
        );
    }

    // GET /github/user/{username}
    public function user(string $username): JsonResponse
    {
        $response = $this->api->get("/users/{$username}");
        return response()->json(
            $response
        );
    }

    // Test endpoint — intentionally bad URL
    public function broken(): JsonResponse
    {
        return response()->json(
            $this->api->get('/this-endpoint-does-not-exist-404')
        );
    }
}