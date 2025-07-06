<?php

return [
    'host' => [
        'host_url' => env('GRAPHDB_HOST_URL', 'https://graphdb.pllod.netseven.work'),
        'auth' => [
            'username' => env('GRAPHDB_USER', 'admin'),
            'password' => env('GRAPHDB_PSW', 'root'),
        ],
    ],
    'active_repository' => env('GRAPHDB_ACTIVE_REPOSITORY', 'default'),
];
