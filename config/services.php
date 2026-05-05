<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Service
    |--------------------------------------------------------------------------
    |
    | Base URL and bearer token for the centralised ExternalApiService.
    | Credentials are read from .env – never hardcode them in source.
    |
    */
    'external_api' => [
        'base_url' => env('EXTERNAL_API_BASE_URL', 'https://fakestoreapi.com'),
        'token' => env('EXTERNAL_API_TOKEN', ''),
        'timeout' => (int) env('EXTERNAL_API_TIMEOUT', 30),
        'retries' => (int) env('EXTERNAL_API_RETRY', 3),
        'retry_ms' => (int) env('EXTERNAL_API_RETRY_MS', 500),
    ],

    'fakestore' => [
        'base_url' => env('FAKESTORE_API_URL', 'https://fakestoreapi.com'),
        'key' => env('FAKESTORE_API_KEY', ''),
        'timeout' => env('FAKESTORE_API_TIMEOUT', 30),
    ],

];
