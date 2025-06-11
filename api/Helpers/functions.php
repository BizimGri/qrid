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
function response($data = [], $status = 200, $message = 'OK', $continueProcessing = false)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header("Connection: close");

    $response = [
        'status' => $status,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    $json = json_encode($response);
    echo $json;

    // Uzun işlemler devam edecekse, istemci bağlantısını sonlandır
    if ($continueProcessing) {
        // Buffer'ı temizle ve gönder
        ob_start();
        $length = strlen($json);
        header("Content-Length: $length");
        ob_end_flush();
        flush();

        // PHP-FPM ile çalışıyorsan hızlıca işlemi bitir
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Kullanıcı bağlantıyı koparsa bile işlem devam etsin
        ignore_user_abort(true);
    } else {
        exit;
    }
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
    if (
        empty($publicRoutes[$requestMethod][$baseRoute])
        || !in_array($uriSegments[1] ?? '', $publicRoutes[$requestMethod][$baseRoute])
    ) {
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
        if ($uriSegments[0] == 'access' && ($uriSegments[1] == 'data' || $uriSegments[1] == 'person')) {
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
            $params = getPhpInputs();
            break;
        case 'DELETE':
            $params = $_GET;
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
function generateVid($length = 10)
{
    return bin2hex(random_bytes($length / 2));
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

function getMetric()
{
    $metricFile = __DIR__ . '/../Storage/Metrics/metrics.json';
    $metricDuration = 60; // saniye

    if (file_exists($metricFile) && (time() - filemtime($metricFile)) < $metricDuration) {
        return json_decode(file_get_contents($metricFile));
    } else return false;
}

function createMetric($data)
{
    if (!is_dir(__DIR__ . '/../Storage/Metrics')) {
        mkdir(__DIR__ . '/../Storage/Metrics', 0755, true);
    }

    $metricFile = __DIR__ . '/../Storage/Metrics/metrics.json';

    file_put_contents($metricFile, json_encode($data));
    $data["new"] = true;
    return $data;
}

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getAccessToken()
{
    $fcmGoogleFile = __DIR__ . '/../fcm-for-qrid-firebase-adminsdk-fbsvc-1c831d5592.json';
    $credentials = json_decode(file_get_contents($fcmGoogleFile), true);
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $now = time();
    $payload = [
        'iss' => $credentials['client_email'],
        'sub' => $credentials['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ];

    $base64UrlHeader = base64url_encode(json_encode($header));
    $base64UrlPayload = base64url_encode(json_encode($payload));
    $signatureInput = $base64UrlHeader . '.' . $base64UrlPayload;

    openssl_sign($signatureInput, $signature, $credentials['private_key'], 'SHA256');
    $base64UrlSignature = base64url_encode($signature);

    $jwt = $signatureInput . '.' . $base64UrlSignature;

    $response = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ])
        ]
    ]));

    $responseData = json_decode($response, true);
    return $responseData['access_token'];
}

function sendNotification($deviceToken, $title, $body, $path = "/", $data = [])
{
    $accessToken = getAccessToken();
    $url = "https://fcm.googleapis.com/v1/projects/fcm-for-qrid/messages:send";
    $data['click_action'] = "https://qrid.space" . $path;
    $data['title'] = $title;
    $data['body'] = $body;
    $path .= "?notification-data=" . base64_encode(json_encode($data));

    $message = [
        'message' => [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'image' => "https://qrid.space/qr-code.ico"
            ],
            'data' => [
                'title' => $title,
                'body' => $body,
                'click_action' => "https://qrid.space" . $path,
                'data' => base64_encode(json_encode($data))
            ],
            'webpush' => [
                'fcm_options' => [
                    'link' => "https://qrid.space" . $path
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // echo "HTTP CODE: $httpCode\n";
    // echo "CURL ERROR: $error\n";
    // echo "Yanıt: $response";

    // $response = file_get_contents($url, false, stream_context_create([
    //     'http' => [
    //         'method' => 'POST',
    //         'header' => "Authorization: Bearer $accessToken\r\n" .
    //             "Content-Type: application/json\r\n",
    //         'content' => json_encode($message)
    //     ]
    // ]));

    // if ($response === false) {
    //     $error = error_get_last();
    //     die(var_dump($error));
    // }
    // return ["response" => $response];

    return ["response" => $response, "httpCode" => $httpCode, "error" => $error];
}

function sendMail($email, $subject, $body, $altBody = "", $type = "")
{
    require_once __DIR__ . "/mailer.php";
    try {
        $mail = createMailer();
        $mail->addAddress($email);
        $mail->Subject = $subject;

        // Enable HTML
        $mail->isHTML(true);
        $mail->Body = $body;

        $mail->AltBody = $altBody;

        $mail->send();
    } catch (Exception $e) {
        response(NULL, 500, "", true);
        error_log("Failed to send email: (" . $type . ") #" . strtotime('now') . " -> " . $e->getMessage());
    }
}

function notifyPerson(){
    
}