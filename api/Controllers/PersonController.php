<?php

require_once __DIR__ . '/../Models/PersonModel.php';
require_once __DIR__ . '/../Helpers/mailer.php';


class PersonController extends MainController
{
    public function __construct()
    {
        parent::__construct(new PersonModel());
    }

    function register($params = null)
    {
        checkRequiredParams(['name', 'email', 'password'], $params ?? $this->params);

        if ($this->model->exists(["email" => $this->params['email']])) {
            response(NULL, 409, "Email already exists.");
        }

        $vID = $this->generateUniqueVid("person");

        $person = [
            "vID" => $vID,
            "name" => $params["name"] ?? $this->params['name'],
            "email" => $params["email"] ?? $this->params['email'],
            "password" => hashPassword($params["params"] ?? $this->params['password']),
            "encryptionKey" => bin2hex(random_bytes(32))
        ];
        if (!empty($params["emailCode"])) $person["emailCode"] = $params["emailCode"];

        $result = $this->model->create($person, false);
        if (empty($params)) {
            if ($result) response([], 201, "Person created.");
            else response(NULL, 500, "Internal Server Error");
        } else return $person;
    }

    function login()
    {
        checkRequiredParams(['email', 'password'], $this->params);

        if (!empty($this->params["details"])) {
            $social = $this->params["details"]["social"] ?? false;
        }

        $user_check = !$this->model->exists(["email" => $this->params['email']]);

        if (!$social && $user_check) {
            response(NULL, 404, "Person not found.");
        }

        $person = $this->model->getWhere(["email" => $this->params['email']], "id, vID, name, email, password");

        if (empty($person) && $social) {
            $result = $this->register([
                "name" => $this->params["details"]["name"],
                "email" => $this->params["email"],
                "password" => $this->params["password"]
            ]);

            if ($result) {
                $person = $this->model->getWhere(["email" => $this->params['email']], "id, vID, name, email");
                if ($person) {
                    $person[0]["first_login"] = true;
                }
            } else response(500);
        } else {
            if (!$social && !verifyPassword($this->params['password'], $person[0]['password'])) {
                response(NULL, 401, "Invalid login credentials.");
            }
        }

        if ($social) $person[0]["social_login"] = true;
        unset($person[0]["password"]);
        $cookieStatus = $this->refreshCookie($person[0], "jwt_token");

        if ($cookieStatus) {
            response($person[0], 200, "Login successful.", true);
            if ($person[0]["first_login"] == true) $this->firstLoginMail($this->params["email"], $this->params["password"]);
        } else response(NULL, 500, "Internal Server Error");
    }

    function loginWithEmail()
    {
        if (!empty($this->params["login-with-email"])) {
            checkRequiredParams(["login-with-email", "email", "password", "name"], $this->params);

            $user_check = !$this->model->exists(["email" => $this->params['email']]);

            $code = random_int(100000, 999999);
            if ($user_check) {
                $result = $this->register([
                    "name" => $this->params["name"],
                    "email" => $this->params["email"],
                    "password" => $this->params["password"],
                    "emailCode" => $code
                ]);

                if ($result) {
                    $person = $this->model->getWhere(["email" => $this->params['email']], "id, vID, name, email");
                    if ($person) {
                        $person[0]["first_login"] = true;
                    }
                } else response(500);
            } else {
                $person = $this->model->getWhere(["email" => $this->params['email']], "id, vID, name, email, emailCode, lastLoginTime");
                $diff = strtotime('now') - strtotime($person[0]["lastLoginTime"]);
                if (!empty($person[0]["emailCode"]) && $diff < 180) {
                    response(NULL, 202);
                }
                if ($person) $this->model->update($person[0]["id"], ["emailCode" => $code]);
            }

            response($person["0"]["first_login"] ? ["first_login" => true] : NULL, 200, "Email sent.", true);
            if ($person[0]["first_login"] == true) $this->firstLoginMail($this->params["email"], $this->params["password"]);
            // SEND EMAIL...
            try {
                $mail = createMailer();
                $mail->addAddress($this->params["email"]);
                $mail->Subject = 'Login Code: ' . $code;
                $mail->Body = "Login Code: $code\n\nThis code is valid for 3 minutes.";
                $mail->send();
            } catch (Exception $e) {
                response(NULL, 500, "", true);
                error_log("Failed to send login code email: #" . strtotime('now') . " -> " . $e->getMessage());
            }
        } else if (!empty($this->params["email-code"])) {
            checkRequiredParams(["email-code", "email"], $this->params);
            $person = $this->model->getWhere(["email" => $this->params['email'], "emailCode" => $this->params["email-code"]], "id, vID, name, email, lastLoginTime");

            if ($person) {
                $diff = strtotime('now') - strtotime($person[0]["lastLoginTime"]);
                if ($person[0]["lastLoginTime"] == NULL || $diff < 180) {
                    $person[0]["timeDiff"] = $diff;
                    $this->model->update($person[0]["id"], ["emailCode" => null]);
                    $cookieStatus = $this->refreshCookie($person[0], "jwt_token");
                    if ($cookieStatus) response($person[0], 200, "Login successful.");
                    else response(NULL, 500, "Internal Server Error");
                } else response(NULL, 203, "Time of Code has expired!");
            } else {
                response(NULL, 400, "Login error!");
            }
        }
    }

    function logout()
    {
        $logoutData = ["fcmToken" => NULL];
        $this->model->update(AuthMiddleware::$person["id"], $logoutData);
        $this->refreshCookie(NULL, "jwt_token", true);
        response(NULL, 200, "Logout successful.");
    }

    function forgotPassword()
    {
        // TODO: Implement this method
    }

    function profile()
    {
        $person = AuthMiddleware::$person;
        $personEncryptionKey = $this->model->getWhere(["id" => $person["id"]], "encryptionKey");
        $person["encryptionKey"] = $personEncryptionKey[0]["encryptionKey"];
        if (empty($person["encryptionKey"])) {
            $person["encryptionKey"] = bin2hex(random_bytes(32));
            $newData = [
                "encryptionKey" => $person["encryptionKey"]
            ];
            $this->model->update($person["id"], $newData);
        }
        $person["firebase_token"] = $this->generateFirebaseCustomToken($person["vID"]);
        response($person, 200, "Profile fetched.");
    }

    function profileDetails()
    {
        $person = $this->model->getWhere(["id" => AuthMiddleware::$person["id"]], "id, vID, email, name, nickname, officialID, phoneNo, job, accessTypeID, fcmToken");
        if ($person) response($person[0]);
        else response(NULL, 404);
    }

    function update()
    {
        if (count($this->params) == 0) response(NULL, 400, 'Missing required parameters');

        // New vID process 
        if ($this->params["newID"] === true) {
            $newID = $this->generateUniqueVid();
            $newData = [
                "vID" => $newID
            ];
            $this->updateResponse($newData);
        }

        // Access Type Changing...
        if (isset($this->params["accessTypeID"])) {
            if (
                !in_array(
                    $this->params["accessTypeID"],
                    [ApiConstants::getWord("accessTypes", "private"), ApiConstants::getWord("accessTypes", "shared"), ApiConstants::getWord("accessTypes", "public")]
                )
            ) response(NULL, 400, 'Invalid parameter!');
            $newData = [
                "accessTypeID" => $this->params["accessTypeID"]
            ];
            $this->updateResponse($newData);
        }

        // Other new datas process
        $newData = [];
        foreach ($this->params as $key => $value) {
            if (in_array($key, ["name", "nickname", "officialID", "phoneNo", "job"])) {
                if (is_string($value) && strlen($value) > 0) $newData[$key] = $value;
                else $newData[$key] = NULL;
            }
        }

        if (count($newData) > 0) {
            $this->updateResponse($newData);
        } else response(NULL, 400, 'Missing required parameters');
    }


    function updateResponse($newData)
    {
        $result = $this->model->update(AuthMiddleware::$person["id"], $newData);

        if ($result) {
            $person = $this->model->getWhere(["id" => AuthMiddleware::$person["id"]], "id, vID, email, name, nickname, officialID, phoneNo, job, accessTypeID");
            $payload = [
                'id' => $result['id'],
                "vID" => $result['vID'],
                'name' => $result['name'],
                'email' => $result['email'],
            ];
            $this->refreshCookie($payload, "jwt_token");
            response($person[0]);
        } else response(NULL, 500);
    }

    function getFCMToken()
    {
        $person = $this->model->getWhere(["id" => AuthMiddleware::$person["id"]], "fcmToken");
        response($person[0]);
    }

    function updateFCMToken()
    {
        checkRequiredParams(["fcmToken"], $this->params);
        $newData = [
            "fcmToken" => $this->params["fcmToken"]
        ];
        $result = $this->model->update(AuthMiddleware::$person["id"], $newData);
        if ($result) response($newData);
        else response(NULL, 500);
    }

    function notificationTest()
    {
        $person = $this->model->getWhere(["id" => AuthMiddleware::$person["id"]], "fcmToken");
        $response = sendNotification($person[0]["fcmToken"], "Hadi Sohbete!", "Tıkla ve sohbete başla =)", "/create-qr", ["x" => "y", "a" => ["b" => "c"]]);
        response($response);
    }

    function firstLoginMail($email, $password)
    {
        $body = "
            <h2>Welcome to QRID!</h2>
            <p>You have successfully signed up. Here are your login details:</p>
            <ul>
                <li><strong>Email:</strong> {$email}</li>
                <li><strong>Temporary Password:</strong> {$password}</li>
            </ul>
            <p>Please make sure to change your password after logging in.</p>
            <p><a href='https://qrid.space/login'>Click here to log in</a></p>
            <br />
            <p style='color:gray; font-size: 0.9rem;'>If you did not request this, you can safely ignore this email.</p>
        ";
        $altBody = "Welcome to QRID!\n\nEmail: {$email}\nTemporary Password: {$password}\n\nLogin here: https://qrid.space/login";

        sendMail($email, 'Welcome to QRID!', $body, $altBody, "welcome mail");
    }
}
