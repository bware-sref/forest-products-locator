<?php

return [
    // geocoding rate limits are defined in config/geocoding

    // contact form
    'contact' => [
        'per_minute' => 1,
        'per_day' => 10,
    ],
    // add/edit mills
    'mills' => [
        'per_minute' => 1,
        'per_day' => 10,
    ],
    // Mill Map & List
    // geocoding may get wrapped up in this one since it's technically an API route
    'api' => [
        'per_minute' => 10,
        'per_day' => 1000,
    ],
];
