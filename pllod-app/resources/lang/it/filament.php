<?php

return [
    'footer' => [
        'text' => 'Questo prodotto digitale è stato finanziato nell\'ambito del progetto H2IOSC (Humanities and cultural Heritage Italian Open Science Cloud funded by the European Union) – NextGenerationEU – NRRP M4C2 - Project code IR0000029 - CUP B63C22000730005.',
        'copyright' => '® Copyright © 2024 H2IOSC. Tutti i diritti riservati.',
    ],
    'widgets' => [
        'user-resources-stats-overview' => [
            'import-resources' => 'Importa le tue risorse',
            'import-resources-description' => 'Recupera le tue risorse depositate nel CLARIN Repository e importale qui',
        ],
        'user-resources' => [
            'title' => 'Risorse',
        ],
        'admin-resources' => [
            'title' => 'Ultime risorse inviate',
        ],
        'admin-resources-stats-overview' => [
            'manage-users-resources' => 'Gestisci le risorse degli utenti',
            'manage-users-resources-description' => 'Raggiungi le risorse inviate dagli utenti e fornisci feedback per le richieste di pubblicazione',
        ],
    ],
    'resources' => [
        'actions' => [
            'import' => 'Importa risorsa',
            'view' => 'Visualizza risorsa',
            'delete' => 'Elimina risorsa',
        ],
        'status' => [
            'submission' => [
                'label' => 'Stato di invio',
            ],
            'submitted' => [
                'label' => 'Inviato',
            ],
        ],
    ],
    'pages' => [
        'import-resource' => [
            'title' => 'Importa le tue risorse',
            'subtitle' => 'Recupera le tue risorse',
            'results-found' => ':count risultati trovati',
            'no-results' => 'Nessun risultato trovato',
            'searching' => 'Cerca...',
            'form' => [
                'submit' => 'Mostra i risultati',
                'reset' => 'Reimposta la ricerca',
            ],
            'advanced-search' => [
                'title' => 'Ricerca avanzata',
                'identifier' => [
                    'label' => 'Cerca per identificatore',
                    'placeholder' => 'Cerca nel CLARIN Repository',
                ],
                'filters' => [
                    'content' => [
                        'legend' => 'Filtra per contenuto',
                    ],
                    'type' => [
                        'legend' => 'Filtra per tipo',
                    ],
                ],
            ],
        ],
    ],
];
