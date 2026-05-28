<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used; however, you remain free to modify this option if you wish.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => env('HASH_DRIVER', 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Bcrypt algorithm. This will allow you
    | to control the amount of time it takes to hash the given password.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
        'limit' => env('BCRYPT_LIMIT', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Argon algorithm. These will allow you
    | to control the amount of time it takes to hash the given password.
    |
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2id Options  ← PRODUCTION DRIVER
    |--------------------------------------------------------------------------
    |
    | memory  (KB): 65536 = 64MB. OWASP min. Raise to 131072 (128MB) if
    |               hardware allows sub-500ms at that level.
    |
    | time    (iter): 4 iterations. Do not go below 3.
    |                 More iterations + lower memory < less iterations + more memory.
    |                 Prefer raising memory over iterations.
    |
    | threads (lanes): 2. Set to number of available vCPUs on prod server.
    |                  Does not speed up hashing; sets attacker parallelism ceiling.
    |
    | verify: reject hashes not made by argon2id when using this driver.
    |
    |
    */
 
    'argon2id' => [
        'memory'  => env('ARGON2ID_MEMORY', 65536),
        'time'    => env('ARGON2ID_TIME', 4),
        'threads' => env('ARGON2ID_THREADS', 2),
        'verify'  => env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Path: Bcrypt to Argon2id
    |--------------------------------------------------------------------------
    |
    | To upgrade existing user accounts from Bcrypt to Argon2id without forcing
    | a password reset:
    |
    | 1. On successful login, the application verifies the password using
    |    `Hash::check()`. Bcrypt hashes will be successfully verified since
    |    PHP's password_verify() automatically reads the hash prefix.
    | 2. Immediately after successful login, the application checks if the
    |    hash needs to be upgraded using `Hash::needsRehash($user->password)`.
    | 3. If `needsRehash` returns true (which it will for all legacy Bcrypt
    |    hashes or outdated Argon2id settings), the application transparently
    |    re-hashes the plain password using the current driver and parameters,
    |    and updates the database record.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | Setting this option to true will tell Laravel to automatically rehash
    | the user's password during login if the configured work factor for
    | the algorithm has changed, allowing graceful upgrades of hashes.
    |
    */

    'rehash_on_login' => true,

];
