<?php

return [

    'publicRoutes' => [
        'POST' => [
            'person' => [
                'login',
                'emailLogin',
                'register',
                'forgot-password'
            ]
        ],
        'GET' => [
            'access' => [
                'data',
                'person'
            ],
            'metric' => [
                'getAll'
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
                'person'   => ['AccessController', 'getPerson'],     // GET /access/person/{vID}
                'all'       => ['AccessController', 'getAll'],      // GET /access  
            ],
            'person' => [
                'logout'            => ['PersonController', 'logout'],           // GET /person/logout
                'profile'           => ['PersonController', 'profile'],          // GET /person/profile
                'profile-details'   => ['PersonController', 'profileDetails'],   // GET /person/profile
                'fcm-token'         => ['PersonController', 'getFCMToken'],      // GET /person/profile
                'notification-test' => ['PersonController', 'notificationTest'], // GET /person/profile
            ],
            'metric' => [
                'getAll'          => ['MetricController', 'getAll']
            ],
            'chat' => [
                '{id}'            => ['ChatController', 'getRoom'],
                'recents'         => ['ChatController', 'getLastChats']
            ]
        ],
        'POST' => [
            'person'  => [
                'register'        => ['PersonController', 'register'],    // POST /person/register
                'login'           => ['PersonController', 'login'],       // POST /person/login
                'emailLogin'      => ['PersonController', 'loginWithEmail'],
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
            ],
            'feedback' => [
                ''          => ['FeedbackController', 'create'],
            ],
            'chat' => [
                'create'       => ['ChatController', 'create'],
                'notify'       => ['ChatController', 'notifyOther']
            ]
        ],
        'PUT' => [
            'person' => [
                'profile'   => ['PersonController', 'update'],
                'fcm-token' => ['PersonController', 'updateFCMToken']
            ],
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
        'DELETE' => [
            'subdata' => [
                ''      => ['SubDataController', 'delete'],     // DELETE /subdata?dataID={dataID}&subDataID={subDataID}
            ],
            'data' => [
                '{id}'      => ['DataController', 'delete'],        // DELETE /data/{id}
            ],
        ]
    ]
];
