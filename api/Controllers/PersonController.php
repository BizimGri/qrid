<?php

require_once __DIR__ . '/../Models/PersonModel.php';

class PersonController extends MainController
{
    public function __construct()
    {
        parent::__construct(new PersonModel());
    }

    function register()
    {
        checkRequiredParams(['name', 'email', 'password'], $this->params);

        if ($this->model->exists(["email" => $this->params['email']])) {
            response(NULL, 409, "Email already exists.");
        }

        $vID = $this->generateUniqueVid("person");

        $person = [
            "vID" => $vID,
            "name" => $this->params['name'],
            "email" => $this->params['email'],
            "password" => hashPassword($this->params['password'])
        ];

        $this->model->create($person, false) ? response([], 201, "Person created.") : response(NULL, 500, "Internal Server Error");
    }

    function login()
    {
        checkRequiredParams(['email', 'password'], $this->params);

        if (!$this->model->exists(["email" => $this->params['email']])) {
            response(NULL, 404, "Person not found.");
        }

        $person = $this->model->getWhere(["email" => $this->params['email']], "id, vID, name, email, password");

        if (!verifyPassword($this->params['password'], $person[0]['password'])) {
            response(NULL, 401, "Invalid login credentials.");
        }

        unset($person[0]["password"]);
        $cookieStatus = $this->refreshCookie($person[0], "jwt_token");

        if ($cookieStatus) response($person[0], 200, "Login successful.");
        else response(NULL, 500, "Internal Server Error");
    }

    function logout()
    {
        $this->refreshCookie(NULL, "jwt_token", true);
        response(NULL, 200, "Logout successful.");
    }

    function forgotPassword()
    {
        // TODO: Implement this method
    }

    function profile()
    {
        response(AuthMiddleware::$person, 200, "Profile fetched.");
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
            $result = $this->model->update(AuthMiddleware::$person["id"], $newData, true);
            if ($result) {
                $payload = [
                    'id' => $result['id'],
                    "vID" => $result['vID'],
                    'name' => $result['name'],
                    'email' => $result['email'],
                ];
                $this->refreshCookie($payload, "jwt_token");
                response($payload, 200, 'New id setted!');
            } else response(NULL, 500);
        }

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
            $result = $this->model->update(AuthMiddleware::$person["id"], $newData, true);
            if ($result) response();
            else response(NULL, 500);
        }

        $newData = [];

        foreach ($this->params as $key => $value) {
            if (in_array($key, ["name", "nickname", "officialID", "phoneNo", "job"])) {
                if (is_string($value) && strlen($value) > 0) $newData[$key] = $value;
                else $newData[$key] = NULL;
            }
        }

        if (count($newData)) {
            $result = $this->model->update(AuthMiddleware::$person["id"], $newData);
            $payload = [
                'id' => $result['id'],
                "vID" => $result['vID'],
                'name' => $result['name'],
                'email' => $result['email'],
            ];
            $this->refreshCookie($payload, "jwt_token");
            response($payload);
        } else response(NULL, 400, 'Missing required parameters');
    }
}
