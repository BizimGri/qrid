<?php require_once __DIR__ . '/../Helpers/JwtHandler.php';

class MainController
{
    protected $model;
    protected $params;

    public function __construct($model)
    {
        $this->model = $model;
        $this->params = getRequestParams();
    }

    public function generateUniqueVid($type = "vID")
    {
        $vIDCount = 0;
        do {
            $vID = generateVid();
            if ($vIDCount > 0) createLog("WARNING", "VID ({$type}) collision detected: $vID # vIDCount: $vIDCount");
        } while ($this->model->exists(["vID" => $vID]) && $vIDCount++ < 5);

        if ($vIDCount >= 5) {
            response(NULL, 500, "Internal Server Error", true);
            createLog("ERROR", "Failed to generate a unique VID for ({$type}) after 5 attempts.");
        }

        return $vID;
    }

    public function refreshCookie($payloadData, $cookieKey, $logout = false, $expireTime = 86400)
    {
        if (!empty($payloadData)) {
            $exp = time() + $expireTime; // Token will be valid for 24 hours

            $payload = [
                'id' => $payloadData['id'],
                "vID" => $payloadData['vID'],
                'name' => $payloadData['name'],
                'email' => $payloadData['email'],
                'exp' => $exp
            ];

            $jwt = JwtHandler::encode($payload, $expireTime);
        }

        $cookieData = [
            "path" => "/",
            "httponly" => true,
            "secure" => true,
            "samesite" => "None"
        ];

        if ($logout) {
            $cookieData["expires"] = time() - 3600;  // Past time
            $jwt = "";
        } else {
            $cookieData["expires"] = $exp;
        }

        return setcookie($cookieKey, $jwt, $cookieData);
    }

    function generateFirebaseCustomToken($uid)
    {
        $serviceAccount = json_decode(file_get_contents(__DIR__ . "/../webrtc-for-qrid-firebase-adminsdk-fbsvc-a7cd1a6e42.json"), true);

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'aud' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
            'iat' => time(),
            'exp' => time() + 3600,
            'uid' => (string)$uid // Burada string zorluyoruz
        ];

        $base64url_encode = function ($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        $sign = function ($input, $privateKeyPem) {
            openssl_sign($input, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
            return $signature;
        };

        $headerEncoded = $base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signatureInput = $headerEncoded . '.' . $payloadEncoded;

        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);

        if (!$privateKey) {
            throw new Exception("Private key yüklenemedi.");
        }

        $signature = $sign($signatureInput, $privateKey);
        $signatureEncoded = $base64url_encode($signature);

        return $signatureInput . '.' . $signatureEncoded;
    }
}
