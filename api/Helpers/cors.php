<?php

/* Handle CORS */

// Specify domains from which requests are allowed
header('Access-Control-Allow-Origin: https://qrid.space');

// Specify which request methods are allowed
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Additional headers which may be sent along with the CORS request
header('Access-Control-Allow-Headers: X-Requested-With,Authorization,Content-Type');

// Set the age to 1 hour to improve speed/caching.
header('Access-Control-Max-Age: 3600');

// Exit early so the page isn't fully loaded for options requests
if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
    exit();
}
