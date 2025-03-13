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

// Route matching
function matchRoute($requestUri, $requestMethod, $apiRoutes, $publicRoutes)
{
    // If request URI is completely empty
    if (empty($requestUri)) {
        response(NULL, 418, 'Welcome To The Desert Of The Real!');
    }

    // Check the valid HTTP method
    if (!isset($apiRoutes[$requestMethod])) {
        return null;
    }

    // Split URL into segments // for now, we only use 0 (baseRoute) and 1 (subRoute/id) parts
    $uriSegments = explode('/', $requestUri);
    $baseRoute = $uriSegments[0] ?? '';

    // If it's not a public route, run AuthMiddleware
    if (!in_array($uriSegments[1] ?? '', $publicRoutes[$requestMethod][$baseRoute])) {
        AuthMiddleware::handle();
    }

    // If the main route (e.g., users, images) is not found, return 404
    if (!isset($apiRoutes[$requestMethod][$baseRoute])) {
        return null;
    }

    // Get sub-routes
    $subRoutes = $apiRoutes[$requestMethod][$baseRoute];
    $subPath = $uriSegments[1] ?? null; // users/{id} -> {id}

    // If there is a direct match
    if ($subPath === null && isset($subRoutes[''])) {
        return [$subRoutes[''], []]; // A request like /users
    }

    // First, check for statically defined paths (e.g., users/campaign)
    if ($subPath !== null && isset($subRoutes[$subPath])) {
        if ($uriSegments[0] == 'access') {
            $vID = sanitizeId($uriSegments[2]); // Make it safe
            return [$subRoutes[$subPath], [$vID]]; // A request like /access/data/{vID} or /access/person/{vID}
        }
        return [$subRoutes[$subPath], []];
    }

    // If it's in the form of users/{id}, directly get the ID
    if ($subPath !== null && isset($subRoutes['{id}'])) {
        $cleanId = sanitizeId($subPath); // Make it safe
        return [$subRoutes['{id}'], [$cleanId]];
    }

    return null;
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
 * Sanitizes a string by trimming whitespace and optionally escaping HTML.
 *
 * @param string $string The string to sanitize.
 * @param bool $escapeHtml Whether to escape HTML characters.
 * @return string The sanitized string.
 * @example sanitizeString(' <script>alert("XSS")</script> '); // Returns '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'
 * @example sanitizeString(' Hello World ', false); // Returns 'Hello World'
 */
function sanitizeString($string, $escapeHtml = true)
{
    if (!is_string($string) || $string === '') {
        return '';
    }

    // Unicode uyumlu trim (Boşlukları ve görünmez karakterleri temizle)
    $string = preg_replace('/^\s+|\s+$/u', '', $string);

    // XSS koruması gerekiyorsa htmlspecialchars uygula
    return $escapeHtml ? htmlspecialchars($string, ENT_QUOTES, 'UTF-8') : $string;
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
function getPhpInputs(): array
{
    $inputs = [];
    $php_input = file_get_contents("php://input");
    $inputs = json_decode($php_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        parse_str($php_input, $inputs);
    }
    return $inputs;
}

/**
 * Checks if the given parameters exist in the provided array and are not empty.
 * Also sanitizes the parameters to prevent injection attacks.
 *
 * @param array $requiredParams The array of required parameter names.
 * @param array $data The array to check for the required parameters.
 * @return void Exits the script with a 400 status code if any required parameter is missing or empty.
 * @example checkRequiredParams(['name', 'email'], ['name' => 'Ahmet', 'email' => 'ahmet@example.com']); // No output, all parameters exist and are not empty
 * @example checkRequiredParams(['name', 'email'], ['name' => 'Ahmet']); // Exits the script with a 400 status code
 */
function checkRequiredParams(array $requiredParams, array $data)
{
    $missingParams = [];
    $emptyParams = [];

    foreach ($requiredParams as $param) {
        if (!array_key_exists($param, $data)) {
            $missingParams[] = $param;
        } elseif (is_string($data[$param]) && trim($data[$param]) === '') {
            $emptyParams[] = $param;
        }
    }

    // E-posta doğrulaması
    if (isset($data["email"]) && !validateEmail($data["email"])) {
        response(['email' => $data["email"]], 400, 'Invalid email format');
    }

    if (!empty($missingParams)) {
        response(['missing_params' => $missingParams], 400, 'Missing required parameters');
    }

    if (!empty($emptyParams)) {
        response(['empty_params' => $emptyParams], 400, 'Empty required parameters');
    }
}

/**
 * Validates an email address.
 *
 * @param string $email The email address to validate.
 * @return bool True if the email address is valid, false otherwise.
 * @example validateEmail('test@example.com'); // Returns true
 * @example validateEmail('invalid-email'); // Returns false
 */
function validateEmail($email): bool
{
    return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hashes a password using the SHA-256 algorithm.
 *
 * @param string $password The password to hash.
 * @return string The hashed password.
 * @example hashPassword('my_secure_password'); // Returns a SHA-256 hashed password
 */
function hashPassword($password)
{
    return hash('sha256', $password);
}

/**
 * Verifies a password against a SHA-256 hashed password.
 *
 * @param string $password The password to verify.
 * @param string $hashedPassword The hashed password to compare against.
 * @return bool True if the password matches the hashed password, false otherwise.
 * @example verifyPassword('my_secure_password', $hashedPassword); // Returns true or false
 */
function verifyPassword($password, $hashedPassword)
{
    return hash('sha256', $password) === $hashedPassword;
}

/**
 * Generates a unique VID for each record.
 *
 * @return string The generated VID.
 * @example generateVid(); // Returns a unique VID
 */
function generateVid()
{
    return bin2hex(random_bytes(5));
}

/**
 * Logs an error message to a log file and optionally to the terminal.
 *
 * @param string $type The type of log message.
 * @param string $message The error message to log.
 * @return void
 */
function createLog($type, $message)
{
    $logFile = __DIR__ . "/../Storage/Logs/app.log";
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ###$type: $message" . PHP_EOL;

    file_put_contents($logFile, $logMessage, FILE_APPEND);

    // Optional: Uncomment the following line to log to the terminal
    // error_log($logMessage);
}
