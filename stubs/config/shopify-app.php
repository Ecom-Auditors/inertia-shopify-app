<?php

return [
    'auth_guard' => 'web',
    'auth_provider' => 'users',

    'api_key' => env('SHOPIFY_API_KEY'),
    'shared_secret' => env('SHOPIFY_SHARED_SECRET'),
    'scopes' => env('SHOPIFY_SCOPES'),

    'webhooks' => [
        // topic => Job class,
        // 'orders/create' => Job::class,
    ],

    'billing' => [
        'enabled' => false,

        // 'name' => '',
        // 'trial_days' => 0,
        'plans' => [[
            // 'price' => 10,
            // 'currency' => 'USD',
            // 'interval' => null, // ANNUAL, EVERY_30_DAYS, NULL (for usage billing)

            // If you want to charge a usage billing, the following is required:
            // 'capped_amount' => 100,
            // 'terms' => '',
        ]],
    ],
];