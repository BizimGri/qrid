<?php

return [

    'publicRoutes' => [
        'POST' => [
            'person' => [
                'login',
                'register',
                'forgot-password'
            ]
        ]
    ],

    'apiRoutes' => [
        'GET' => [
            'data' => [
                'all'    => ['DataController', 'getAll'],           // GET /data
                '{id}'   => ['DataController', 'getByVID'],         // GET /data/{id}
            ]
        ],
        'POST' => [
            'person'  => [
                'register'  => ['PersonController', 'register'],    // POST /person/register
                'login'     => ['PersonController', 'login'],       // POST /person/login
            ],
            'data' => [
                ''          => ['DataController', 'store'],         // POST /data
            ],
            'subdata' => [
                ''          => ['SubDataController', 'store'],      // POST /sub-data
            ]
        ],
        'PUT' => [
            'data' => [
                '{id}'      => ['DataController', 'update'],        // PUT /data/{id}
            ],
            'subdata' => [
                '{id}'      => ['SubDataController', 'update'],     // PUT /subdata/{id}
            ]
        ],
        'DELETE' => []
    ]
];
