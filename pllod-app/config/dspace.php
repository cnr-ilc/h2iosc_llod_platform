<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DSpace Server Configuration
    |--------------------------------------------------------------------------
    |
    | Base URL of the DSpace REST API server
    |
    */
    'base_url' => env('DSPACE_BASE_URL', 'https://dspace-clarin-it.ilc.cnr.it/server'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Credentials
    |--------------------------------------------------------------------------
    |
    | Username and password for DSpace authentication
    |
    */
    'username' => env('DSPACE_USERNAME'),
    'password' => env('DSPACE_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for JWT token caching in Redis
    | - prefix: Cache key prefix
    | - ttl: Token cache time in minutes (should be less than JWT expiry of 30 min)
    |
    */
    'cache' => [
        'prefix' => 'dspace_',
        'ttl' => 25,
    ],
];
