<?php

return [
    'auth_guard' => 'web',
    'auth_provider' => 'users',

    'api_key' => env('SHOPIFY_API_KEY'),
    'shared_secret' => env('SHOPIFY_SHARED_SECRET'),
    'scopes' => env('SHOPIFY_SCOPES'),
];