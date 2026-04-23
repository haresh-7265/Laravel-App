<?php

return [
    'name'          => env('ADMIN_NAME', 'Admin Panel'),
    'email'         => env('ADMIN_EMAIL', 'admin@example.com'),
    'currency_code'      => env('ADMIN_CURRENCY_CODE', 'USD'),
    'allow_delete'  => env('ADMIN_ALLOW_DELETE', true),
    'freeShippingThreshold' => 399
];