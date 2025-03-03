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
                'request', // qrid.space/api/access/request/?vID=1eryew4wre GET isteği içinden parametrelerde vID yakalanır...
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
                'request'   => ['AccessController', 'get'],             // GET /access/data/{vID}
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
