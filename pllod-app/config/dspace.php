<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DSpace API Configuration
    |--------------------------------------------------------------------------
    */

    'base_url' => env('DSPACE_API_URL', 'https://dspace-clarin-it.ilc.cnr.it/repository/rest'),

    'auth' => [
        'email' => env('DSPACE_EMAIL'),
        'password' => env('DSPACE_PASSWORD'),
    ],
];
