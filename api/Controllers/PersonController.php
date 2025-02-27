<?php

require_once __DIR__ . '/../Models/PersonModel.php';

class PersonController extends MainController
{
    public function __construct()
    {
        parent::__construct(new PersonModel());
    }

    function register() {
        checkRequiredParams(['name', 'email', 'password'], $this->params);

        if($this->model->exists(["email" => $this->params['email']])) {
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

    function login() {
        require_once __DIR__ . '/../Helpers/JwtHandler.php';

        checkRequiredParams(['email', 'password'], $this->params);

        if (!$this->model->exists(["email" => $this->params['email']])) {
            response(NULL, 404, "Person not found.");
        }

        $person = $this->model->getWhere(["email" => $this->params['email']], "id, vID, name, email, password");

        if (!verifyPassword($this->params['password'], $person[0]['password'])) {
            response(NULL, 401, "Invalid login credentials.");
        }

        $payload = [
            'id' => $person[0]['id'],
            "vID" => $person[0]['vID'],
            'name' => $person[0]['name'],
            'email' => $person[0]['email'],
            'exp' => time() + (60 * 60 * 24) // Token will be valid for 24 hours
        ];

        $jwt = JwtHandler::encode($payload);

        setcookie("jwt_token", $jwt, [
            "expires" => time() + 86400,
            "path" => "/",
            "httponly" => true,  // Disable JavaScript access
            "secure" => true,    // Require HTTPS
            "samesite" => "Strict"
        ]);

        response(NULL, 200, "Login successful.");
    }
}
