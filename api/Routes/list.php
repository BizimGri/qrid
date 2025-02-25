<?php

return [
    'GET' => [
        'users'  => [ // Static route and data for testing
            ''       => ['UserController', 'index'],  // GET /users
            '{id}'   => ['UserController', 'getById'],   // GET /users/{id}
        ]
    ],
    'POST' => [
        'person'  => [
            'register'  => ['PersonController', 'register'],    // POST /person/register
            'login'     => ['PersonController', 'login'],       // POST /person/login
        ]
    ],
    'PUT' => [
        
    ],
    'DELETE' => [
        
    ]
];