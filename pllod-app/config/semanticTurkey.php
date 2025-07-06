<?php

return [

    'base_url' => env('SEMANTIC_TURKEY_BASE_URL', 'http://localhost:1979'),
    'admin_email' => env('SEMANTIC_TURKEY_ADMIN_EMAIL', 'admin@pllod.test'),
    'admin_password' => env('SEMANTIC_TURKEY_ADMIN_PASSWORD', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | ST Project Creation Default Configuration
    |--------------------------------------------------------------------------
    |
    | This values defines the default conf for creating a project on Semantic Turkey
    | with a GraphDB integrated.
    |
    */
    'default_project_config' => [
        'base' => [
            'consumer' => 'SYSTEM',
            'historyEnabled' => 'false',
            'validationEnabled' => 'false',
            'blacklistingEnabled' => 'false',
            'undoEnabled' => 'false',
            'shaclEnabled' => 'false',
            'trivialInferenceEnabled' => 'false',
            'transitiveImportAllowance' => 'web',
            'openAtStartup' => false,
            'coreBackendType' => 'openrdf:NativeStore',
            'repositoryAccess' => json_encode([
                '@type' => 'CreateRemote',
                'serverURL' => env('GRAPHDB_HOST_URL'),
            ]),
        ],
        'lexicalizationModel' => '<http://www.w3.org/2000/01/rdf-schema>',
        'model' => '<http://www.w3.org/2002/07/owl>',
        'dataFormat' => 'RDF/XML',

        /*
        |--------------------------------------------------------------------------
        | ST Project User Roles
        |--------------------------------------------------------------------------
        |
        | The roles of a user that can have in a ST project
        |
        */
        'all_user_project_roles' => [
            'lexicographer',
            'lurker',
            'mapper',
            'ontologist',
            'projectmanager',
            'rdfgeek',
            'thesaurus-editor',
            'validator',
        ],
    ],
];
