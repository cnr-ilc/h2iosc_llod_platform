<?php

return [
    'footer' => [
        'text' => 'This digital product has been funded under the H2IOSC project (Humanities and cultural Heritage Italian Open Science Cloud funded by the European Union) – NextGenerationEU – NRRP M4C2 - Project code IR0000029 - CUP B63C22000730005.',
        'copyright' => '® Copyright © :year H2IOSC. All rights reserved.',
    ],
    'widgets' => [
        'user-resources-stats-overview' => [
            'import-resources' => 'Import your resources',
            'import-resources-description' => 'Retrieve resources deposited on the CLARIN Repository and import them here',
        ],
        'user-resources' => [
            'title' => 'Resources',
        ],
        'admin-resources' => [
            'title' => 'Latest resources submitted',
        ],
        'admin-resources-stats-overview' => [
            'manage-users-resources' => 'Manage user resources',
            'manage-users-resources-description' => 'Browse through user-submitted resources and provide feedback for publication requests',
        ],
    ],
    'resources' => [
        'actions' => [
            'import' => 'Import resource',
            'view' => 'View resource',
            'delete' => 'Delete resource',
        ],
        'status' => [
            'submission' => [
                'label' => 'Submission status',
            ],
            'submitted' => [
                'label' => 'Submitted',
            ],
        ],
    ],
    'pages' => [
        'import-resource' => [
            'title' => 'Import your resource',
            'subtitle' => 'Retrieve your resource',
            'results-found' => ':count result found',
            'no-results' => 'No result found',
            'searching' => 'Searching...',
            'form' => [
                'submit' => 'Show result',
                'reset' => 'Reset search query',
            ],
            'advanced-search' => [
                'title' => 'Advanced search',
                'identifier' => [
                    'label' => 'Search by item identifier',
                    'placeholder' => 'Search the CLARIN Repository',
                ],
                'filters' => [
                    'content' => [
                        'legend' => 'Filter by content',
                    ],
                    'type' => [
                        'legend' => 'Filter by type',
                    ],
                ],
            ],
        ],
    ],
];
