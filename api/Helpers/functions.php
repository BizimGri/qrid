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