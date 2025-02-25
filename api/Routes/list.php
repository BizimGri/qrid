<?php

return [
    'GET' => [
        'users'  => [
            ''       => ['UserController', 'index'],  // GET /users
            '{id}'   => ['UserController', 'getById'],   // GET /users/{id}
        ],
        'images' => [
            ''       => ['ImageController', 'index'],  // GET /images
            '{id}'   => ['ImageController', 'getById'],   // GET /images/{id}
        ],
    ],
    'POST' => [
        'users'  => [
            ''       => ['UserController', 'store'],  // POST /users
        ],
        'images' => [
            ''       => ['ImageController', 'store'],  // POST /images
        ],
    ],
    'PUT' => [
        'users'  => [
            '{id}'   => ['UserController', 'update'],  // PUT /users/{id}
        ],
        'images' => [
            '{id}'   => ['ImageController', 'update'],  // PUT /images/{id}
        ],
    ],
    'DELETE' => [
        'users'  => [
            '{id}'   => ['UserController', 'destroy'],  // DELETE /users/{id}
        ],
        'images' => [
            '{id}'   => ['ImageController', 'destroy'],  // DELETE /images/{id}
        ],
    ]
];