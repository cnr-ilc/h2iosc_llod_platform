<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Python Quality Check Service Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Python service
    | that performs health and quality checks on resource files.
    |
    */

    'url' => env('PY_QUALITY_CHECK_SERVICE_URL', 'http://localhost:8000'),

    'endpoints' => [
        'health' => '/health',
        'quality' => '/quality',
    ],

];
