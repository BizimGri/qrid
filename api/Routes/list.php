<?php

return [

    'publicRoutes' => [
        'POST' => [
            'person' => [
                'login',
                'register',
                'forgot-password'
            ]
        ],
        'GET' => [
            'access' => [
                'data',
                'person'
            ]
        ]
    ],

    'apiRoutes' => [
        'GET' => [
            'data' => [
                'all'    => ['DataController', 'getAll'],           // GET /data
                '{id}'   => ['DataController', 'getByVID'],         // GET /data/{id}
            ],
            'access' => [
                'data'   => ['AccessController', 'getData'],         // GET /access/data/{vID}
                'person'   => ['AccessController', 'getPerson'],         // GET /access/person/{vID}
                'all'       => ['AccessController', 'getAll'],      // GET /access  
            ],
            'person' => [
                'logout'          => ['PersonController', 'logout'],      // GET /person/logout
                'profile'         => ['PersonController', 'profile'],     // GET /person/profile
            ]
        ],
        'POST' => [
            'person'  => [
                'register'        => ['PersonController', 'register'],    // POST /person/register
                'login'           => ['PersonController', 'login'],       // POST /person/login
                'forgot-password' => ['PersonController', 'forgotPassword'] // POST /person/forgot-password
            ],
            'data' => [
                ''          => ['DataController', 'create'],         // POST /data
            ],
            'subdata' => [
                ''          => ['SubDataController', 'create'],      // POST /sub-data
            ],
            'access' => [
                'request'   => ['AccessController', 'create'],       // POST /access/request
            ]
        ],
        'PUT' => [
            'data' => [
                '{id}'      => ['DataController', 'update'],        // PUT /data/{id}
            ],
            'subdata' => [
                '{id}'      => ['SubDataController', 'update'],     // PUT /subdata/{id}
            ],
            'access' => [
                'request'   => ['AccessController', 'approve'],      // PUT /access/{id}
            ]
        ],
        'DELETE' => []
    ]
];
