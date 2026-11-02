<?php

return [
    // geocoding rate limits are defined in config/geocoding

    // Mill Map & List
    // geocoding may get wrapped up in this one since it's technically an API route
    'api' => [
        'per_minute' => env('RATE_LIMIT_API_PER_MINUTE', 10),
        'per_day' => env('RATE_LIMIT_API_PER_DAY', 1000),
    ],

    // contact form
    'contact' => [
        'per_minute' => env('RATE_LIMIT_CONTACT_PER_MINUTE', 1),
        'per_day' => env('RATE_LIMIT_CONTACT_PER_DAY', 10),
    ],

    // export mills
    'export' => [
        'per_minute' => env('RATE_LIMIT_EXPORT_PER_MINUTE', 1),
        'per_day' => env('RATE_LIMIT_EXPORT_PER_DAY', 20),
    ],

    // add/edit mills
    'mills' => [
        'per_minute' => env('RATE_LIMIT_MILLS_PER_MINUTE', 1),
        'per_day' => env('RATE_LIMIT_MILLS_PER_DAY', 10),
    ],
];
