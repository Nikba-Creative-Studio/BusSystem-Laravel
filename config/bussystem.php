<?php

return [
    'login' => env('BUS_API_LOGIN', ''),
    'password' => env('BUS_API_PASSWORD', ''),
    'test_mode' => env('BUS_API_TEST_MODE', true),
    'lang' => env('BUS_API_LANG', 'en'),
    'cache_times' => [
        'get_points' => 525600, // One year in minutes
        'get_routes' => 1440,   // One day in minutes
    ],
    'endpoints' => [
        'test' => 'https://test-api.bussystem.eu/server',
        'production' => 'https://api.bussystem.eu/server',
    ],
];
