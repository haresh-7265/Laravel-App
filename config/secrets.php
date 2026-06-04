<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active Driver
    |--------------------------------------------------------------------------
    | Supported: "env", "aws", "vault", "doppler"
    */
    'driver' => env('SECRETS_DRIVER', 'env'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    | Cache secrets at boot to avoid hitting the vault on every request.
    | Set ttl to 0 to disable caching.
    */
    'cache' => [
        'enabled' => env('SECRETS_CACHE_ENABLED', true),
        'ttl'     => env('SECRETS_CACHE_TTL', 3600), // seconds
        'key'     => 'secrets_provider_cache',
    ],

    'aws' => [
        'region'      => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'secret_name' => env('AWS_SECRET_NAME'),
        'key'         => env('AWS_ACCESS_KEY_ID'),
        'secret'      => env('AWS_SECRET_ACCESS_KEY'),
    ],

    'vault' => [
        'addr'       => env('VAULT_ADDR'),        // https://vault.example.com
        'token'      => env('VAULT_TOKEN'),        // or use approle below
        'path'       => env('VAULT_SECRET_PATH'),  // secret/data/myapp
        'approle'    => [
            'enabled'   => env('VAULT_APPROLE_ENABLED', false),
            'role_id'   => env('VAULT_ROLE_ID'),
            'secret_id' => env('VAULT_SECRET_ID'),
        ],
    ],

    'doppler' => [
        'token'   => env('DOPPLER_TOKEN'),
        'project' => env('DOPPLER_PROJECT'),
        'config'  => env('DOPPLER_CONFIG', 'production'),
    ],

];