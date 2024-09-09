<?php

return [
    'login' => env('BUS_API_LOGIN', ''),
    'password' => env('BUS_API_PASSWORD', ''),
    'test_mode' => env('BUS_API_TEST_MODE', true),
    'lang' => env('BUS_API_LANG', 'en'),
    'cache_times' => [
        'get_points' => 525600, // One year in minutes
        'get_routes' => 1440,   // One day in minutes
        'get_all_routes' => 1440, // One day in minutes
        'get_baggage' => 1440,  // One day in minutes
        'get_free_seats' => 60,  // One Hour in minutes
        'get_plan' => 60,  // One Hour in minutes
        'new_order' => 0,  // No cache
        'reserve_ticket' => 0,  // No cache
        'reserve_validation' => 0,  // No cache
        'sms_validation' => 0,  // No cache
        'get_order' => 30,  // No cache
        'get_ticket' => 30,  // No cache
        'buy_ticket' => 0,  // No cache
        'reg_ticket' => 0,  // No cache
        'cancel_ticket' => 0,  // No cache
        'get_cash' => 0,  // No cache
        'get_orders' => 0,  // No cache
        'get_tickets' => 0,  // No cache
        'get_dispatcher_tickets' => 0,  // No cache
    ],
    'endpoints' => [
        'test' => 'https://test-api.bussystem.eu/server',
        'production' => 'https://api.bussystem.eu/server',
    ],
];
