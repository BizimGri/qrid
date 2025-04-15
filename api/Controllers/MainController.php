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
            createLog("ERROR", "Failed to generate a unique VID for ({$type}) after 5 attempts.");
            response(NULL, 500, "Internal Server Error");
        }

        return $vID;
    }

    public function refreshCookie($payloadData, $cookieKey, $logout = false)
    {
        if (!empty($payloadData)) {
            $exp = time() + 86400; // Token will be valid for 24 hours
            $payload = [
                'id' => $payloadData['id'],
                "vID" => $payloadData['vID'],
                'name' => $payloadData['name'],
                'email' => $payloadData['email'],
                'exp' => $exp
            ];

            $jwt = JwtHandler::encode($payload);
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
}
