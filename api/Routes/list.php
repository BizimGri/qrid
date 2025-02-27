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
            'users'  => [                                           // Static route and data for testing
                ''       => ['UserController', 'index'],            // GET /users
                '{id}'   => ['UserController', 'getById'],          // GET /users/{id}
            ],
            'data' => [
                ''       => ['DataController', 'index'],            // GET /data
                '{id}'   => ['DataController', 'getById'],          // GET /data/{id}
            ]
        ],
        'POST' => [
            'person'  => [
                'register'  => ['PersonController', 'register'],    // POST /person/register
                'login'     => ['PersonController', 'login'],       // POST /person/login
            ],
            'data' => [
                ''          => ['DataController', 'store'],         // POST /data
            ]
        ],
        'PUT' => [],
        'DELETE' => []
    ]
];
