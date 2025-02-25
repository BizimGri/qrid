<?php

/**
 * Sends a JSON response with data, status code, and message.
 *
 * @param array $data The data to include in the response.
 * @param int $status The HTTP status code for the response.
 * @param string $message The message to include in the response.
 * @return void Exits the script after sending the response.
 * @example response(['name' => 'Ahmet KARACA'], 200, 'OK');
 * @example response([], 404, 'Not Found');
 */
function response($data = [], $status = 200, $message = 'OK')
{
    http_response_code($status);
    header('Content-type: application/json; charset=utf-8');
    $response = [
        'status' => $status,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

/**
 * Sanitizes an ID to ensure it only contains valid characters.
 *
 * @param string $id The ID to sanitize.
 * @return string The sanitized ID.
 * @return void Exits the script if the ID is invalid.
 * @example sanitizeId('123-abc'); // Returns '123-abc'
 * @example sanitizeId('!@#'); // Exits the script with a 400 status code
 */
function sanitizeId($id)
{
    // Ensure it only contains letters, numbers, hyphens (-), and underscores (_)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
        response([], 400, 'Invalid ID format');
    }
    return $id;
}


/**
 * Retrieves parameters from GET, POST, PUT, and DELETE requests.
 *
 * @return array The array of parameters based on the request method.
 * @example getRequestParams(); // Returns an array of parameters
 */
function getRequestParams()
{
    $params = [];
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $params = $_GET;
            break;
        case 'POST':
            $params = !empty($_POST) ? $_POST : getPhpInputs();
            break;
        case 'PUT':
        case 'DELETE':
            $params = getPhpInputs();
            break;
        default:
            response([], 405, 'Method Not Allowed');
    }

    return $params;
}

/**
 * Retrieves raw parameters from the request body.
 *
 * @return array The array of raw parameters.
 * @example getPhpInputs(); // Returns an array of raw parameters
 */
function getPhpInputs() : array {
    $inputs = [];
    $php_input = file_get_contents("php://input");
    $inputs = json_decode($php_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        parse_str($php_input, $inputs);
    }
    return $inputs;
}